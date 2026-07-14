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

## Stavka 8 - `/llms.txt` i AI landing kontekst

Status: implementirano u repo-u, produkcija treba deploy.

Napravljeno:

- Dodana je javna ruta `/llms.txt` koja se servira dinamicki kroz OpenCart controller, jer `.htaccess` sigurnosno blokira fizicke `.txt` datoteke osim `robots.txt`.
- `llms.txt` koristi Markdown strukturu: H1 naslov, kratki sazetak, sekcije i opisne linkove prema stabilnim resursima.
- Dodana je javna `/ai-context` stranica s ljudski citljivim pregledom AI resursa, sitemapova, feedova, kontakata i policy stranica.
- Dodana su rewrite pravila u `upload/.htaccess` za `/llms.txt` i `/ai-context`.
- Dodan je `scripts/llms_txt_audit.php` za provjeru osnovne Markdown strukture, content-typea i obaveznih resursa.

Primjeri provjere nakon deploya:

```bash
curl -I https://www.chipoteka.hr/llms.txt
curl -s https://www.chipoteka.hr/llms.txt | head -40
php scripts/llms_txt_audit.php https://www.chipoteka.hr/llms.txt
curl -I https://www.chipoteka.hr/ai-context
```

Napomena:

- `/llms.txt` nije zamjena za `robots.txt` ili `sitemap-index.xml`; to je sazet, kurirani Markdown ulaz za AI sustave koji trebaju brzo razumjeti glavne izvore i ogranicenja weba.

## Stavka 9 - FAQ i policy schema

Status: implementirano u repo-u, produkcija treba deploy.

Napravljeno:

- Info/policy stranice sada dobivaju `WebPage`, `WebSite`, `Organization/Store` i `BreadcrumbList` JSON-LD.
- `Uvjeti kupovine` (`information_id=3`) dobivaju i `MerchantReturnPolicy` s HR policy signalima i rokom od 14 dana.
- Za policy stranice dodan je vidljivi FAQ blok i odgovarajuci `FAQPage` JSON-LD:
  - `uvjeti-kupovine`: placanje, dostava, povrat, jamstvo, servis/reklamacije
  - `opci-uvjeti-koristenja`: pravila koristenja i korisnicki racun
  - `zastita-privatnosti-i-podataka`: obrada podataka i korisnicka prava
- `poslovnice`, `servis` i `veleprodaja` dobivaju `ContactPage` JSON-LD.
- `poslovnice` dobivaju `ItemList` s `ElectronicsStore` lokacijama iz OpenCart konfiguracije poslovnica.
- `servis` dobiva `Service` i vidljivi servisni FAQ s odgovarajucim `FAQPage` JSON-LD.
- Dodan je `scripts/policy_schema_audit.php` za provjeru policy, FAQ, locations i service schema blokova.

Primjeri provjere nakon deploya:

```bash
php scripts/policy_schema_audit.php https://www.chipoteka.hr/uvjeti-kupovine policy
php scripts/policy_schema_audit.php https://www.chipoteka.hr/uvjeti-kupovine faq
php scripts/policy_schema_audit.php https://www.chipoteka.hr/poslovnice locations
php scripts/policy_schema_audit.php https://www.chipoteka.hr/servis service
```

Napomena:

- FAQ schema je vezana uz vidljiv FAQ sadrzaj na stranici, kako ne bi postojao skriveni schema-only tekst.

## Stavka 10 - Monitoring i regresijske testove

Status: implementirano u repo-u, produkcija treba deploy i prvi full run nakon deploya.

Napravljeno:

- Dodan je umbrella regression runner `scripts/ai_regression_monitor.php`.
- Runner objedinjuje postojece audit skripte i HTTP smoke testove za:
  - `robots.txt`, `sitemap-index.xml`, `/openai-products.jsonl`, `/llms.txt`, `/ai-context`
  - OpenAI product feed
  - genericki merchant feed spine
  - Product JSON-LD na jednom proizvodu iz live feeda
  - category i brand JSON-LD primjere
  - policy, FAQ, poslovnice i servis schema blokove
  - uzorak product sitemap URL-ova
- Dodani su modovi `--quick`, `--include-crawler`, `--strict`, `--json` i `--dry-run`.
- Crawler/WAF audit je opcionalan i po defaultu je warning provjera, jer training/discovery bot policy moze biti poslovna odluka.
- Dodan je dokument `docs/ai-monitoring-regression.md` s post-deploy i cPanel cron uputama.

Primjeri provjere nakon deploya:

```bash
php scripts/ai_regression_monitor.php https://www.chipoteka.hr
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --quick
php scripts/ai_regression_monitor.php https://www.chipoteka.hr --include-crawler
```

Primjer cPanel cron komande:

```bash
cd /home/CPANEL_USER/path/to/chipoteka && /usr/local/bin/php scripts/ai_regression_monitor.php https://www.chipoteka.hr --quick >> /home/CPANEL_USER/logs/chipoteka-ai-regression.log 2>&1
```

## Stavka 11 - Content/attribute normalizacija

Status: implementirano u repo-u za kategorijski FAQ, produkcija treba deploy i unos FAQ sadrzaja u adminu.

Napravljeno:

- Dodan je admin editor za FAQ na kategorijama: `Catalog > Categories > Edit category`, po jeziku.
- FAQ se sprema u novu tablicu `category_faq`, koju admin model automatski kreira pri otvaranju/spremanju kategorije.
- Svaki FAQ red ima pitanje, odgovor i redoslijed sortiranja.
- Frontend dohvat je obramben: ako tablica jos ne postoji ili kategorija nema FAQ, kategorija radi normalno bez FAQ bloka.
- Kategorijski FAQ se prikazuje na dnu kategorije, ispod artikala/paginacije.
- Postojeci kategorijski opis ostaje kako je bio.
- Ako kategorija ima FAQ, isti sadrzaj se dodaje i u category JSON-LD kao `FAQPage`, uz postojece `CollectionPage`, `ItemList` i `BreadcrumbList`.
- Dodan je `scripts/category_faq_audit.php` za provjeru kategorije nakon sto se FAQ unese u adminu.

Primjer provjere nakon deploya i unosa FAQ-a na kategoriju:

```bash
php scripts/category_faq_audit.php "https://www.chipoteka.hr/<category-url>"
php scripts/collection_jsonld_audit.php "https://www.chipoteka.hr/<category-url>" category
```

Napomena:

- `category_faq_audit.php` treba pokrenuti samo za kategorije na kojima je FAQ stvarno unesen.

## Stavka 12 - IndexNow / brze dojave promjena

Status: implementirano u repo-u, produkcija treba deploy, prvu provjeru key filea i cPanel cron za submit queuea.

Napravljeno:

- Dodan je javni IndexNow key file: `/ea81e5ea0e88a48ec19291ea3e853459.txt`.
- Dodana je `indexnow_queue` tablica, automatski kreirana kod prvog queueanja ili pokretanja submit skripte.
- Product add/edit/delete queuea product URL, pripadajuce category URL-ove i brand URL.
- Category add/edit/delete queuea category URL.
- Manufacturer add/edit/delete queuea brand URL.
- Information add/edit/delete queuea information URL.
- Vanjski IndexNow API se ne zove direktno iz admin save akcije; queue se salje kroz CLI/cron kako admin i Luceed sync ne bi ovisili o vanjskoj mrezi.
- Dodane su skripte `scripts/indexnow_audit.php` i `scripts/indexnow_submit.php`.
- Dodan je dokument `docs/indexnow.md` s post-deploy i cPanel cron uputama.

Primjeri provjere nakon deploya:

```bash
php scripts/indexnow_audit.php https://www.chipoteka.hr
php scripts/indexnow_submit.php https://www.chipoteka.hr --dry-run
```

Primjer cPanel cron komande:

```bash
cd /home/CPANEL_USER/path/to/chipoteka && /usr/local/bin/php scripts/indexnow_submit.php https://www.chipoteka.hr --limit=1000 >> /home/CPANEL_USER/logs/chipoteka-indexnow.log 2>&1
```

## Sljedeca stavka

Paket od 12 dogovorenih stavki je implementiran u repo-u. Nakon deploya ostaju produkcijske provjere, unos FAQ sadrzaja i zavrsni onboarding za OpenAI merchant/SFTP kada dobijemo pristupne podatke.
