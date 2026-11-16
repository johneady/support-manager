#!/usr/bin/env bash
#
# Says whether the checked-out commit is a release, so CI publishes an image
# for releases only. A release commit raised the version in
# .release-please-manifest.json over its first parent, which only merging the
# release-please PR does. Prints the version and writes version=X.Y.Z to
# $GITHUB_OUTPUT for a release, and version= (empty) for anything else.
#
# Needs the commit's parent, so a checkout of at least fetch-depth: 2. Only
# git and sed, no jq.
#
#   docker/release-version.sh

set -euo pipefail

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

emit() {
    if [ -n "${GITHUB_OUTPUT:-}" ]; then
        echo "version=$1" >>"${GITHUB_OUTPUT}"
    fi
}

# A depth-1 clone hides the parent, so every commit would look like a first
# release or like no release at all. Fail instead of guessing.
if ! git rev-parse -q --verify 'HEAD^' >/dev/null; then
    if [ "$(git rev-parse --is-shallow-repository)" = true ]; then
        echo "::error::This clone has no parent commit; release-version.sh needs fetch-depth: 2 or more." >&2
        exit 1
    fi

    echo "HEAD is the first commit; not a release."
    emit ""
    exit 0
fi

version="$(git show "HEAD:${manifest}" 2>/dev/null | read_version || true)"
previous="$(git show "HEAD^:${manifest}" 2>/dev/null | read_version || true)"

# The first manifest commit only records the version already released by
# hand, and a revert of a release sets the version back: neither is a release.
if [ -z "${version}" ] || [ -z "${previous}" ] || ! is_newer "${version}" "${previous}"; then
    echo "Not a release (${previous:-none} to ${version:-none}); nothing to publish."
    emit ""
    exit 0
fi

echo "Release ${version} (from ${previous})."
emit "${version}"
