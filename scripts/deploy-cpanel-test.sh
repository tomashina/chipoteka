#!/bin/bash
# Deploy the separate cPanel checkout without replacing this site's settings/data.
set -euo pipefail

target=/home/amds/chipoteka.agmedia.rocks
dry_run=0
while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) dry_run=1; shift ;;
        --target)
            [[ $# -ge 2 && -n "$2" ]] || { echo "Missing --target directory" >&2; exit 1; }
            target=$2; shift 2 ;;
        *) echo "Usage: $0 [--dry-run] [--target EXISTING_DIRECTORY]" >&2; exit 1 ;;
    esac
done

source_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)
[[ -d "$target" && ! -L "$target" ]] || { echo "Target must be an existing directory, not a symbolic link" >&2; exit 1; }
target_root=$(cd "$target" && pwd -P)
case "$target_root/" in
    "$source_root/"|"$source_root/"*) echo "Source and target must be separate directories" >&2; exit 1 ;;
esac
case "$source_root/" in
    "$target_root/"*) echo "Source cannot be inside the target" >&2; exit 1 ;;
esac
for directory in upload upload/admin storage; do
    [[ -d "$target_root/$directory" && ! -L "$target_root/$directory" ]] || {
        echo "Target $directory must be an existing directory, not a symbolic link" >&2; exit 1;
    }
done
for config in upload/config.php upload/admin/config.php upload/env.php upload/.htaccess; do
    [[ -f "$target_root/$config" && ! -L "$target_root/$config" ]] || {
        echo "Missing regular site configuration: $config" >&2; exit 1;
    }
done
[[ -f "$source_root/upload/index.php" && -d "$source_root/storage/vendor" ]] || {
    echo "Source is not the expected OpenCart repository" >&2; exit 1;
}
command -v rsync >/dev/null || { echo "rsync is required" >&2; exit 1; }

# Excluded paths are never replaced. No --delete: existing site-only files stay.
# --checksum avoids relying on checkout timestamps when deciding what changed.
options=(
    -a --checksum --safe-links --no-owner --no-group --no-perms --itemize-changes
    --exclude=/.git
    --exclude=/.cpanel.yml
    --exclude=/upload/.htaccess
    --exclude=/upload/config.php
    --exclude=/upload/admin/config.php
    --exclude=/upload/config-test.php
    --exclude=/upload/env.php
    --exclude=/upload/.env
    --exclude=/upload/php.ini
    --exclude=/upload/.user.ini
    --exclude=/upload/admin/php.ini
    --exclude=/upload/admin/.user.ini
    --exclude=/upload/robots.txt
    --exclude=/storage/backup/
    --exclude=/storage/cache/
    --exclude=/storage/download/
    --exclude=/storage/logs/
    --exclude=/storage/marketplace/
    --exclude=/storage/modification/
    --exclude=/storage/session/
    --exclude=/storage/upload/
    --exclude=/storage/orders_logs/
    --exclude=/upload/system/storage/
    --exclude=/upload/storage/
    --exclude=/upload/assets/
    --exclude=/upload/image/cache/
    --exclude=/upload/image/cachewebp/
    --exclude=/upload/image/catalog/
    --exclude=/upload/image/filemanager/
    --exclude=/upload/image/tmp/
)
if [[ "$dry_run" -eq 1 ]]; then
    options+=(--dry-run)
fi
echo "Deploying tracked checkout to $target_root (dry-run=$dry_run)"
rsync "${options[@]}" "$source_root/" "$target_root/"
