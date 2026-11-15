<?php

declare(strict_types=1);

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Exercises docker/tag-release.sh, which gives a release's sha-<commit> image
 * its X.Y.Z and X.Y tags in GHCR after docker.yml publishes it.
 *
 * Each test builds a throwaway git repository whose history raises (or does
 * not raise) the version in .release-please-manifest.json, and puts a fake
 * curl first on PATH that serves manifests from a directory standing in for
 * the registry. A PUT writes the uploaded manifest there, so the assertions
 * read back which tags exist and what bytes they name.
 */
beforeEach(function () {
    $this->work = sys_get_temp_dir().'/tag-release-'.bin2hex(random_bytes(6));
    $this->repo = "{$this->work}/repo";
    $this->registry = "{$this->work}/registry";
    $this->bin = "{$this->work}/bin";

    File::ensureDirectoryExists($this->repo);
    File::ensureDirectoryExists($this->registry);
    File::ensureDirectoryExists($this->bin);

    File::put("{$this->bin}/curl", <<<'BASH'
        #!/usr/bin/env bash
        out= write= method=GET data= url=
        while [ $# -gt 0 ]; do
            case "$1" in
                -o) out="$2"; shift ;;
                -w) write="$2"; shift ;;
                -X) method="$2"; shift ;;
                -H|-u) shift ;;
                --data-binary) data="${2#@}"; shift ;;
                -*) ;;
                *) url="$1" ;;
            esac
            shift
        done

        case "$url" in
            */token\?*) echo '{"token":"fake"}'; exit 0 ;;
        esac

        tag="${url##*/manifests/}"
        if [ "$method" = PUT ]; then
            cp "$data" "$FAKE_REGISTRY/$tag"
            echo "$tag" >> "$FAKE_REGISTRY/.puts"
            exit 0
        fi

        if [ -f "$FAKE_REGISTRY/$tag" ]; then
            cp "$FAKE_REGISTRY/$tag" "$out"
            if grep -q '"manifests"' "$FAKE_REGISTRY/$tag"; then
                printf '200 application/vnd.oci.image.index.v1+json'
            else
                printf '200 application/vnd.oci.image.manifest.v1+json'
            fi
        else
            : > "$out"
            printf '404 text/plain'
        fi
        BASH);
    chmod("{$this->bin}/curl", 0755);

    $this->git = function (string ...$args): string {
        return trim(Process::path($this->repo)
            ->env(['GIT_AUTHOR_NAME' => 'Test', 'GIT_AUTHOR_EMAIL' => 'test@example.com', 'GIT_COMMITTER_NAME' => 'Test', 'GIT_COMMITTER_EMAIL' => 'test@example.com'])
            ->run(['git', ...$args])
            ->throw()
            ->output());
    };

    $this->commitVersion = function (string $version): string {
        File::put("{$this->repo}/.release-please-manifest.json", json_encode(['.' => $version]));
        ($this->git)('add', '.');
        ($this->git)('commit', '-q', '-m', "release {$version}");

        return ($this->git)('rev-parse', 'HEAD');
    };

    $this->publishSha = function (string $sha, string $manifest = '{"layers":["a"]}'): void {
        File::put("{$this->registry}/sha-{$sha}", $manifest);
    };

    $this->runScript = function (): ProcessResult {
        File::delete("{$this->work}/output");

        return Process::path($this->repo)
            ->env([
                'PATH' => "{$this->bin}:".getenv('PATH'),
                'FAKE_REGISTRY' => $this->registry,
                'GITHUB_TOKEN' => 'fake',
                'GITHUB_OUTPUT' => "{$this->work}/output",
            ])
            ->run([base_path('docker/tag-release.sh'), 'ghcr.io/owner/app']);
    };

    $this->puts = fn (): array => File::exists("{$this->registry}/.puts")
        ? file("{$this->registry}/.puts", FILE_IGNORE_NEW_LINES)
        : [];

    ($this->git)('init', '-q', '-b', 'main');
});

afterEach(function () {
    File::deleteDirectory($this->work);
});

test('a release that raises the version tags its sha image with the version and minor', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0');
    ($this->publishSha)($sha);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->puts)())->toBe(['1.1.0', '1.1'])
        ->and(File::get("{$this->registry}/1.1.0"))->toBe(File::get("{$this->registry}/sha-{$sha}"))
        ->and(File::get("{$this->registry}/1.1"))->toBe(File::get("{$this->registry}/sha-{$sha}"))
        ->and(File::get("{$this->work}/output"))->toBe("version=1.1.0\nminor=1.1\n");
});

/**
 * Release-please tags with GITHUB_TOKEN, so the release commit's own run is
 * not the only chance: a later commit on main supplies the tags too.
 */
test('a later commit on main tags the last release commit, not itself', function () {
    ($this->commitVersion)('1.0.0');
    $releaseSha = ($this->commitVersion)('1.2.0');
    File::put("{$this->repo}/feature.txt", 'later work');
    ($this->git)('add', '.');
    ($this->git)('commit', '-q', '-m', 'feat: later work');
    ($this->publishSha)($releaseSha);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->puts)())->toBe(['1.2.0', '1.2'])
        ->and(File::get("{$this->registry}/1.2.0"))->toBe(File::get("{$this->registry}/sha-{$releaseSha}"));
});

test('a commit that lowers the version tags nothing', function () {
    ($this->commitVersion)('1.1.0');
    $sha = ($this->commitVersion)('1.0.0');
    ($this->publishSha)($sha);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and($result->output())->toContain('did not raise the version')
        ->and(($this->puts)())->toBe([]);
});

test('the first manifest commit tags nothing because there is no previous version', function () {
    $sha = ($this->commitVersion)('1.0.0');
    ($this->publishSha)($sha);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and(($this->puts)())->toBe([]);
});

test('a release whose sha image was never published warns and tags nothing', function () {
    ($this->commitVersion)('1.0.0');
    ($this->commitVersion)('1.1.0');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and($result->output())->toContain('was never published')
        ->and(($this->puts)())->toBe([]);
});

/**
 * A rebuild must never change what a published version names.
 */
test('an existing version tag naming a different image is left alone', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0');
    ($this->publishSha)($sha, '{"layers":["new"]}');
    File::put("{$this->registry}/1.1.0", '{"layers":["original"]}');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue()
        ->and($result->output())->toContain('already exists as a different image')
        ->and(($this->puts)())->toBe([])
        ->and(File::get("{$this->registry}/1.1.0"))->toBe('{"layers":["original"]}');
});

test('a pre-release gets its version tag but does not move the minor tag', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0-beta.1');
    ($this->publishSha)($sha);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->puts)())->toBe(['1.1.0-beta.1']);
});

test('the docker workflow runs the script on main after publishing', function () {
    $workflow = File::get(base_path('.github/workflows/docker.yml'));

    expect($workflow)
        ->toContain("fetch-depth: \${{ github.ref != 'refs/heads/main' && 1 || 0 }}")
        ->toContain('docker/tag-release.sh "ghcr.io/${GITHUB_REPOSITORY,,}"')
        ->toMatch("/- name: Tag the release\n\s+id: release\n\s+if: github.event_name != 'pull_request' && github.ref == 'refs\/heads\/main'/")
        ->and(strpos($workflow, '- name: Build and push'))->toBeLessThan(strpos($workflow, '- name: Tag the release'));
});

/**
 * `docker buildx imagetools create` wraps a single-platform image in a
 * one-entry index under a second digest. A version tag left like that is
 * re-pointed at the image itself, and its minor tag moves with it.
 */
test('a version tag that only wraps the sha image is re-pointed at the image itself', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0');
    $image = '{"layers":["a"]}';
    ($this->publishSha)($sha, $image);
    $digest = 'sha256:'.hash('sha256', $image);
    $wrapper = '{"manifests":[{"digest":"'.$digest.'"}]}';
    File::put("{$this->registry}/1.1.0", $wrapper);
    File::put("{$this->registry}/1.1", $wrapper);

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and($result->output())->toContain('pointing it at the image itself')
        ->and(($this->puts)())->toBe(['1.1.0', '1.1'])
        ->and(File::get("{$this->registry}/1.1.0"))->toBe($image)
        ->and(File::get("{$this->registry}/1.1"))->toBe($image);
});

test('re-pointing a wrapped version leaves a minor tag a newer patch already took', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0');
    $image = '{"layers":["a"]}';
    ($this->publishSha)($sha, $image);
    File::put("{$this->registry}/1.1.0", '{"manifests":[{"digest":"sha256:'.hash('sha256', $image).'"}]}');
    File::put("{$this->registry}/1.1", '{"layers":["patch"]}');

    $result = ($this->runScript)();

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and(($this->puts)())->toBe(['1.1.0'])
        ->and(File::get("{$this->registry}/1.1"))->toBe('{"layers":["patch"]}');
});

test('latest and the branch tag only move for the current tip of the default branch', function () {
    $workflow = File::get(base_path('.github/workflows/docker.yml'));

    expect($workflow)
        ->toContain("type=ref,event=branch,enable=\${{ steps.tip.outputs.current == 'true' }}")
        ->toContain("type=raw,value=latest,enable=\${{ steps.tip.outputs.latest == 'true' }}")
        ->toContain('tip="$(git ls-remote origin "$GITHUB_REF" | cut -f1)"')
        ->toContain('if [ "$current" = true ] && [ "$GITHUB_REF" = "refs/heads/$DEFAULT_BRANCH" ]; then');
});

/**
 * A cancelled run on main never publishes its sha image, so a release commit
 * cancelled by the next merge could never get its version tags.
 */
test('runs on main are never cancelled by a newer commit', function (string $path) {
    expect(File::get(base_path($path)))
        ->toMatch("/group: [a-z]+-\\$\{\{ github.workflow \}\}-\\$\{\{ github.ref == 'refs\/heads\/main' && github.sha \|\| github.ref \}\}/");
})->with([
    'ci' => '.github/workflows/ci.yml',
    'docker' => '.github/workflows/docker.yml',
]);

/**
 * Release 1.1.0 went untagged because the checkout on main was a depth-1
 * clone: the release commit's parent was missing, so the script read no
 * previous version and exited successfully having tagged nothing.
 */
test('a shallow clone fails loudly instead of tagging nothing', function () {
    ($this->commitVersion)('1.0.0');
    $sha = ($this->commitVersion)('1.1.0');
    ($this->publishSha)($sha);

    $shallow = "{$this->work}/shallow";
    Process::run(['git', 'clone', '-q', '--depth', '1', "file://{$this->repo}", $shallow])->throw();
    $this->repo = $shallow;

    $result = ($this->runScript)();

    expect($result->successful())->toBeFalse()
        ->and($result->errorOutput())->toContain('shallow clone')
        ->and(($this->puts)())->toBe([]);
});

/**
 * GitHub evaluates `a && b || c` with JavaScript-style truthiness, so a
 * falsy b (0, '', false) silently yields c. Evaluate the workflow's
 * fetch-depth expression the same way for both refs.
 */
test('the fetch-depth expression gives a full clone on main and a shallow one elsewhere', function (string $ref, int $expected) {
    preg_match("/fetch-depth: \\$\\{\\{ github.ref (==|!=) '([^']+)' && (\\d+) \\|\\| (\\d+) \\}\\}/", File::get(base_path('.github/workflows/docker.yml')), $match);

    expect($match)->not->toBeEmpty();

    [, $operator, $compared, $whenTrue, $otherwise] = $match;
    $condition = $operator === '==' ? $ref === $compared : $ref !== $compared;
    $depth = ($condition && (int) $whenTrue) ? (int) $whenTrue : (int) $otherwise;

    expect($depth)->toBe($expected);
})->with([
    'main' => ['refs/heads/main', 0],
    'pull request' => ['refs/pull/1/merge', 1],
    'tag' => ['refs/tags/v1.2.3', 1],
]);
