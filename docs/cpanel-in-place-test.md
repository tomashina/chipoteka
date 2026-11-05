# Updating the existing cPanel test site

Keep the existing cPanel repository `chipoteka-test`, its path
`/home/amds/chipoteka.agmedia.rocks`, the `chipoteka.agmedia.rocks` subdomain and
the `codex/pickup-main-plushosting-sync-deploy` branch. No separate checkout or
deployment copy is needed. In **Git Version Control → Manage → Pull or Deploy**,
**Update from Remote** fast-forwards this checkout and updates its code directly.

The test site has local settings and generated data that intentionally differ
from Git: `upload/.htaccess`, `upload/config.php`, `upload/admin/config.php`,
`upload/env.php`, `storage/upload/assets/placanja.json`,
`storage/upload/assets/skladista.json`, and generated image caches. Preserve
these local changes; do not reset, clean, stash or overwrite the running site's
files to make an update succeed. Never commit server credentials.

On this test branch, the tracked `upload/.htaccess` and
`storage/upload/assets/skladista.json` must retain their baseline blobs from
`fe578203c350833d0820a27fc7c96da678f9c1d2`. The site's current local copies remain
untouched when the cumulative incoming diff does not change those paths.
Production redirects/PHP rules and generated warehouse JSON must not be merged
into these two tracked files on this branch. `.gitignore` does not protect them
because they are already tracked. Review future merges against this baseline.

For each change, review and test locally, commit and push to the same branch,
then use **Update from Remote** in the existing cPanel repository. Check the
resulting HEAD and site response; confirm that settings and generated data stay
unchanged. A separate **Deploy HEAD Commit** action is not part of this setup.
If a future update overlaps another locally modified path, resolve it through
the reviewed Git workflow before updating the server.

## One-time recovery after code was already deployed

If a previous Git-controlled deployment installed the incoming code while this
checkout's HEAD/index stayed old, even identical incoming files can block a
pull. Fetch the reviewed commit, then run the recovery script from that Git
object; it does not need to be copied into the live source tree:

```sh
cd /home/amds/chipoteka.agmedia.rocks
git fetch origin codex/pickup-main-plushosting-sync-deploy
git show origin/codex/pickup-main-plushosting-sync-deploy:scripts/reconcile-cpanel-test-index.py | python3 - --repo "$PWD" --target origin/codex/pickup-main-plushosting-sync-deploy
```

Review the report, then repeat with `--apply`. The script updates only index
metadata for nonprotected files whose bytes already match the fetched target.
It leaves HEAD and site files unchanged, preserves local configuration/runtime
files and rejects unknown source edits or unrelated staged changes. It requires
the same branch and a fast-forward target. Missing new files and unchanged old
files are left for the normal pull. Finally use the existing cPanel repository's
**Update from Remote** and verify HEAD, site response and protected settings.
