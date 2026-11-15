#!/usr/bin/env bash
#
# Gives the latest release's image its version tags (1.17.0 and 1.17), by
# adding them to the sha-<sha> image already in the registry. Nothing is
# built, so the version names the exact bytes that were tested and pushed.
#
# release-please tags releases with GITHUB_TOKEN, which fires no workflow, so
# docker.yml runs this after every publish on main instead. The release is
# the commit that last raised the version in .release-please-manifest.json.
# Because any later run on main can supply the tags, a release commit whose
# own run failed late or was re-run is not left untagged. A version tag that
# already exists is never moved: a rebuild must not change what a published
# version names.
#
# The tags are written through the registry's HTTP API, copying the sha
# image's manifest byte for byte, so every tag shares one digest and the
# registry lists one image. `docker buildx imagetools create` would wrap a
# single-platform image in a new index instead: the same layers under a
# second digest, which GHCR shows as a separate image.
#
# Needs the full history (fetch-depth: 0) and GITHUB_TOKEN with
# packages: write. Only curl, sed and sha256sum, no docker or jq.
#
#   docker/tag-release.sh ghcr.io/johneady/support-manager

set -euo pipefail

image="${1:?usage: docker/tag-release.sh <image>}"
manifest=.release-please-manifest.json

# POSIX sed, not jq or grep -P: the self-hosted runners may have neither.
read_version() {
    sed -n 's/.*"\."[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p'
}

# sort -V ranks 1.18.0 below 1.18.0-beta.1, so a final release outranking its
# own pre-releases is checked first.
is_newer() {
    local new="$1" old="$2"

    [ "${new}" != "${old}" ] || return 1

    if [ "${new%%-*}" = "${old%%-*}" ]; then
        [ "${new}" = "${new%%-*}" ] && return 0
        [ "${old}" = "${old%%-*}" ] && return 1
    fi

    [ "$(printf '%s\n%s\n' "${old}" "${new}" | sort -V | tail -n1)" = "${new}" ]
}

registry="${image%%/*}"
repository="${image#*/}"
accept="application/vnd.oci.image.index.v1+json,application/vnd.oci.image.manifest.v1+json,application/vnd.docker.distribution.manifest.list.v2+json,application/vnd.docker.distribution.manifest.v2+json"
work="$(mktemp -d)"
trap 'rm -rf "${work}"' EXIT
fetched_type=

registry_token() {
    curl -fsS -u "${GITHUB_ACTOR:-ci}:${GITHUB_TOKEN:?GITHUB_TOKEN is required}" \
        "https://${registry}/token?service=${registry}&scope=repository:${repository}:pull,push" |
        sed -n 's/.*"token"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p'
}

# Saves the tag's manifest to $2 and its media type in $fetched_type. Returns
# 1 when the tag does not exist; any other failure stops the script, so an
# unreachable registry is never mistaken for a missing tag. Never call it in
# $(...): the exit would only leave the subshell.
fetch_manifest() {
    local tag="$1" out="$2" status

    status="$(curl -sS -o "${out}" -w '%{http_code} %{content_type}' \
        -H "Authorization: Bearer ${token}" -H "Accept: ${accept}" \
        "https://${registry}/v2/${repository}/manifests/${tag}")"

    case "${status}" in
        "200 "*) fetched_type="${status#200 }" ;;
        "404 "*) return 1 ;;
        *)
            echo "::error::Reading ${image}:${tag} failed: HTTP ${status%% *}." >&2
            exit 1
            ;;
    esac
}

put_manifest() {
    local tag="$1" file="$2" media_type="$3"

    curl -fsS -o /dev/null -X PUT \
        -H "Authorization: Bearer ${token}" -H "Content-Type: ${media_type}" \
        --data-binary "@${file}" \
        "https://${registry}/v2/${repository}/manifests/${tag}"
}

digest_of() {
    echo "sha256:$(sha256sum "$1" | cut -d' ' -f1)"
}

# What `imagetools create` used to write: an index whose only entry is the
# source image. It names the same bytes, so pointing its tag straight at the
# source changes nothing that runs.
is_wrapper_of() {
    local file="$1" media_type="$2" digest="$3"

    case "${media_type}" in
        *index* | *manifest.list*) ;;
        *) return 1 ;;
    esac

    [ "$(grep -o '"digest"' "${file}" | wc -l)" -eq 1 ] && grep -q "\"${digest}\"" "${file}"
}

# A shallow clone hides the release commit's parent, so the previous version
# reads as empty and the release looks like it raised nothing. Fail instead
# of quietly tagging nothing.
if [ "$(git rev-parse --is-shallow-repository)" = true ]; then
    echo "::error::This is a shallow clone; tag-release.sh needs the full history (fetch-depth: 0)." >&2
    exit 1
fi

release_commit="$(git log -1 --first-parent --format=%H -- "${manifest}")"
version="$(git show "${release_commit}:${manifest}" | read_version)"
previous="$(git show "${release_commit}^:${manifest}" 2>/dev/null | read_version || true)"

# Build metadata (1.18.0+build.1) is not a valid image tag, and release-please
# never writes it.
if ! printf '%s' "${version}" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$'; then
    echo "Version '${version}' cannot be an image tag; nothing tagged."
    exit 0
fi

# The version has to go up, not just change: a revert of a release commit sets
# it back, and that commit is not the older release.
if [ -z "${previous}" ] || ! is_newer "${version}" "${previous}"; then
    echo "${release_commit} did not raise the version (${previous:-none} to ${version}); nothing tagged."
    exit 0
fi

source_tag="sha-${release_commit}"
token="$(registry_token)"

# The source first: if this lookup works, a miss on the version tag below is
# a real miss, not the registry being unreachable.
if ! fetch_manifest "${source_tag}" "${work}/source"; then
    echo "::warning::Release ${version} has no image tag: ${image}:${source_tag} was never published. Re-run that commit's workflow to publish it."
    exit 0
fi
source_type="${fetched_type}"
source_digest="$(digest_of "${work}/source")"

# Like metadata-action's semver pattern, the floating X.Y tag skips
# pre-releases.
minor=
if [ "${version}" = "${version%%-*}" ]; then
    minor="${version%.*}"
fi

if fetch_manifest "${version}" "${work}/existing"; then
    existing_type="${fetched_type}"
    existing_digest="$(digest_of "${work}/existing")"

    if [ "${existing_digest}" = "${source_digest}" ]; then
        echo "${image}:${version} already names ${source_tag}; left alone."
        exit 0
    fi

    if ! is_wrapper_of "${work}/existing" "${existing_type}" "${source_digest}"; then
        echo "${image}:${version} already exists as a different image; left alone."
        exit 0
    fi

    # X.Y moves with it only if it still names the same wrapper; a newer
    # patch may already have taken it.
    if [ -n "${minor}" ] && fetch_manifest "${minor}" "${work}/minor" &&
        [ "$(digest_of "${work}/minor")" != "${existing_digest}" ]; then
        minor=
    fi

    echo "${image}:${version} wraps ${source_tag} in a second index; pointing it at the image itself."
fi

put_manifest "${version}" "${work}/source" "${source_type}"
if [ -n "${minor}" ]; then
    put_manifest "${minor}" "${work}/source" "${source_type}"
fi

echo "Tagged ${image}:${source_tag} (${source_digest}) as ${version}${minor:+ and ${minor}}."

if [ -n "${GITHUB_OUTPUT:-}" ]; then
    {
        echo "version=${version}"
        echo "minor=${minor}"
    } >>"${GITHUB_OUTPUT}"
fi
