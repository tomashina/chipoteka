# IndexNow / brze dojave promjena

Ova implementacija koristi queue + cron pristup:

- admin/Luceed promjene proizvoda, kategorija, brendova i informativnih stranica upisuju URL u `indexnow_queue`
- cPanel cron pokrece submit skriptu i salje batch prema IndexNow API-ju
- javni key file je u web rootu: `/ea81e5ea0e88a48ec19291ea3e853459.txt`

## Post-deploy provjera

```bash
php scripts/indexnow_audit.php https://www.chipoteka.hr
php scripts/indexnow_submit.php https://www.chipoteka.hr --dry-run
```

`--dry-run` ne salje nista prema IndexNow, nego ispisuje payload koji bi bio poslan.

## cPanel cron

Primjer za svakih 10 minuta:

```bash
cd /home/CPANEL_USER/path/to/chipoteka && /usr/local/bin/php scripts/indexnow_submit.php https://www.chipoteka.hr --limit=1000 >> /home/CPANEL_USER/logs/chipoteka-indexnow.log 2>&1
```

Ako treba ponovno probati URL-ove koji su pali nakon vise pokusaja:

```bash
php scripts/indexnow_submit.php https://www.chipoteka.hr --retry-failed --limit=1000
```

## Sto se queuea

- product add/edit/delete: product URL, pripadajuce kategorije i brand stranica
- category add/edit/delete: category URL
- manufacturer add/edit/delete: brand URL
- information add/edit/delete: information URL

Vanjski IndexNow API se ne zove direktno iz admin save akcije, kako spremanje proizvoda/kategorije ne bi ovisilo o mrezi ili dostupnosti API-ja.
