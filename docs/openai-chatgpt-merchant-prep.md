# OpenAI/ChatGPT merchant priprema

Datum: 2026-07-14

## Cilj

Pripremiti Chipoteka katalog za prijavu u ChatGPT product discovery / OpenAI commerce onboarding.

OpenAI trenutno trazi strukturirani product feed za product discovery. Sluzbeni merchant obrazac prihvaca prijavu za dijeljenje product feeda, a OpenAI navodi da shopping korisnici kupnju zavrsavaju na merchant webu, ne kroz obavezni ChatGPT checkout.

## Trenutno spremno

- Merchant web: `https://www.chipoteka.hr`
- Live feed za provjeru: `https://www.chipoteka.hr/openai-products.jsonl`
- Feed format: JSONL snapshot preko HTTPS-a
- Feed namjena: ChatGPT product discovery, bez direktnog checkouta
- Checkout strategija: korisnik se salje na Chipoteka product URL
- Glavna kategorija za OpenAI prijavu: `Electronics & Appliances`
- Target country u feedu: `HR`
- Store country u feedu: `HR`
- Currency: `EUR`
- `is_eligible_search`: `true`
- `is_eligible_checkout`: `false`

## Copy/paste za OpenAI merchant formu

Link: `https://chatgpt.com/merchants/`

Predlozeni unosi:

- Company: `Chipoteka`
- Link to your merchant website: `https://www.chipoteka.hr`
- Headquarter country: `Croatia`
- Primary Product Categories: `Electronics & Appliances`
- We are interested in: `Integrating my product feed so my products show up in search results on ChatGPT`
- Feed Size - Unique SKU Count: odabrati stvarni raspon nakon provjere punog feeda; za Chipoteka katalog najvjerojatnije `0-1M`
- Anything else you would like us to know:

```text
Chipoteka is a Croatian electronics and appliances retailer. We have prepared a structured product discovery feed aligned with OpenAI's stable product feed schema for non-Ads discovery. The feed includes product IDs, titles, descriptions, canonical product URLs, images, brand, price in EUR, availability, seller and return policy URLs, GTIN/MPN where available, category paths, shipping and basic review signals where available. Checkout remains on the merchant website.

Current validation endpoint:
https://www.chipoteka.hr/openai-products.jsonl

We can provide the same full snapshot as a stable jsonl.gz file over SFTP once onboarding access and delivery credentials are available.
```

## Feed mapping prema OpenAI stable schema

Required discovery polja:

| OpenAI field | Chipoteka izvor | Status |
|---|---|---|
| `is_eligible_search` | hardcoded `true` | OK |
| `is_eligible_checkout` | hardcoded `false` | OK |
| `item_id` | `product_id` | OK |
| `title` | `product_description.name` | OK |
| `description` | plain-text product description / meta fallback | OK |
| `url` | canonical product URL | OK |
| `brand` | manufacturer, fallback Chipoteka | OK |
| `image_url` | original `/image/catalog/...` product image | OK |
| `price` | product price with tax, EUR | OK |
| `availability` | `quantity`, `quantityws`, `stock_status` | OK |
| `seller_name` | `Chipoteka` | OK |
| `seller_url` | `https://www.chipoteka.hr` | OK |
| `return_policy` | `https://www.chipoteka.hr/uvjeti-kupovine` | OK |
| `target_countries` | `HR` | OK |
| `store_country` | `HR` | OK |

Optional signali koje vec saljemo gdje postoje:

| OpenAI field | Chipoteka izvor | Napomena |
|---|---|---|
| `gtin` | UPC/EAN | Salje se samo kad je validan 8/12/13/14 znamenki |
| `mpn` | MPN/model | Filtrira se da ne salje garanciju ili dostavni tekst |
| `condition` | hardcoded `new` | Svi proizvodi su novi proizvodi |
| `product_category` | category path | Koristi `>` separator |
| `additional_image_urls` | dodatne product slike | CSV lista URL-ova |
| `sale_price` | active special price | Samo ako je <= regular price |
| `pricing_trend` | `price_last_30` | Trenutno tekstualno "Lowest price in last 30 days..." |
| `weight`, `item_weight_unit` | product weight class | Samo ako postoji tezina |
| `dimensions` | product dimensions | Samo ako postoje sve dimenzije |
| `review_count`, `star_rating` | reviews | Samo ako postoje objavljene recenzije |
| `shipping` | osnovno HR pravilo dostave | Standard shipping |
| `seller_privacy_policy` | info page | Korisno iako checkout nije ukljucen |
| `seller_tos` | info page | Korisno iako checkout nije ukljucen |

## Gap analysis

Tehnicki gapovi prije finalnog OpenAI onboarding deliveryja:

- OpenAI file-upload dokumentacija preferira push na SFTP, stabilan naziv filea i full snapshot barem dnevno. Trenutno imamo live HTTPS JSONL endpoint; nakon odobrenja treba sloziti `jsonl.gz` export i SFTP upload job.
- Endpoint trenutno vraca JSONL bez `.gz` kompresije. To je dobro za javni QA, ali za OpenAI delivery treba imati stabilan compressed file, npr. `chipoteka-products.jsonl.gz`.
- Full feed treba testirati bez `limit` parametra i spremiti ukupan broj jedinstvenih SKU-ova za prijavni obrazac.
- `OAI-SearchBot` fake user-agent test i dalje moze vracati 403 kroz Cloudflare/WAF. To ne blokira SFTP feed delivery, ali ostaje AI crawler audit napomena.

Podatkovni gapovi koje ima smisla poboljsati kroz ERP/admin:

- `material`, `color`, `size`, `age_group`, `gender` uglavnom nisu relevantni za elektroniku, osim za rubne kategorije.
- `sale_price_start_date` i `sale_price_end_date` bi bili korisni ako Luceed/OpenCart pouzdano cuva datume akcija.
- `availability_date` treba dodati samo ako imamo pouzdan datum dolaska za `pre_order`.
- `warranty` nije standardno required OpenAI polje u stable product feedu, ali je korisno kao buduci content signal u product opisima/policy shemi.
- Review coverage je vjerojatno nizak; gdje postoji, salje se.

## Provjere prije prijave

Pokrenuti:

```bash
php scripts/openai_product_feed_audit.php "https://www.chipoteka.hr/openai-products.jsonl?limit=100" 100
php scripts/openai_merchant_readiness.php "https://www.chipoteka.hr/openai-products.jsonl?limit=1000"
```

Za puni katalog, nakon sto server bez problema generira full feed:

```bash
php scripts/openai_merchant_readiness.php "https://www.chipoteka.hr/openai-products.jsonl"
```

## Nakon OpenAI odobrenja

1. Dobiti SFTP host, username, path i autentikaciju od OpenAI-a.
2. Generirati stabilni full snapshot file, npr. `chipoteka-products.jsonl.gz`.
3. Uploadati na isti SFTP path/name svaki dan ili cesce.
4. Nakon uploadanja pokrenuti lokalni audit nad istim fileom prije slanja.
5. Ako se proizvod uklanja, ili ga maknuti iz full snapshot feeda ili poslati `is_eligible_search=false`.

## Izvori

- OpenAI Commerce get started: `https://developers.openai.com/commerce/guides/get-started`
- OpenAI file upload overview: `https://developers.openai.com/commerce/specs/file-upload/overview`
- OpenAI products schema: `https://developers.openai.com/commerce/specs/file-upload/products`
- OpenAI merchant application: `https://chatgpt.com/merchants/`
