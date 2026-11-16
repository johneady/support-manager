<?php

declare(strict_types=1);

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Images are published for releases only: ci.yml's publish job runs when
 * docker/release-version.sh says the pushed commit raised the version in
 * .release-please-manifest.json, which only merging the release-please PR
 * does. Dependabot commits that change the image are fix(deps), so they cut
 * a release instead of waiting for the next feature.
 *
 * The script tests build a throwaway git repository whose history raises (or
 * does not raise) the version, and run the script inside it.
 */
beforeEach(function () {
    $this->work = sys_get_temp_dir().'/release-version-'.bin2hex(random_bytes(6));
    $this->repo = "{$this->work}/repo";
    File::ensureDirectoryExists($this->repo);

    $this->git = function (string ...$args): string {
        return trim(Process::path($this->repo)
            ->env(['GIT_AUTHOR_NAME' => 'Test', 'GIT_AUTHOR_EMAIL' => 'test@example.com', 'GIT_COMMITTER_NAME' => 'Test', 'GIT_COMMITTER_EMAIL' => 'test@example.com'])
            ->run(['git', ...$args])
            ->throw()
            ->output());
    };

    $this->commitVersion = function (string $version): void {
        File::put("{$this->repo}/.release-please-manifest.json", json_encode(['.' => $version]));
        ($this->git)('add', '.');
        ($this->git)('commit', '-q', '-m', "release {$version}");
    };

    $this->commitFile = function (string $message): void {
        File::put("{$this->repo}/work.txt", $message);
        ($this->git)('add', '.');
        ($this->git)('commit', '-q', '-m', $message);
    };

    $this->runScript = function (): ProcessResult {
        File::delete("{$this->work}/output");

        return Process::path($this->repo)
            ->env(['GITHUB_OUTPUT' => "{$this->work}/output"])
            ->run([base_path('docker/release-version.sh')]);
    };

    $this->output = fn (): string => File::exists("{$this->work}/output") ? File::get("{$this->work}/output") : '';

    ($this->git)('init', '-q', '-b', 'main');
});

afterEach(function () {
    File::deleteDirectory($this->work);
});

test('a commit that raises the version is a release', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->output)())->toBe("version=1.1.0\n");
});

test('a pre-release that raises the version is a release', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0-beta.1');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->output)())->toBe("version=1.1.0-beta.1\n");
});

test('an ordinary merge after a release is not a release', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0');
    ($this->commitFile)('fix(deps): bump something');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and($result->output())->toContain('Not a release')
        ->and(($this->output)())->toBe("version=\n");
});

test('a commit that lowers the version is not a release', function () {
    ($this->commitVersion)('1.1.0');
    ($this->commitVersion)('1.0.0');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and(($this->output)())->toBe("version=\n");
});

test('the commit that adds the manifest is not a release', function () {
    ($this->commitFile)('chore: start');
    ($this->commitVersion)('1.0.0');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and(($this->output)())->toBe("version=\n");
});

test('the first commit in the repository is not a release', function () {
    ($this->commitVersion)('1.0.0');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and(($this->output)())->toBe("version=\n");
});

/**
 * GitHub merges the release PR with a merge commit. Its first parent is the
 * previous tip of main, so the version it raised is read against that.
 */
test('merging a release pull request with a merge commit is a release', function () {
    ($this->commitVersion)('1.0.0');
    ($this->git)('checkout', '-q', '-b', 'release-please--branches--main');
    ($this->commitVersion)('1.1.0');
    ($this->git)('checkout', '-q', 'main');
    ($this->commitFile)('fix: something merged meanwhile');
    ($this->git)('merge', '-q', '--no-ff', '-m', 'Merge pull request #1', 'release-please--branches--main');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->output)())->toBe("version=1.1.0\n");
});

test('a depth-1 clone fails instead of guessing', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0');
    $shallow = "{$this->work}/shallow";
    Process::run(['git', 'clone', '-q', '--depth', '1', "file://{$this->repo}", $shallow])->throw();
    $this->repo = $shallow;

    $result = ($this->runScript)();

    expect($result->successful())->toBeFalse()
        ->and($result->errorOutput())->toContain('fetch-depth: 2');
});

test('a depth-2 clone, as CI checks out, is enough', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0');
    $shallow = "{$this->work}/shallow";
    Process::run(['git', 'clone', '-q', '--depth', '2', "file://{$this->repo}", $shallow])->throw();
    $this->repo = $shallow;

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->output)())->toBe("version=1.1.0\n");
});

test('ci.yml publishes an image only for a release commit', function () {
    $workflow = File::get(base_path('.github/workflows/ci.yml'));

    expect($workflow)
        ->toContain('release-version: ${{ steps.release.outputs.version }}')
        ->toMatch("/- name: Check for a release\n\s+id: release\n\s+run: docker\/release-version.sh/")
        ->toContain("if: github.event_name == 'push' && github.ref == 'refs/heads/main' && needs.checks.outputs.release-version != ''");

    $checks = substr($workflow, strpos($workflow, "  checks:\n"));
    preg_match('/actions\/checkout@v\d+\n(\s+with:\n(?:\s+#.*\n)*\s+fetch-depth: (\d+))?/', $checks, $checkout);

    expect($checkout[2] ?? '1')->not->toBe('1');
});

/**
 * Only releases publish, and a Dependabot merge can land while a release is
 * still building, so on main the newest release commit takes `latest`.
 */
test('latest follows the newest release commit on the default branch', function () {
    expect(File::get(base_path('.github/workflows/docker.yml')))
        ->toContain('newest="$(git log -1 --first-parent --format=%H FETCH_HEAD -- .release-please-manifest.json)"')
        ->toContain('if [ "$GITHUB_REF" = "refs/heads/$DEFAULT_BRANCH" ]; then')
        ->toContain('newest="$(git ls-remote origin "$GITHUB_REF" | cut -f1)"');
});

test('dependabot updates that change the image are fix commits', function (string $ecosystem, string $prefix) {
    $config = File::get(base_path('.github/dependabot.yml'));
    $blocks = preg_split('/^  - package-ecosystem: /m', $config);
    $block = collect($blocks)->first(fn (string $block): bool => str_starts_with($block, "{$ecosystem}\n"));

    if ($block === null) {
        $this->markTestSkipped("{$ecosystem} is not configured here.");
    }

    expect($block)->toMatch("/commit-message:\n\s+prefix: {$prefix}\n/")
        ->toContain('include: scope');
})->with([
    'composer' => ['composer', 'fix'],
    'npm' => ['npm', 'fix'],
    'docker' => ['docker', 'fix'],
    'github-actions' => ['github-actions', 'ci'],
]);

test('composer dev dependencies cut no release', function () {
    $config = File::get(base_path('.github/dependabot.yml'));

    expect($config)->toMatch('/prefix-development: (chore|build)\n/');
});
