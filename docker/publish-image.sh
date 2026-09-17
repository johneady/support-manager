#!/usr/bin/env bash
# Builds the runtime image locally and pushes it to GHCR, producing exactly
# what .github/workflows/docker.yml would publish.
#
# This is a supported path, not a stopgap: the deploy contract is identical
# either way -- Dokploy pulls an immutable `sha-<full-sha>` tag from GHCR, and
# nothing about docker-compose.dokploy.yml or the Dokploy environment changes.
# Only the machine doing the build differs. Use it when Actions minutes are
# unavailable, or whenever you want to publish without waiting on CI.
#
# The one rule: whatever builds the image, it must not be the deploy host.
#
# What is deliberately preserved from the workflow, because the deploy relies
# on it:
#   - the same registry and repository path, lowercased (GHCR rejects uppercase)
#   - the same `sha-<full-sha>` tag scheme, so IMAGE_TAG means the same thing
#   - `--target runtime`, so the extension assertion in that stage still runs
#   - the artisan smoke test the workflow runs on PRs
#
# What it does NOT do: build multi-arch. The workflow is single-arch too (it
# builds on ubuntu-latest, amd64), so this matches -- but if your machine is
# arm64 and the deploy host is amd64, read the ARCH note below or the image
# will pull and then refuse to start with an exec format error.
#
# Usage:
#   ./docker/publish-image.sh              # build + push HEAD, tagged sha-<sha>
#   ./docker/publish-image.sh --also-latest
#   ./docker/publish-image.sh --dry-run    # build and smoke-test, do not push
#
# Auth: needs a GitHub PAT with `write:packages` in GHCR_TOKEN, or an already
# logged-in `gh` (the script falls back to `gh auth token`).

set -euo pipefail

cd "$(dirname "$0")/.."

ALSO_LATEST=0
DRY_RUN=0
for arg in "$@"; do
  case "$arg" in
    --also-latest) ALSO_LATEST=1 ;;
    --dry-run) DRY_RUN=1 ;;
    -h|--help) sed -n '2,30p' "$0"; exit 0 ;;
    *) echo "error: unknown argument: $arg" >&2; exit 2 ;;
  esac
done

# Refuse to publish a tag that does not describe the tree, because the whole
# point of the sha tag is that it names the exact source that produced the
# image. A dirty tree makes `sha-<sha>` a lie, and it is a lie you only catch
# when a rollback to that sha produces something you have never seen.
if ! git diff-index --quiet HEAD -- 2>/dev/null; then
  echo "error: working tree is dirty. The sha tag must name the exact source" >&2
  echo "       that built the image -- commit or stash first." >&2
  git status --short >&2
  exit 1
fi

SHA="$(git rev-parse HEAD)"

# Lowercased: GHCR rejects an uppercase path outright ("repository name must be
# lowercase"), and a GitHub owner preserves its case, so `JohnEady/boilerplate`
# would fail at push. metadata-action does this for us in the workflow.
REPO="$(git remote get-url origin \
  | sed -E 's#^(https://github\.com/|git@github\.com:)##; s#\.git$##' \
  | tr '[:upper:]' '[:lower:]')"

IMAGE="ghcr.io/${REPO}"
TAG="sha-${SHA}"

echo "Repository : ${REPO}"
echo "Image      : ${IMAGE}:${TAG}"
echo

# ARCH: the deploy host is amd64. If this machine is not, cross-building here
# is far slower than a native build (qemu emulating a 6-minute C compile), but
# an image of the wrong architecture is worse -- it pulls fine and then dies
# with "exec format error" on start. So set the platform explicitly rather than
# letting it default to the builder's own.
PLATFORM="${PLATFORM:-linux/amd64}"

# A dedicated `docker-container` builder, which is what setup-buildx-action
# gives the workflow. Docker's DEFAULT builder uses the `docker` driver, and
# that driver cannot export a cache at all -- the build dies immediately with
# "Cache export is not supported for the docker driver". So the cache flags
# below are only usable on a builder created like this one.
#
# Created once and reused; `docker buildx create` is not idempotent, so the
# inspect guards it. The builder survives reboots.
BUILDER="${BUILDER:-support-manager-publish}"
if ! docker buildx inspect "$BUILDER" >/dev/null 2>&1; then
  echo "==> Creating buildx builder: $BUILDER"
  docker buildx create --name "$BUILDER" --driver docker-container >/dev/null
fi

BUILD_ARGS=(
  --builder "$BUILDER"
  --file Dockerfile
  --target runtime
  --platform "$PLATFORM"
  --tag "${IMAGE}:${TAG}"
  # A local cache dir stands in for the workflow's `type=gha`. Without it every
  # run recompiles the extensions from scratch (~6 min), which is exactly the
  # cost this project moved off the deploy host to avoid paying repeatedly.
  --cache-from "type=local,src=.docker-cache"
  --cache-to "type=local,dest=.docker-cache,mode=max"
)

if [ "$ALSO_LATEST" -eq 1 ]; then
  BUILD_ARGS+=(--tag "${IMAGE}:latest")
fi

# --load in both cases, never --push. The push happens as a separate step below,
# AFTER the smoke test -- with `buildx build --push` the image reaches the
# registry before anything has run it, so a broken build is already pullable and
# IMAGE_TAG names something that cannot boot.
BUILD_ARGS+=(--load)

echo "==> Building (this compiles the PHP extensions; ~6 min on a cold cache)"
docker buildx build "${BUILD_ARGS[@]}" .

# The same check the workflow runs on PRs: the build's own assertion proves the
# extensions are present, this proves Laravel actually boots inside them.
#
# --entrypoint php is required -- the image ENTRYPOINT intercepts commands and
# exits on a missing APP_KEY, which is not set here.
echo
echo "==> Smoke-testing the image"
docker run --rm --entrypoint php "${IMAGE}:${TAG}" artisan --version

if [ "$DRY_RUN" -eq 1 ]; then
  echo
  echo "Dry run: built and smoke-tested ${IMAGE}:${TAG}, not pushed."
  exit 0
fi

echo
echo "==> Logging in to GHCR"
TOKEN="${GHCR_TOKEN:-}"
if [ -z "$TOKEN" ] && command -v gh >/dev/null 2>&1; then
  TOKEN="$(gh auth token 2>/dev/null || true)"
fi
if [ -z "$TOKEN" ]; then
  echo "error: no credential. Set GHCR_TOKEN to a PAT with write:packages," >&2
  echo "       or run 'gh auth login' with that scope." >&2
  exit 1
fi
# GHCR ignores the username when the password is a PAT, but docker login still
# requires one to be present -- and it must not be `git config user.name`, which
# is a display name ("John Eady"), not a GitHub login. The repo owner is the one
# name that is always correct here.
GHCR_USER="${GHCR_USER:-${REPO%%/*}}"
printf '%s' "$TOKEN" | docker login ghcr.io -u "$GHCR_USER" --password-stdin

echo
echo "==> Pushing"
docker push "${IMAGE}:${TAG}"
if [ "$ALSO_LATEST" -eq 1 ]; then
  docker push "${IMAGE}:latest"
fi

cat <<EOF

Published ${IMAGE}:${TAG}

Deploy by setting this in the Dokploy environment, then redeploying:

    IMAGE_TAG=${TAG}

EOF
