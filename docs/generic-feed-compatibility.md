# Genericka feed kompatibilnost za buduce kanale

Datum: 2026-07-14

## Cilj

Pripremiti product feed atribute tako da se postojeci AI/OpenAI feed moze ponovno iskoristiti za buduce merchant kanale: Google Merchant Center ako/when trziste bude podrzano, usporedne portale, feed management alate ili druga AI/product discovery trzista.

Ova stavka ne pokrece Google Shopping za Hrvatsku. Ona priprema mapiranje i audit.

## Zasto ne radimo live Google HR feed sada

Sluzbeni Google Merchant Center popis podrzanih jezika i zemalja trenutno ne navodi Croatian/Hrvatsku za Shopping ads/free listings. Google trazi da je product data language uskladen s landing page i checkout iskustvom, i da se cilja zemlja u kojoj su Shopping ads/free listings dostupni.

Zato je sigurnije drzati ovu stavku kao genericku kompatibilnost i ne obecavati Google HR prikaz.

## Implementirano

Dodan je alat:

```bash
php scripts/generic_merchant_feed.php <openai-jsonl-url-or-file> [output-tsv] [limit]
```

Skripta koristi nas vec validirani OpenAI JSONL feed kao source of truth i mapira ga u Google-like merchant TSV spine.

Primjer samo audit:

```bash
php scripts/generic_merchant_feed.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100"
```

Primjer audit + TSV export:

```bash
php scripts/generic_merchant_feed.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100" /tmp/chipoteka-generic-products.tsv
```

Za puni katalog:

```bash
php scripts/generic_merchant_feed.php "https://www.chipoteka.hr/openai-products.jsonl" /tmp/chipoteka-generic-products.tsv
```

## Mapiranje polja

| Generic / Google-like field | OpenAI feed field | Status |
|---|---|---|
| `id` | `item_id` | OK |
| `title` | `title` | OK |
| `description` | `description` | OK |
| `link` | `url` | OK |
| `image_link` | `image_url` | OK |
| `additional_image_link` | `additional_image_urls` | Optional |
| `availability` | `availability` | OK, mapira se na `in stock`, `out of stock`, `preorder`, `backorder` |
| `price` | `price` | OK |
| `sale_price` | `sale_price` | Optional |
| `brand` | `brand` | OK |
| `gtin` | `gtin` | Optional, ali jako korisno |
| `mpn` | `mpn` | Optional, filtrirano u OpenAI feedu |
| `identifier_exists` | izvedeno iz GTIN/brand/MPN | OK |
| `condition` | `condition` | OK |
| `product_type` | `product_category` | OK |
| `shipping` | `shipping` | Optional |

## Sto je jos potrebno za konkretan Google/drugi market

Prije stvarnog ukljucivanja kanala treba znati:

- ciljnu zemlju i podrzani jezik
- valutu i lokalizaciju cijena
- dostavu i povrate za tu zemlju
- zahtjeve kanala za tax/shipping format
- pravila za kategorije i product identifiers
- hoce li se koristiti TSV, XML, API, SFTP ili feed management platforma

Ako se kasnije cilja npr. Austrija/Njemacka/Italija, treba pripremiti lokalizirane landing pageove ili barem jezik/cijene/checkout koji zadovoljava pravila tog trzista.

## Izvori

- Google Merchant Center supported languages and currencies: `https://support.google.com/merchants/answer/160637`
- Google Merchant Center product data specification: `https://support.google.com/merchants/answer/7052112`
