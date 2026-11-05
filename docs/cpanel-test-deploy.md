# cPanel test deployment

The test site is `/home/amds/chipoteka.agmedia.rocks`; its webroot is the
`upload` subdirectory. Its checkout contains local settings and runtime files,
so it must not serve as the cPanel source repository for future updates.

## Setup and normal updates

1. Commit, review, test and push deployment changes to the existing
   `codex/pickup-main-plushosting-sync-deploy` branch on GitHub.
2. In cPanel Git Version Control, create a separate clean clone at
   `/home/amds/repositories/chipoteka-cpanel`, using the existing GitHub remote.
   Select `codex/pickup-main-plushosting-sync-deploy` as its checked-out branch.
3. On this new repository, **Update from Remote** pulls the latest code without
   touching the running site's settings or data.
4. Review a deployment preview from cPanel Terminal:

   ```sh
   cd /home/amds/repositories/chipoteka-cpanel
   bash scripts/deploy-cpanel-test.sh --dry-run
   ```

5. In that repository's **Pull or Deploy** tab, **Deploy HEAD Commit** runs the
   checked-in `.cpanel.yml` and deployment script. Confirm its success and the
   test site's response. If an application change needs OpenCart modification
   refresh or database migrations, review and perform that change separately;
   this script does not run migrations, crons, refresh requests or send mail.

Keep all source edits in the local development repository and push through Git.
The separate cPanel source checkout must stay clean. Do not reset or clean the
old checkout at the site path: it retains site settings and runtime data.

## What deployment preserves

The script copies source files using `rsync --checksum`, without `--delete`.
It preserves the existing site's `.git`, `.htaccess`, frontend/admin config,
`env.php`, `.env`, `config-test.php`, PHP settings and `robots.txt`. In particular,
the test host's redirects and cPanel PHP handler are not replaced by the
production rules in the repository. The tracked `config-test.php` is excluded
because its content is not a PHP program and must not be newly published.

Runtime storage directories, uploaded JSON data, image caches, image catalog,
filemanager originals and temporary images are excluded. Code under
`storage/vendor` is deployed, including the Luceed integration. Existing files
that were deleted upstream remain on the target; review any necessary removals
as a separate Git-controlled deployment change.

No backup is written inside the public tree. Use the hosting provider's existing
backup/snapshot facility before a deployment that requires rollback coverage.
Git history records code versions; it does not back up local settings, databases
or uploaded data.

## Local verification

Use an existing, disposable fixture target with `upload/config.php`,
`upload/admin/config.php`, `upload/env.php`, `upload/.htaccess` and a `storage`
directory. The source repository must be separate from the target.

```sh
bash scripts/deploy-cpanel-test.sh --dry-run --target /absolute/path/to/fixture
bash scripts/deploy-cpanel-test.sh --target /absolute/path/to/fixture
```

Verify that the preview changes no bytes; a real fixture deployment updates
application/vendor code while preserving target configuration, runtime files,
the old `.git`, and target-only files. Source and target overlap, missing site
configuration, and linked top-level destination directories cause an error.
