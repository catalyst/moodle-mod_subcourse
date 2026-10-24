#!/usr/bin/env bash

set -euo pipefail

readonly EXPECTED_TAG='v10.2.0'
readonly EXPECTED_COMPONENT='mod_subcourse'
readonly EXPECTED_VERSION='2026102400'
readonly EXPECTED_SUPPORTED='[405, 500]'

repositoryroot="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repositoryroot"

ref="${1:-HEAD}"
tag="${2:-$EXPECTED_TAG}"
outputdir="${3:-dist}"

if [[ "$tag" != "$EXPECTED_TAG" ]]; then
    echo "Expected release tag $EXPECTED_TAG, got $tag" >&2
    exit 1
fi

git cat-file -e "${ref}^{commit}"

versionphp="$(git show "${ref}:version.php")"
grep -Eq "^\\\$plugin->component = '$EXPECTED_COMPONENT';$" <<< "$versionphp"
grep -Eq "^\\\$plugin->version = $EXPECTED_VERSION;$" <<< "$versionphp"
grep -Eq "^\\\$plugin->release = $EXPECTED_VERSION;$" <<< "$versionphp"
grep -Fqx "\$plugin->supported = $EXPECTED_SUPPORTED;" <<< "$versionphp"

mkdir -p "$outputdir"
archive="$outputdir/mod_subcourse-${tag}.zip"
checksum="$archive.sha256"

git archive --format=zip --prefix=subcourse/ --output="$archive" "$ref"

archivefiles="$(unzip -Z1 "$archive")"
requiredfiles=(
    subcourse/version.php
    subcourse/lib.php
    subcourse/locallib.php
    subcourse/db/install.xml
    subcourse/lang/en/subcourse.php
    subcourse/LICENSE
    subcourse/README.md
)

for requiredfile in "${requiredfiles[@]}"; do
    grep -Fqx "$requiredfile" <<< "$archivefiles" || {
        echo "Required package file is missing: $requiredfile" >&2
        exit 1
    }
done

if grep -Eq '(^|/)(\.git|\.github|ci)(/|$)|moodle-preflight-report\.md$|\.DS_Store$|templates/\.mustachelintignore$' \
        <<< "$archivefiles"; then
    echo 'Development-only content was found in the release package:' >&2
    grep -E '(^|/)(\.git|\.github|ci)(/|$)|moodle-preflight-report\.md$|\.DS_Store$|templates/\.mustachelintignore$' \
        <<< "$archivefiles" >&2
    exit 1
fi

if grep -Ev '^subcourse(/|$)' <<< "$archivefiles" | grep -q .; then
    echo 'Release package contains content outside the subcourse root.' >&2
    exit 1
fi

temporarydir="$(mktemp -d)"
trap 'rm -rf "$temporarydir"' EXIT
unzip -q "$archive" -d "$temporarydir"

find "$temporarydir/subcourse" -type f -name '*.php' -print0 \
    | xargs -0 -n1 php -l

(
    cd "$outputdir"
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$(basename "$archive")" > "$(basename "$checksum")"
    else
        shasum -a 256 "$(basename "$archive")" > "$(basename "$checksum")"
    fi
)

echo "Created $archive"
echo "Created $checksum"
