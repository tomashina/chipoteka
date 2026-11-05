#!/usr/bin/env python3
"""Reconcile already deployed files with Git's index; never write site files."""
import argparse
import hashlib
import os
import stat
import subprocess
import sys

BRANCH = 'codex/pickup-main-plushosting-sync-deploy'
PROTECTED_FILES = {
    'upload/.htaccess', 'upload/config.php', 'upload/admin/config.php',
    'upload/config-test.php', 'upload/env.php', 'upload/.env', 'upload/php.ini',
    'upload/.user.ini', 'upload/admin/php.ini', 'upload/admin/.user.ini',
    'upload/robots.txt', '.cpanel.yml',
}
PROTECTED_DIRS = (
    '.git/', 'storage/backup/', 'storage/cache/', 'storage/download/',
    'storage/logs/', 'storage/marketplace/', 'storage/modification/',
    'storage/session/', 'storage/upload/', 'storage/orders_logs/',
    'upload/system/storage/', 'upload/storage/', 'upload/assets/',
    'upload/image/cache/', 'upload/image/cachewebp/', 'upload/image/catalog/',
    'upload/image/filemanager/', 'upload/image/tmp/',
)
DIGEST_FILES = (
    'upload/.htaccess', 'upload/config.php', 'upload/admin/config.php',
    'upload/env.php', 'storage/upload/assets/placanja.json',
    'storage/upload/assets/skladista.json',
)


def protected(path):
    return path in PROTECTED_FILES or path.startswith(PROTECTED_DIRS)


def run_git(repo, *args, **kwargs):
    env = os.environ.copy()
    env['GIT_OPTIONAL_LOCKS'] = '0'
    result = subprocess.run(['git', '-C', repo] + list(args),
                            input=kwargs.get('data'), stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, env=env)
    if result.returncode:
        raise RuntimeError('Git command failed: ' +
                           result.stderr.decode('utf-8', 'replace').strip())
    return result.stdout


def tree(repo, revision):
    result = {}
    for entry in run_git(repo, 'ls-tree', '-rz', '--full-tree', revision).split(b'\0'):
        if entry:
            info, path = entry.split(b'\t', 1)
            mode, kind, oid = info.split(b' ')
            result[os.fsdecode(path)] = (mode, oid)
    return result


def index(repo):
    result = {}
    for entry in run_git(repo, 'ls-files', '--stage', '-z').split(b'\0'):
        if entry:
            info, path = entry.split(b'\t', 1)
            mode, oid, stage = info.split(b' ')
            if stage != b'0':
                raise RuntimeError('Unmerged index entry; resolve it first')
            result[os.fsdecode(path)] = (mode, oid)
    return result


def physical_blob(repo, path):
    full = os.path.join(repo, path)
    parent = os.path.dirname(path)
    while parent:
        if os.path.islink(os.path.join(repo, parent)):
            raise RuntimeError('Linked parent directory: ' + path)
        parent = os.path.dirname(parent)
    if not os.path.lexists(full):
        return None
    metadata = os.lstat(full)
    if stat.S_ISLNK(metadata.st_mode):
        content = os.fsencode(os.readlink(full))
        digest = hashlib.sha1(b'blob ' + str(len(content)).encode('ascii') + b'\0' + content)
        return (b'120000', digest.hexdigest().encode('ascii'))
    if not stat.S_ISREG(metadata.st_mode):
        raise RuntimeError('Expected regular file or symlink: ' + path)
    digest = hashlib.sha1(b'blob ' + str(metadata.st_size).encode('ascii') + b'\0')
    with open(full, 'rb') as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b''):
            digest.update(chunk)
    return (b'100644', digest.hexdigest().encode('ascii'))


def same_content(physical, entry):
    if physical is None or entry is None:
        return physical is entry
    return physical[1] == entry[1] and (physical[0] == b'120000') == (entry[0] == b'120000')


def config_digest(repo):
    digest = hashlib.sha256()
    for path in DIGEST_FILES:
        digest.update(os.fsencode(path) + b'\0')
        value = physical_blob(repo, path)
        digest.update(b'missing' if value is None else b' '.join(value))
        digest.update(b'\0')
    return digest.hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--repo', default='.')
    parser.add_argument('--target', required=True, help='Fetched fast-forward commit or ref')
    parser.add_argument('--branch', default=BRANCH, help='Expected current branch')
    parser.add_argument('--apply', action='store_true', help='Update index metadata after validation')
    args = parser.parse_args()
    repo = os.path.realpath(args.repo)
    if os.path.realpath(os.fsdecode(run_git(repo, 'rev-parse', '--show-toplevel')).strip()) != repo:
        raise RuntimeError('--repo must name the repository root')
    branch = os.fsdecode(run_git(repo, 'symbolic-ref', '--quiet', '--short', 'HEAD')).strip()
    if branch != args.branch:
        raise RuntimeError('Unexpected branch: ' + branch)
    head = run_git(repo, 'rev-parse', '--verify', 'HEAD^{commit}').strip()
    target = run_git(repo, 'rev-parse', '--verify', args.target + '^{commit}').strip()
    run_git(repo, 'merge-base', '--is-ancestor', head.decode('ascii'), target.decode('ascii'))
    old = tree(repo, head.decode('ascii'))
    new = tree(repo, target.decode('ascii'))
    staged = index(repo)
    changed = sorted(path for path in set(old) | set(new) if old.get(path) != new.get(path))
    for path in sorted(set(old) | set(staged)):
        if old.get(path) != staged.get(path):
            if protected(path) or path not in changed or staged.get(path) != new.get(path):
                raise RuntimeError('Unexpected pre-existing staged change: ' + path)
    before_digest = config_digest(repo)
    signatures = {}
    updates = []
    pending = 0
    skipped = 0
    for path in changed:
        if protected(path):
            skipped += 1
            continue
        expected = new.get(path)
        if expected is not None and expected[0] not in (b'100644', b'100755', b'120000'):
            raise RuntimeError('Unsupported incoming file type: ' + path)
        current = physical_blob(repo, path)
        signatures[path] = current
        if same_content(current, expected):
            if staged.get(path) != expected:
                updates.append((path, expected))
        elif same_content(current, old.get(path)):
            pending += 1
        elif current is None and old.get(path) is None:
            pending += 1
        else:
            raise RuntimeError('Local code differs from both HEAD and target: ' + path)
    print('HEAD: ' + head.decode('ascii'))
    print('Target: ' + target.decode('ascii'))
    print('Index updates: {}; normal pull paths: {}; protected skipped: {}'.format(len(updates), pending, skipped))
    print('Protected configuration digest: ' + before_digest)
    for path, entry in updates:
        print(('Stage deletion: ' if entry is None else 'Stage matching target: ') + path)
    if not args.apply:
        print('Report only; index and working files unchanged.')
        return
    if run_git(repo, 'rev-parse', 'HEAD').strip() != head or index(repo) != staged:
        raise RuntimeError('HEAD or index changed during validation; retry')
    if config_digest(repo) != before_digest:
        raise RuntimeError('Protected configuration changed during validation; retry')
    for path, signature in signatures.items():
        if physical_blob(repo, path) != signature:
            raise RuntimeError('Working file changed during validation: ' + path)
    payload = b''
    for path, entry in updates:
        mode, oid = (b'0', b'0' * 40) if entry is None else entry
        payload += mode + b' ' + oid + b'\t' + os.fsencode(path) + b'\0'
    if payload:
        run_git(repo, 'update-index', '-z', '--index-info', data=payload)
    if config_digest(repo) != before_digest:
        raise RuntimeError('Protected configuration changed concurrently; inspect before pulling')
    for path, signature in signatures.items():
        if physical_blob(repo, path) != signature:
            raise RuntimeError('Working file changed concurrently: ' + path)
    if run_git(repo, 'rev-parse', 'HEAD').strip() != head:
        raise RuntimeError('HEAD changed concurrently; inspect before pulling')
    print('Index reconciled. HEAD and all checked working-file bytes unchanged.')
    print('Now use Update from Remote in the existing cPanel repository.')


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, OSError) as error:
        print('ERROR: ' + str(error), file=sys.stderr)
        sys.exit(1)
