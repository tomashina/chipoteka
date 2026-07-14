# AI monitoring i regresijski testovi

Ovaj dokument opisuje kako provjeravati AI-friendly sloj nakon deploya i kako ga pokrenuti periodicki kroz cron/cPanel.

## Glavna komanda

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr
```

Skripta provjerava:

- osnovne HTTP endpointove: homepage, `robots.txt`, `sitemap-index.xml`, `/openai-products.jsonl`, `/llms.txt`, `/ai-context`
- OpenAI product feed strukturu
- genericki merchant feed spine
- Product JSON-LD na jednom proizvodu iz live product feeda
- category i brand JSON-LD na poznatim primjerima
- policy, FAQ, poslovnice i servis schema blokove
- uzorak product sitemap URL-ova i image URL-ova

Ako bilo koja kriticna provjera padne, skripta zavrsava s exit code `1`.

## Brza provjera

Za brzi smoke test bez uzorka sitemap URL-ova:

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --quick
```

## Crawler/WAF provjera

Crawler/WAF audit je odvojen jer Cloudflare pravila mogu imati poslovno namjerne blokade za training botove.

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --include-crawler
```

U ovom modu crawler audit je upozorenje. Za strogi mod u kojem i crawler upozorenje rusi build/cron:

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --include-crawler --strict
```

## JSON output

Za integraciju s drugim alatima:

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --json
```

## cPanel cron primjer

Putanje ovise o serveru, ali oblik komande je:

```bash
cd /home/CPANEL_USER/path/to/chipoteka && /usr/local/bin/php scripts/ai_regression_monitor.php https://www.chipoteka.hr --quick >> /home/CPANEL_USER/logs/chipoteka-ai-regression.log 2>&1
```

Preporuka:

- nakon svakog deploya: puni monitor bez `--quick`
- dnevno kroz cron: `--quick`
- tjedno ili nakon Cloudflare promjena: `--include-crawler`

## Sto napraviti ako test padne

- `/llms.txt` vraca 404 ili HTML: provjeriti `.htaccess` rewrite i sigurnosni `FilesMatch` iznimku za `llms.txt`.
- `openai-products.jsonl` pada: provjeriti feed rutu, content-type, PHP error log i zadnji deploy.
- Product/category/brand schema pada: provjeriti JSON-LD blok u izvoru stranice i je li deployana zadnja verzija controller/template izmjena.
- Policy/FAQ schema pada: provjeriti jesu li deployane stavke 9 controller, language i template promjene.
- Sitemap URL audit pada: otvoriti prijavljeni URL i regenerirati sitemap ako pokazuje stare ili 404 image URL-ove.
- Crawler audit pada samo za training botove (`GPTBot`, `ClaudeBot`): to moze biti prihvatljiva policy odluka. Ako pada `ChatGPT-User`, `Claude-SearchBot`, `PerplexityBot`, `Googlebot` ili `Bingbot`, treba provjeriti Cloudflare/WAF.
