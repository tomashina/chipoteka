# AI-friendly implementacija - status

Datum: 2026-07-14

## Stavka 1 - AI crawler i Cloudflare/WAF audit

Status: djelomicno implementirano u repo-u, produkcijska WAF promjena jos potrebna.

Napravljeno:

- Dodana audit skripta `scripts/ai_crawler_audit.php`.
- Dodana Cloudflare/WAF checklist dokumenacija `docs/ai-crawler-waf-checklist.md`.
- Audit skripta razlikuje discovery botove koje zelimo pustiti od training botova koji su poslovna odluka.

Zadnja live provjera:

- `Browser`, `Googlebot` i `Bingbot` vracaju HTTP 200.
- `OAI-SearchBot`, `ChatGPT-User`, `Claude-SearchBot`, `Claude-User`, `PerplexityBot` i `Perplexity-User` vracaju HTTP 403 na homepageu i `sitemap-index.xml`.
- `robots.txt` je dostupan tim botovima, ali to nije dovoljno ako ih WAF blokira na stvarnim URL-ovima.

Sljedece izvan repo-a:

- U Cloudflare/WAF-u dozvoliti korisne AI discovery botove.
- Nakon toga ponovno pokrenuti:

```bash
php scripts/ai_crawler_audit.php https://www.chipoteka.hr
```

## Stavka 2 - Robots.txt i sitemap uskladenje

Status: implementirano u repo-u, produkcija treba deploy i regeneraciju sitemapova.

Napravljeno:

- `upload/robots.txt` sada pokazuje na `https://www.chipoteka.hr/sitemap-index.xml`.
- U isti robots policy dodani su discovery botovi: `OAI-SearchBot`, `ChatGPT-User`, `Claude-SearchBot`, `Claude-User`, `PerplexityBot`, `Perplexity-User`, `Googlebot`, `Bingbot`.
- `boost_sitemap` index ruta sada koristi XML content-type s charsetom, cache header i XML escaping za sitemap `<loc>`.
- Sitemap generator prije novog generiranja brise stare sitemap XML datoteke za odabrane tipove, kako stari chunkovi ne bi ostali u indeksu.
- Product sitemap model sada ukljucuje samo proizvode s `status = 1` i `date_available <= NOW()`.
- Ispravljen bug u image sitemap dimenzijama gdje se konfigurirana visina upisivala u `$width`.
- Image sitemap sada koristi provjerene originalne `/image/catalog/...` slike umjesto krhkih `/image/cache/...` URL-ova. Ako izvorna slika ne postoji, `<image:image>` se preskace, ali product URL ostaje u sitemapu.
- Dodan je `scripts/sitemap_url_audit.php` za provjeru page i image URL-ova unutar pojedinog sitemap XML-a.
- Uklonjen PHP deprecation u `getCategories` potpisu.

Live primjer koji je potvrdio problem:

- `https://www.chipoteka.hr/sitemaps/sitemap_0_2_product_12.xml` sadrzi staru cache sliku `...T464OTP7A-1155x500.jpg`.
- Ta slika vraca HTTP 404.
- Isti product live stranica sada koristi nove slike `...UPBJQM5LF...` i `...SAHXN7UJZ...`, sto potvrduje da je sitemap chunk zastario.

Sljedece nakon deploya:

- U adminu pokrenuti Boost Sitemap generate za product, category, category_product, manufacturer, manufacturer_product i information.
- Provjeriti da `sitemap-index.xml` ima svjeze `lastmod` datume.
- Provjeriti da uzorak product sitemap URL-ova i image sitemap URL-ova vise ne vraca 404.

Primjer provjere:

```bash
php scripts/sitemap_url_audit.php https://www.chipoteka.hr/sitemaps/sitemap_0_2_product_12.xml 50
```

## Sljedeca stavka

Stavka 3: Product JSON-LD refaktor.
