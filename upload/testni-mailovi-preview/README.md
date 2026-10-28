# Chipoteka — lokalni pregled mailova

Samostalna statička mini-stranica za pregled svih kombinacija iz matrice statusnih i vremenskih mailova.

Svaki pojedinačni mail renderiran je izravno kroz postojeći produkcijski predložak `upload/admin/view/template/mail/mail.twig`; promijenjeni su samo novi tekstovi i izmišljeni testni podaci. Navigacijska stranica nije dio maila.

## Pokretanje

Može se otvoriti izravno datoteka `index.html` ili pokrenuti lokalni web-poslužitelj:

```bash
php -S 127.0.0.1:8743 -t upload/testni-mailovi-preview
```

Zatim otvoriti `http://127.0.0.1:8743/`.

## Sadržaj

- `index.html` — preglednik s filtrima i desktop/mobilnim prikazom
- `mails/*.html` — zaseban samostalni HTML za svaku kombinaciju
- `assets/scenarios.js` — indeks svih scenarija i testnih podataka
- `assets/app.js` i `assets/styles.css` — sučelje mini-stranice

Svi kupci, adrese, OIB-i, brojevi narudžbi i proizvodi su izmišljeni. Namjerno su korištene složenije adrese (`12 A`, `23/2`, `2, ulaz C`, `269 D`) kako bi se uočile pogreške pri spajanju ulice i kućnog broja.

## Ponovna izgradnja

```bash
php scripts/build_mail_preview.php
```

Generator očekuje 73 scenarija i prekida rad ako broj nije potpun.

## Otvorena pitanja iz specifikacije

- V6 navodi status `10 — Isporučeno`, dok statusna tablica `10` naziva `Otpremljeno`.
- V7 i V8 status `09` nazivaju `Rezervacija`, dok statusna tablica `09` naziva `Nalog spreman za isporuku`.
- Globalna napomena traži uvažavanje vikenda i hrvatskih neradnih dana, dok su pragovi zapisani u satima. Potrebno je potvrditi točno poslovno računanje rokova.
- B2B početni virman ima naveden predmet i uvjet, ali ne i cjelovit tekst tijela poruke.
- Legenda propisuje otkazivanje B2C/preuzimanje virman narudžbe nakon 48 sati, dok V9 propisuje podsjetnik tek nakon 96 sati. Ako vrijedi otkazivanje nakon 48 sati, V9 se ne može poslati.
- Postojeći live predložak u podnožju spaja adresu bez razmaka (`Industrijska cesta 2810360 Sesvete`) i prikazuje `©2021`; u previewu je to namjerno ostavljeno nepromijenjeno radi vjernog prikaza.

Ove točke su vidljivo označene u pregledniku kako bi ih naručitelj mogao potvrditi prije implementacije produkcijskih pravila.
