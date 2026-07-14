# AI-friendly implementacija - status

Datum: 2026-07-14

## Stavka 1 - AI crawler i Cloudflare/WAF audit

Status: djelomicno implementirano u repo-u, produkcijska WAF promjena jos potrebna za `OAI-SearchBot`.

Napravljeno:

- Dodana audit skripta `scripts/ai_crawler_audit.php`.
- Dodana Cloudflare/WAF checklist dokumenacija `docs/ai-crawler-waf-checklist.md`.
- Audit skripta razlikuje discovery botove koje zelimo pustiti od training botova koji su poslovna odluka.

Zadnja live provjera:

- `Browser`, `Googlebot` i `Bingbot` vracaju HTTP 200.
- `ChatGPT-User`, `Claude-SearchBot`, `Claude-User`, `PerplexityBot` i `Perplexity-User` vracaju HTTP 200 na homepageu i `sitemap-index.xml`.
- `OAI-SearchBot` jos vraca HTTP 403 na homepageu i `sitemap-index.xml`.
- `GPTBot` i `ClaudeBot` mogu ostati blokirani kao training botovi, ako je to poslovna odluka.

Sljedece izvan repo-a:

- U Cloudflare/WAF-u rijesiti `OAI-SearchBot`, najvjerojatnije kroz Bot Fight Mode / Cloudflare AI Crawl Control ogranicenja.
- Nakon toga ponovno pokrenuti:

```bash
php scripts/ai_crawler_audit.php https://www.chipoteka.hr
```

## Stavka 2 - Robots.txt i sitemap uskladenje

Status: implementirano u repo-u i potvrdeno na produkciji nakon deploya/regeneracije.

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

Provjera nakon deploya:

- `robots.txt` sadrzi AI discovery botove i pokazuje na `https://www.chipoteka.hr/sitemap-index.xml`.
- `sitemap-index.xml` ima svjeze `lastmod` datume.
- Uzorak product sitemap URL-ova i image sitemap URL-ova vraca HTTP 200.

Primjer provjere:

```bash
php scripts/sitemap_url_audit.php https://www.chipoteka.hr/sitemaps/sitemap_0_2_product_12.xml 50
```

## Stavka 3 - Product JSON-LD refaktor

Status: implementirano u repo-u, produkcija treba deploy.

Napravljeno:

- Rucni Product JSON-LD u Twig templateu zamijenjen je jednim `product_json_ld` blokom generiranim u PHP controlleru preko `json_encode`.
- JSON-LD sada koristi jedan canonical URL za `Product.url`, `mainEntityOfPage`, `Offer.url`, canonical link i OG URL.
- Product schema sada sadrzi `@id`, `name`, `url`, `description`, `sku`, `model`, `brand`, slike, GTIN gdje je dostupan, `Offer`, `AggregateRating` kada postoje reviewi i `additionalProperty` iz product atributa.
- Availability se vise ne postavlja uvijek na `InStock`; mapira se iz Luceed-sync polja `quantity`, `quantityws` i `stock_status`.
- Dodan je `BreadcrumbList` u istom `@graph` JSON-LD bloku.
- Dodan je `scripts/product_jsonld_audit.php` za provjeru live product JSON-LD blokova.

Primjer provjere nakon deploya:

```bash
php scripts/product_jsonld_audit.php https://www.chipoteka.hr/napajanje-750w-asus-tuf-gaming-750b-90ye00d0-b0na00-80-bronze-120mm-fan-9150055067
```

Napomena:

- Live stranica prije deploya jos pokazuje staru shemu; audit trenutno javlja npr. `missing url`, sto treba nestati nakon deploya nove verzije.

## Stavka 4 - Category/brand structured data

Status: implementirano u repo-u, produkcija treba deploy.

Napravljeno:

- Category stranice dobivaju JSON-LD `CollectionPage`, kategorijski `Thing`, `ItemList` i `BreadcrumbList`.
- Manufacturer/brand stranice dobivaju JSON-LD `CollectionPage`, `Brand`, `ItemList` i `BreadcrumbList`.
- `ItemList` koristi proizvode s trenutne paginacije i ukupni broj proizvoda iz postojeceg queryja, bez dodatnih teskih upita.
- Product list itemi unutar category/brand schema bloka sadrze `Product`, canonical product URL, sliku, SKU/model, brand, GTIN/MPN gdje je dostupan i `Offer`.
- Availability u listama koristi `quantity`, Luceed/dobavljacki `quantityws` i `stock_status`, isto kao Product JSON-LD.
- Dodan je `scripts/collection_jsonld_audit.php` za provjeru category i brand JSON-LD blokova.

Primjeri provjere nakon deploya:

```bash
php scripts/collection_jsonld_audit.php "https://www.chipoteka.hr/<category-url>" category
php scripts/collection_jsonld_audit.php "https://www.chipoteka.hr/<brand-url>" brand
```

## Stavka 5 - AI product feed prema OpenAI/ChatGPT product discovery shemi

Status: implementirano u repo-u i potvrdeno na produkciji.

Napravljeno:

- Dodan je javni JSONL snapshot feed na `/openai-products.jsonl`.
- Feed koristi OpenAI stable file-upload product schema polja za non-Ads discovery feed.
- Svaki zapis sadrzi required discovery polja: `is_eligible_search`, `is_eligible_checkout`, `item_id`, `title`, `description`, `url`, `brand`, `image_url`, `price`, `availability`, `seller_name`, `seller_url`, `return_policy`, `target_countries` i `store_country`.
- `is_eligible_checkout` je postavljen na `false`, jer direktni ChatGPT checkout nije dio ove stavke.
- Cijene se salju kao EUR s porezom. Regularna cijena ide u `price`, a aktivni special u `sale_price`.
- Availability koristi `quantity`, Luceed/dobavljacki `quantityws` i `stock_status`.
- Dodani su optional signali gdje postoje: GTIN, MPN, category path, dodatne slike, cijena zadnjih 30 dana, tezina/dimenzije, review count/rating i osnovni shipping.
- `offer_id` koristi stabilni interni `product_id`, a `mpn` se filtrira kako se ne bi slali tekstovi tipa garancija/dostava.
- Dodan je `scripts/openai_product_feed_audit.php` za provjeru JSONL feeda.

Primjer provjere nakon deploya:

```bash
php scripts/openai_product_feed_audit.php "https://www.chipoteka.hr/openai-products.jsonl?limit=50" 50
```

Napomena:

- OpenAI dokumentacija navodi da je product feed onboarding u ChatGPT trenutno dostupan approved partnerima. Ovaj feed priprema katalog za prijavu i buducu isporuku kroz OpenAI podrzane kanale.

## Stavka 6 - OpenAI/ChatGPT merchant priprema

Status: implementirano u repo-u.

Napravljeno:

- Dodan je merchant pripremni dokument `docs/openai-chatgpt-merchant-prep.md`.
- Dokument sadrzi copy/paste podatke za `https://chatgpt.com/merchants/`, preporucenu primarnu kategoriju `Electronics & Appliances`, feed opis, delivery napomenu i tekst za polje "Anything else".
- Dodana je mapping tablica prema OpenAI stable product feed shemi.
- Dodana je gap analiza: trenutni HTTPS JSONL feed je spreman za QA/prijavu, a nakon OpenAI odobrenja treba sloziti stabilni `jsonl.gz` full snapshot i SFTP upload job.
- Dodan je `scripts/openai_merchant_readiness.php` za sazetak feed coveragea, OpenAI feed size bucketa, optional field coveragea i merchant copy/paste podataka.

Primjeri provjere:

```bash
php scripts/openai_product_feed_audit.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100" 100
php scripts/openai_merchant_readiness.php "https://www.chipoteka.hr/openai-products.jsonl?limit=1000"
```

Napomena:

- OpenAI docs navode da se produkcijski file-upload feed dostavlja kao full snapshot kroz podrzane kanale poput SFTP-a, uz stabilan file name i barem dnevni refresh. To je sljedeci korak tek nakon odobrenja merchant pristupa.

## Stavka 7 - Opcionalna genericka feed kompatibilnost za buduce Google/druga trzista

Status: implementirano u repo-u.

Napravljeno:

- Dodan je dokument `docs/generic-feed-compatibility.md`.
- Dodan je alat `scripts/generic_merchant_feed.php`.
- Alat koristi postojeci OpenAI JSONL feed kao source of truth i mapira ga u Google-like TSV spine: `id`, `title`, `description`, `link`, `image_link`, `availability`, `price`, `brand`, `gtin`, `mpn`, `condition`, `product_type`, `shipping`.
- Skripta moze raditi samo audit ili audit + TSV export.
- Dokumentirana je napomena da ovo nije live Google HR launch: sluzbeni Google Merchant Center popis podrzanih jezika/zemalja trenutno ne navodi Croatian/Hrvatsku za Shopping ads/free listings.

Primjeri provjere:

```bash
php scripts/generic_merchant_feed.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100"
php scripts/generic_merchant_feed.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100" /tmp/chipoteka-generic-products.tsv
```

Zadnja live provjera:

- 100/100 proizvoda iz uzorka ima required generic merchant spine.
- `availability`, `price`, `brand`, `product_type`, `shipping` su prisutni na uzorku.
- GTIN coverage na uzorku: 85/100.

## Sljedeca stavka

Stavka 8: `/llms.txt` i AI landing kontekst.
