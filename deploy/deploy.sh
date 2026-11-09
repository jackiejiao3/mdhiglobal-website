#!/bin/bash
# Publish tracked static website files, preserving server-only files and PHP.
set -Eeuo pipefail
umask 077
repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)
target=/home1/mdhiglob/public_html
backup_root=/home1/mdhiglob/deploy-backups/mdhiglobal
mode=${1:---production}
case "$mode" in
  --production|--dry-run) ;;
  --preview) target=/home1/mdhiglob/public_html/mdhi-preview ;;
  *) echo 'Usage: deploy.sh [--production|--dry-run|--preview]' >&2; exit 2 ;;
esac
fail() { echo "ERROR: $*" >&2; exit 1; }
for cmd in git sha256sum mktemp flock cp mv mkdir chmod; do
  command -v "$cmd" >/dev/null || fail "Required command missing: $cmd"
done
[[ "$repo" == /home1/mdhiglob/repositories/mdhiglobal-website ]] || fail 'Unexpected repository path'
cd -- "$repo"
[[ -z $(git status --porcelain) ]] || fail 'Repository has local modifications; update only through GitHub'
[[ $(git symbolic-ref --short HEAD) == main ]] || fail 'Deploy the main branch only'
sha=$(git rev-parse HEAD)
files=()
while IFS= read -r -d '' rel; do
  [[ "$rel" != /* && "$rel" != *../* ]] || fail "Unsafe tracked path: $rel"
  case "$rel" in
    *.html) [[ "$rel" != */* ]] || continue ;;
    css/*|js/*|images/*|suyee/*|scripts/*)
      case "${rel,,}" in
        *.css|*.js|*.jpg|*.jpeg|*.webp|*.png|*.gif|*.svg|*.ico|*.mp4|*.woff|*.woff2|*.ttf|*.eot) ;;
        *) continue ;;
      esac ;;
    *) continue ;;
  esac
  [[ -f "$rel" && ! -L "$rel" ]] || fail "Not a regular source file: $rel"
  files+=("$rel")
done < <(git ls-files -z)
(( ${#files[@]} > 0 )) || fail 'Empty publish manifest'
for required in index.html abtmdhi.html medi.html mggal.html manufacturing-capabilities.html mdhicont.html css/group-header.css css/about-mdhi.css css/media-manufacturing.css js/infographic-viewer.js; do
  [[ " ${files[*]} " == *" $required "* ]] || fail "Required publish file missing: $required"
done
# Refuse links in any destination component, including existing parent directories.
check_path() {
  local path=$1
  while [[ "$path" != / && "$path" != . ]]; do
    [[ ! -L "$path" ]] || fail "Symlink refused: $path"
    path=$(dirname -- "$path")
  done
}
check_path "$target"
check_path "$backup_root"
[[ -d /home1/mdhiglob/public_html ]] || fail 'Confirmed document root does not exist'
for rel in "${files[@]}"; do
  check_path "$repo/$rel"
  check_path "$target/$rel"
  [[ ! -e "$target/$rel" || -f "$target/$rel" ]] || fail "Destination is not a file: $rel"
done
echo "Commit: $sha"
echo "Destination: $target"
echo "Static files: ${#files[@]} (PHP, .htaccess, PDFs and unrelated directories excluded)"
if [[ "$mode" == --dry-run ]]; then
  printf '%s\n' "${files[@]}"
  exit 0
fi
mkdir -p -- "$backup_root"
exec 9>"$backup_root/.deploy.lock"
flock -n 9 || fail 'Another MDHI deployment is running'
snapshot=$(mktemp -d "$backup_root/$(date -u +%Y%m%dT%H%M%SZ)-${sha:0:12}-XXXXXX")
mkdir -- "$snapshot/previous"
printf '%s\n' "$sha" > "$snapshot/source-commit.txt"
printf '%s\n' "$target" > "$snapshot/target.txt"
: > "$snapshot/overwritten.txt"
: > "$snapshot/added.txt"
: > "$snapshot/source-sha256.txt"
# Back up every existing publish file before changing any website file.
for rel in "${files[@]}"; do
  sha256sum -- "$rel" >> "$snapshot/source-sha256.txt"
  if [[ -f "$target/$rel" ]]; then
    mkdir -p -- "$snapshot/previous/$(dirname -- "$rel")"
    cp -p -- "$target/$rel" "$snapshot/previous/$rel"
    printf '%s\n' "$rel" >> "$snapshot/overwritten.txt"
  else
    printf '%s\n' "$rel" >> "$snapshot/added.txt"
  fi
done
temp_file=
finish() {
  local result=$?
  [[ -z ${temp_file:-} ]] || rm -f -- "$temp_file"
  if (( result != 0 )); then
    printf 'FAILED\n' > "$snapshot/status.txt"
    echo "Deployment stopped; some files may have been updated. Restore existing files from: $snapshot/previous" >&2
  fi
  return "$result"
}
trap finish EXIT
publish_file() {
  local rel=$1 parent="$target/$(dirname -- "$1")" ancestor missing=()
  ancestor=$parent
  while [[ ! -d "$ancestor" ]]; do missing+=("$ancestor"); ancestor=$(dirname -- "$ancestor"); done
  mkdir -p -- "$parent"
  for ancestor in "${missing[@]}"; do chmod 755 -- "$ancestor"; done
  temp_file=$(mktemp "$parent/.mdhi-publish-XXXXXX")
  cp -- "$rel" "$temp_file"
  chmod 644 -- "$temp_file"
  mv -f -- "$temp_file" "$target/$rel"
  temp_file=
}
# Publish assets first, then HTML. Replacing each file uses a same-directory rename.
for rel in "${files[@]}"; do [[ "$rel" == *.html ]] || publish_file "$rel"; done
for rel in "${files[@]}"; do [[ "$rel" != *.html ]] || publish_file "$rel"; done
(cd -- "$target" && sha256sum -c "$snapshot/source-sha256.txt") > "$snapshot/verification.txt"
printf 'SUCCESS\n' > "$snapshot/status.txt"
echo "Deployment completed. Backup and verification: $snapshot"
echo 'Check homepage, About, Gallery, brochure download and the contact form in the browser.'
