# AI crawler / WAF checklist

Ova checklist sluzi za stavku 1 iz AI-friendly paketa: potvrditi da Cloudflare/WAF ne blokira korisne discovery botove, a da se zasebno donese odluka o botovima za trening modela.

## Botovi koje zelimo pustiti za discovery

- `OAI-SearchBot` - OpenAI crawler za ChatGPT search/discovery.
- `ChatGPT-User` - user-triggered OpenAI fetch kada korisnik trazi ili otvara sadrzaj kroz ChatGPT.
- `Claude-SearchBot` - Anthropic crawler za search/discovery.
- `Claude-User` - user-triggered Anthropic fetch kroz Claude.
- `PerplexityBot` - Perplexity crawler za search index i citiranje izvora.
- `Perplexity-User` - user-triggered Perplexity fetch.
- `Googlebot` i `Bingbot` - bazni search crawleri koje i AI sustavi cesto koriste kao signal.

## Botovi za poslovnu odluku

- `GPTBot` - OpenAI crawler za poboljsanje/trening modela.
- `ClaudeBot` - Anthropic crawler za poboljsanje/trening modela.

Ako klijent zeli maksimalnu AI vidljivost, mogu ostati dozvoljeni. Ako ne zeli davati sadrzaj za trening modela, mogu se blokirati u `robots.txt` i/ili WAF-u bez blokiranja discovery botova.

## Provjera nakon Cloudflare promjene

Pokrenuti:

```bash
php scripts/ai_crawler_audit.php https://www.chipoteka.hr
```

Ocekivano:

- `/`, `/robots.txt`, `/sitemap-index.xml` i `/sitemap.xml` trebaju vratiti `2xx` ili `3xx` za discovery botove.
- `GPTBot` i `ClaudeBot` mogu biti `2xx`, `3xx` ili blokirani, ovisno o poslovnoj odluci.
- Ako `Googlebot`/`Bingbot` prolaze, a AI discovery botovi dobiju `403`, problem je najvjerojatnije u Cloudflare managed rules, Bot Fight Mode, Super Bot Fight Mode, WAF custom rule ili hosting firewallu.

## Cloudflare pravila koja treba provjeriti

- Bot Fight Mode / Super Bot Fight Mode.
- WAF custom rules po user-agentu.
- Managed rules koje ciljaju `AI crawler`, `scraper`, `unknown bot` ili `bad bot`.
- Rate limiting koji razlikuje bot user-agent od browser user-agenta.
- Verified bot / allowlist pravila po poznatim botovima i IP rasponima gdje su dostupni.

## Sluzbene reference

- OpenAI crawlers: https://developers.openai.com/api/docs/bots
- Anthropic crawler help: https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
- Perplexity crawlers: https://docs.perplexity.ai/docs/resources/perplexity-crawlers
