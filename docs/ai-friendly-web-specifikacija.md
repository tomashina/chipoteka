# Chipoteka.hr - prijedlog poboljsanja za AI-friendly katalog

Datum: 2026-07-03  
Satnica: 25 EUR/h

## Kontekst

Klijent je primijetio da korisnici sve vise koriste ChatGPT, Claude, Gemini, Grok, Perplexity i slicne AI modele za pronalazak i usporedbu proizvoda. Cilj nije samo klasicni SEO, nego da web bude sto citljiviji za trazilice, AI answer engine sustave i buduce product discovery feedove.

AI-friendly web se u praksi sastoji od cetiri sloja:

1. crawleri moraju smjeti doci do stranica i dobiti HTTP 200 bez WAF/CDN blokade;
2. proizvodi, kategorije, cijene, dostupnost i brandovi moraju biti strojno citljivi kroz validan schema.org JSON-LD i feedove;
3. sitemapovi, canonical linkovi i robots pravila moraju biti uskladeni i svjezi;
4. sadrzaj mora biti dovoljno konkretan da AI moze odgovoriti na upite tipa "koji Arduino starter kit kupiti", "ima li kabel u poslovnici", "koja je razlika izmedu ova dva modela".

## Trenutno stanje iz analize projekta

Projekt je OpenCart/PHP web shop s custom katalogom i Luceed integracijama. Katalog vec sadrzi dosta korisnih podataka: naziv, model/SKU, brand, EAN/UPC, cijene, akcijske cijene, zalihe, opise, specifikacije, slike, EPREL/energetske podatke i kategorije.

Pozitivno:

- Product stranice vec imaju canonical URL i dio OpenGraph meta podataka.
- Product template vec generira osnovni `Product` JSON-LD blok.
- Postoje Google Base/Merchant i drugi product feedovi koji se mogu iskoristiti kao baza za novi AI feed.
- Postoji sitemap infrastruktura i image sitemap podaci.
- Googlebot test na produkciji vraca HTTP 200.

Problemi / prilike:

- Produkcijski test s AI crawler user-agentima `GPTBot`, `OAI-SearchBot`, `ChatGPT-User` i `ClaudeBot` vraca Cloudflare HTTP 403 na naslovnici. To moze direktno sprijeciti AI modele da citaju ponudu.
- Produkcija ima `/llms.txt` kao 404, dakle nema kuriranog AI discovery dokumenta.
- Produkcijski `sitemap-index.xml` i sitemap datoteke imaju `lastmod` vrijednosti iz 2023., iako se katalog mijenja. To je los signal svjezine.
- `upload/robots.txt` u repozitoriju pokazuje na `sitemap.xml`, dok produkcija pokazuje na `sitemap-index.xml`. Treba uskladiti izvor istine.
- Postojeci JSON-LD u `upload/catalog/view/theme/basel/template/product/product.twig` je rucno slozen u Twig templateu, a ne kroz `json_encode`, pa postoji rizik nevalidnog JSON-a kod navodnika, HTML-a i novih redova.
- JSON-LD uvijek oznacava dostupnost kao `InStock`, bez obzira na stvarnu kolicinu.
- JSON-LD cijena moze uzeti krivu cijenu kad postoji akcijska cijena, jer se gleda `price` prije `special`.
- `gtin14` se puni iz `upc`, sto moze biti netocno ako se radi o UPC/EAN vrijednosti drukcije duljine.
- Modul `seo_structured_data` uglavnom generira Twitter/OpenGraph meta tagove, ali ne pravi punu schema.org strukturu za proizvode, kategorije, organizaciju i breadcrumbs.
- Category stranice imaju canonical/prev/next, ali nemaju `ItemList` JSON-LD s proizvodima iz kategorije.
- Nema automatiziranog testa koji bi redovno provjeravao crawler pristup, sitemap svjezinu, validnost JSON-LD-a i uskladenost cijena/dostupnosti izmedu HTML-a, feeda i baze.

## Konkretni tehnicki nalazi

- Product JSON-LD se trenutno generira u `upload/catalog/view/theme/basel/template/product/product.twig`, linije 2-25.
- Product controller vec priprema podatke potrebne za bolji schema markup: brand, model, UPC/EAN, EPREL, opis, canonical URL i slike u `upload/catalog/controller/product/product.php`, okvirno linije 379-399 i 649-660.
- Category controller vec ima canonical, prev/next i OG URL logiku u `upload/catalog/controller/product/category.php`, okvirno linije 488-533, ali nema category `ItemList` schema.
- Simple sitemap feed postoji u `upload/catalog/controller/extension/feed/simple_google_sitemap.php`, linije 27-61, ali produkcija koristi `sitemap-index.xml` s generiranim sitemap datotekama koje na testu imaju stare `lastmod` datume iz 2023.
- Repo `upload/robots.txt`, linije 1-32, nije uskladen s produkcijskim `robots.txt` jer repo pokazuje na `sitemap.xml`, a produkcija na `sitemap-index.xml`.

## Live audit www.chipoteka.hr

Provjera je radena 2026-07-03 na produkciji.

Najvazniji nalazi:

- `https://www.chipoteka.hr/robots.txt` vraca HTTP 200 i pokazuje na `https://www.chipoteka.hr/sitemap-index.xml`.
- `https://www.chipoteka.hr/sitemap-index.xml` i `https://www.chipoteka.hr/sitemap.xml` vracaju HTTP 200 i XML, ali se serviraju dinamicki, s `OCSESSID`, `language` i `currency` cookiejima te `no-cache/no-store` headerima. Za sitemap je bolje da bude stabilan, cacheable XML bez sesije.
- `/llms.txt` vraca HTTP 404.
- Homepage je dostupna za browser user-agent, `Googlebot` i `Bingbot`, ali testirani AI user-agenti dobivaju Cloudflare/WAF HTTP 403: `GPTBot`, `OAI-SearchBot`, `ChatGPT-User`, `ClaudeBot`, `Claude-SearchBot`, `Claude-User` i `PerplexityBot`.
- `sitemap-index.xml` ima 92 sitemap datoteke, a sve `lastmod` vrijednosti su iz 2023-02-15. To je los signal za svjezinu kataloga.
- Uzorak prvih 50 URL-ova iz `sitemaps/sitemap_0_2_product_1.xml` dao je 33 x HTTP 404 i 17 x HTTP 200. Uzorak prvih 50 URL-ova iz category sitemapa dao je 4 x HTTP 404 i 46 x HTTP 200. Information sitemap uzorak je bio 12 x HTTP 200.
- Category stranica `https://www.chipoteka.hr/proizvodi-na-akciji` je server-side citljiva u HTML-u, ima canonical i paginaciju, prikazuje 866 artikala i product kartice su vidljive bez JavaScripta. To je dobro za crawlanje. Nedostaje joj `ItemList` structured data za proizvode na listingu.
- Testirani product URL `https://www.chipoteka.hr/33-v-5-v-ttl-logic-level-converter-module-wpi410-8090229159` ima `Product` JSON-LD, `BreadcrumbList`, canonical, OG price i vidljivu zalihu u HTML-u. To je dobra baza, ali vrijede gore navedeni problemi iz templatea: JSON se slaze rucno, availability je uvijek `InStock`, a GTIN polje treba mapirati prema stvarnoj duljini identifikatora.
- Live Google Base ruta `index.php?route=extension/feed/google_base` ne izgleda kao cist javni XML feed na brzoj provjeri jer vraca `text/html` header. Postojeci feed kod se moze iskoristiti kao baza, ali ga ne bih tretirao kao gotov AI/product-discovery feed bez dodatne provjere.

Zakljucak live audita: najhitnije nije novi content, nego tehnicki pristup i svjezina indeksa. Prvo treba rijesiti Cloudflare/WAF pristup za korisne AI discovery botove, zatim regenerirati sitemapove bez 404 URL-ova i tek onda nadograditi JSON-LD/feed sloj.

## Preporucene stavke

| # | Stavka | Opis | Procjena | Cijena |
|---|---|---|---:|---:|
| 1 | AI crawler i Cloudflare/WAF audit | Provjeriti pravila koja blokiraju AI usere, razdvojiti botove za pretrazivanje od botova za trening, pripremiti allowlist za korisne discovery botove i testirati HTTP 200/403 po user-agentu. | 4-6 h | 100-150 EUR |
| 2 | Robots.txt i sitemap uskladenje | Uskladiti repo i produkciju, potvrditi tocnu sitemap lokaciju, popraviti stale `lastmod`, provjeriti split sitemapova, izbaciti duplikate/filter URL-ove i osigurati da product/category/manufacturer/information URL-ovi budu stabilni. | 6-8 h | 150-200 EUR |
| 3 | Product JSON-LD refaktor | Prebaciti JSON-LD generiranje iz Twig stringova u PHP polje + `json_encode`; dodati tocan active price, sale/strikethrough price, availability prema zalihi, brand, SKU, MPN, GTIN, vise slika, seller, condition i canonical URL. | 10-12 h | 250-300 EUR |
| 4 | Category/brand structured data | Dodati `BreadcrumbList`, `ItemList` za kategorije/brandove, `WebSite` + `SearchAction`, `Organization`/`OnlineStore` podatke, osnovne policy podatke gdje ima smisla. | 6-8 h | 150-200 EUR |
| 5 | AI product feed | Napraviti read-only feed za AI/product discovery: `item_id`, title, plain-text description, URL, image URL-ovi, brand, category path, price, sale price, availability, GTIN/MPN i osnovni delivery/warranty podaci gdje postoje. Feed moze biti JSON/NDJSON i mapiran prema OpenAI product feed specifikaciji. | 10-12 h | 250-300 EUR |
| 6 | OpenAI/ChatGPT merchant priprema | Pripremiti feed i dokumentaciju za prijavu proizvoda u ChatGPT product discovery. Ukljucuje gap analysis prema OpenAI feed shemi i popis atributa koje jos treba napuniti iz ERP-a ili admina. | 3-4 h | 75-100 EUR |
| 7 | Opcionalna feed kompatibilnost za Google/druga trzista | Hrvatska trenutno nije podrzana kao target country za Google Shopping ads/free listings, pa ovo nije prioritet za domace Google Merchant prikaze. Stavka ima smisla samo kao osnovna provjera kompatibilnosti generickog product feeda za buduce sirenje, druga podrzana trzista ili ponovnu upotrebu atributa prema AI/usporednim kanalima. | 2-3 h | 50-75 EUR |
| 8 | `/llms.txt` i AI landing kontekst | Dodati `/llms.txt` kao kratki vodič za AI sustave: sto je Chipoteka, glavne kategorije, linkovi na sitemap/feed, kontakt, pravila kupnje, najvaznije info stranice. Ovo je emerging/proposed standard, pa ga treba tretirati kao dodatni signal, ne kao zamjenu za sitemap/schema. | 3-4 h | 75-100 EUR |
| 9 | FAQ i policy schema | Dodati osnovne strukturirane podatke za nacine placanja, dostavu, povrat, jamstvo, servis i poslovnice. Posebno korisno za AI odgovore na "kako kupiti", "koliko traje dostava", "moze li povrat". | 4-5 h | 100-125 EUR |
| 10 | Monitoring i regresijski testovi | Automatizirati osnovne provjere: AI crawler HTTP status, sitemap freshness, validan JSON-LD, cijena/dostupnost u JSON-LD-u naspram feeda, 404 u sitemapu i broj indeksabilnih URL-ova. | 5-6 h | 125-150 EUR |
| 11 | Content/attribute normalizacija | Pripremiti pravila za kvalitetnije product opise i specifikacije: cisti plain-text opis, bullet points, normalizirani atributi, sinonimi za bitne kategorije, pitanja i odgovori po kategorijama. | 7-8 h | 175-200 EUR |
| 12 | IndexNow / brze dojave promjena | Integrirati dojavu izmjena URL-ova prema IndexNow/Bing kad se proizvod doda, promijeni ili makne. Korisno za brzu rediscovery logiku u trazilicama i sustavima koji se na njih oslanjaju. | 4 h | 100 EUR |

## Preporuceni prosireni paket

Predlazem ponuditi jedan paket koji pokriva cijeli AI-friendly upgrade, bez podjele na manje faze. Razlog je sto se najveci ucinak dobiva tek kad se zajedno rijese crawler pristup, sitemap svjezina, structured data i feed sloj.

Paket ukljucuje:

1. AI crawler i Cloudflare/WAF audit
2. Robots.txt i sitemap uskladenje
3. Product JSON-LD refaktor
4. Category/brand structured data
5. AI product feed prema OpenAI/ChatGPT product discovery shemi
6. OpenAI/ChatGPT merchant pripremu
7. Opcionalnu genericku feed kompatibilnost za buduce Google/druga trzista
8. `/llms.txt` i AI landing kontekst
9. FAQ i policy schema
10. Monitoring i regresijske testove
11. Content/attribute normalizaciju
12. IndexNow / brze dojave promjena

Procjena paketa: 64-80 h  
Cijena paketa: 1.600-2.000 EUR

Ovaj paket pokriva tri najveca trenutna problema: AI modeli mogu biti blokirani, sitemapovi salju zastarjele signale, a product schema/feed sloj je djelomicno krhak i nedovoljno standardiziran. Uz to dodaje bolju pripremu za buduci product discovery kroz AI modele i usporedne kanale.

## Napomene za klijenta

Ovo nije garancija da ce svaki AI model odmah nuditi Chipoteku. Svaki sustav ima vlastiti indeks, ranking i politiku citiranja. Ali ovim radovima web postaje tehnicki spremniji za AI pretrazivanje: crawleri mogu pristupiti stranicama, proizvodi su strukturirano opisani, feedovi su svjezi, a cijene/dostupnost su manje dvosmislene.

Posebno bih naglasio da treba razlikovati:

- AI botove za pretrazivanje i odgovore korisnicima, koje uglavnom zelimo pustiti;
- AI botove za treniranje modela, koje mozemo pustiti ili blokirati ovisno o poslovnoj politici.

Praktican prijedlog je dozvoliti discovery/search botove kao sto su `OAI-SearchBot`, `ChatGPT-User`, `Claude-User`, `Claude-SearchBot`, `PerplexityBot`, `Googlebot` i `Bingbot`, a posebno odluciti sto napraviti s training botovima kao sto su `GPTBot` i `ClaudeBot`.

## Izvori i relevantne smjernice

- Google Search Central: Product structured data - https://developers.google.com/search/docs/appearance/structured-data/product
- Google Search Central: Merchant listing structured data - https://developers.google.com/search/docs/appearance/structured-data/merchant-listing
- Google Merchant Center: Product data specification - https://support.google.com/merchants/answer/7052112
- Google Merchant Center: Supported languages and countries - https://support.google.com/merchants/answer/160637
- OpenAI crawlers - https://developers.openai.com/api/docs/bots
- OpenAI/ChatGPT product feed - https://developers.openai.com/commerce/specs/file-upload/products
- OpenAI merchants / product discovery - https://chatgpt.com/merchants/
- Perplexity crawlers i WAF smjernice - https://docs.perplexity.ai/docs/resources/perplexity-crawlers
- Anthropic Claude crawler help - https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
- Bing IndexNow - https://www.bing.com/indexnow/getstarted
- llms.txt proposal - https://llmstxt.org/
