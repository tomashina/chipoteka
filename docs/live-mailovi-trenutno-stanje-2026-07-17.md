# Chipoteka — mailovi kupcima: uvjeti i tekstovi

Stanje provjereno 17. 7. 2026. prema live `emails.json`, live `mail` konfiguraciji i aktivnim cronovima.

`%s` se u predmetu i tekstu zamjenjuje brojem narudžbe. Ispod je tekst bez HTML oblikovanja, ali s istim sadržajem koji vidi kupac.

## Oznake statusa koje se koriste u uvjetima

| Status | Značenje |
|---|---|
| `02` | Odobren |
| `03` | Korekcija |
| `05` | Odobren – komisioniranje |
| `06` | Nalog na slaganju |
| `09` | Nalog spreman za isporuku |
| `10` | Otpremljeno |
| `11` | Isporučeno |
| `12` | Rezervacija |

## 1. Mail odmah nakon zaprimanja narudžbe

### Kartica / WSPay

Uvjet: kreirana je nova narudžba i `payment_code = wspay`.

Predmet: **Chipoteka.hr - Narudžba broj %s je zaprimljena**

> Poštovani/a,
>
> zahvaljujemo Vam na Vašoj narudžbi. Uplatu kao i narudžbu smo uredno zaprimili i naš tim u webshopu će je obraditi u najbržem mogućem roku.

### Virman – B2C

Uvjet: kreirana je nova narudžba, `payment_code = bank_transfer` i korisnička grupa je manja od `2`.

Predmet: **Chipoteka.hr - Ponuda broj %s**

> Poštovani/a,
>
> zahvaljujemo Vam na Vašoj narudžbi, molimo vas da izvršite uplatu po sljedećim uputama za plaćanje. Rok za uplatu je maksimalno 48h tijekom koga robu koju ste naručili držimo rezerviranu za vas. Ukoliko u tom roku ne zaprimimo uplatu, nažalost moramo poništiti ovu narudžbu.

Mail sadrži i iznos, IBAN, model, poziv na broj i 2D barkod za uplatu.

### Virman – B2B

Uvjet: kreirana je nova narudžba, `payment_code = bank_transfer` i korisnička grupa je veća od `2`.

Predmet: **Chipoteka.hr - Ponuda broj %s**

> Poštovani/a,
>
> zahvaljujemo Vam na Vašoj narudžbi.

### Pouzeće – standardna dostava

Uvjet: kreirana je nova narudžba, `payment_code = cod` i dostava nije `weight.weight_5`.

Predmet: **Chipoteka.hr - Narudžba broj %s**

> Poštovani/a,
>
> zahvaljujemo Vam na Vašoj narudžbi broj %s koju smo uredno zaprimili i naš tim u webshopu će je obraditi u najbržem mogućem roku.
>
> Podsjećamo Vas da ćete plaćanje obaviti kod kurira naše dostavne službe pri preuzimanju paketa, te Vas molimo ukoliko ste u mogućnosti da pripremite točan iznos novca za Vašu narudžbu.
>
> Hvala Vam

### Pouzeće – BoxNow tok

Uvjet: kreirana je nova narudžba, `payment_code = cod` i dostava je `weight.weight_5`.

Predmet: **Chipoteka.hr - Narudžba broj %s**

> Poštovani/a,
>
> zahvaljujemo Vam na Vašoj narudžbi broj %s koju smo uredno zaprimili i naš tim u webshopu će je obraditi u najbržem mogućem roku.
>
> Podsjećamo Vas da ćete plaćanje izvršiti putem poveznice koju dobijete u SMS-u prije preuzimanja paketa.
>
> Hvala Vam

## 2. Mail nakon promjene statusa narudžbe

Statusi se provjeravaju periodički. Mail se šalje samo za prijelaze navedene u nastavku.

### Narudžba se obrađuje

Uvjeti:

| Način plaćanja | Prijelaz statusa |
|---|---|
| pouzeće, virman ili WSPay | `02 Odobren` → `05 Odobren – komisioniranje` |
| pouzeće, virman ili WSPay | `02 Odobren` → `03 Korekcija` |
| samo virman | `12 Rezervacija` → `05 Odobren – komisioniranje` |

Predmet: **Chipoteka.hr - Vaša narudžba broj %s se obrađuje**

> Poštovani/a,
>
> roba iz vaše narudžbe %s se obrađuje na našem skladištu.
>
> Čim je predamo dostavnoj službi, poslat ćemo Vam informaciju na mail.

### Zaprimljena narudžba ili uplata

| Uvjet | Mail koji se šalje |
|---|---|
| pouzeće: `12 Rezervacija` → `02 Odobren` | tekst za pouzeće iz poglavlja 1 |
| WSPay: `12 Rezervacija` → `02 Odobren` | tekst za WSPay iz poglavlja 1 |
| virman: `12 Rezervacija` → `02 Odobren` | tekst „Zaprimili smo uplatu” ispod |
| virman: `12 Rezervacija` → `03 Korekcija` | tekst „Zaprimili smo uplatu” ispod |

Predmet za virman: **Chipoteka.hr - Zaprimili smo uplatu za narudžbu %s**

> Poštovani/a, zahvaljujemo Vam na Vašoj uplati.
>
> Uplatu kao i narudžbu broj %s smo uredno zaprimili i naš tim u webshopu će je obraditi u najbržem mogućem roku.
>
> Hvala Vam.

### Paket je predan dostavnoj službi

Svi prijelazi u tablici šalju isti tekst:

| Način plaćanja | Prijelaz statusa |
|---|---|
| pouzeće, virman ili WSPay | `02` → `10` |
| pouzeće, virman ili WSPay | `03` → `10` |
| pouzeće, virman ili WSPay | `05` → `10` |
| pouzeće, virman ili WSPay | `06` → `10` |
| samo virman | `09` → `10` |
| samo virman ili WSPay | `12` → `10` |
| pouzeće, virman ili WSPay | `05` → `11` |
| pouzeće, virman ili WSPay | `12` → `11` |
| pouzeće, virman ili WSPay | `02` → `11` |
| pouzeće, virman ili WSPay | `03` → `11` |
| samo pouzeće ili WSPay | `05` → `03` |

Predmet: **Chipoteka.hr - Vaš paket je spakiran i predan dostavnoj službi**

> Poštovani/a,
>
> Vaš paket iz narudžbe %s je spakiran i predan dostavnoj službi. Vrlo brzo ćete od dostavne službe dobiti poruku sa poveznicom I brojem paketa kako bi mogli pratiti Vašu narudžbu.
>
> Kupac je dužan prilikom preuzimanja pošiljke pregledati njeno pakiranje. Ako su na pakiranju pošiljke vidljiva oštećenja, kupac treba odbiti primitak iste uz napomenu da pošiljka sadrži nedostatak, jer postoji mogućnost da je proizvod unutar takvog pakiranja oštećen. Odbijanjem primitka, dostavna služba će pošiljku vratiti Z-el d.o.o. .
>
> Ako kupac preuzme pošiljku koja ima vidljiva vanjska oštećenja na pakiranju ili drugi vidljivi nedostatak proizvoda, dužan je isti dan dostaviti fotografije oštećenja pakiranja i proizvoda na e-mail adresu webnarudzba@chipoteka.hr . Prigovori dostavljeni slijedeći dan ili kasnije, neće biti prihvaćeni.
>
> Zahvaljujemo Vam na povjerenju.

Važno za novu komunikaciju: ovaj se tekst trenutačno šalje i za prijelaze prema statusu `11 Isporučeno`, kao i za prijelaz `05 → 03 Korekcija`, iako tekst govori da je paket predan dostavnoj službi.

## 3. Vremenski mailovi

Sljedeći uvjeti i tekstovi postoje u live konfiguraciji. Na trenutačnom live kodu ne šalju se zbog pogrešnih vremenskih uvjeta u mehanizmu.

### Uplata nije zaprimljena nakon 24 sata

Konfigurirani uvjet: plaćanje virmanom i narudžba je 24 sata ostala u statusu `12 Rezervacija`.

Predmet: **Chipoteka.hr - I dalje čekamo uplatu za vašu narudžbu broj %s**

> Poštovani/a,
>
> do ovog trena, nismo zaprimili uplatu za narudžbu broj %s koju ste obavili na našem webshopu. Podsjećamo Vas da robu možemo držati rezerviranu još samo 24h nakon čega, ukoliko ne zaprimimo uplatu ćemo morati poništiti Vašu narudžbu.
>
> Ako ste uplatu već napravili, molimo zanemarite ovu obavijest

### Uplata nije zaprimljena nakon 48 sati

Konfigurirani uvjet: plaćanje virmanom i narudžba je 48 sati ostala u statusu `12 Rezervacija`.

Predmet: **Chipoteka.hr - Vaša narudžba broj %s je otkazana**

> Poštovani/a,
>
> rezervacija za Vašu narudžbu broj %s je automatski otkazana s obzirom da niste napravili uplatu u roku od 48h. Ukoliko ste uplatu zaboravili napraviti, a proizvodi su Vam i dalje potrebni, molimo Vas da kupnju ponovite na našem webshopu chipoteka.hr.
>
> Narudžbu, pored plaćanja virmanom, možete platiti I karticama kao i pouzećem (prilikom preuzimanja od naše dostavne službe).
>
> Hvala Vam.

Važno: ovaj mail tvrdi da je narudžba automatski otkazana, ali postojeći mail mehanizam sam ne mijenja status narudžbe u Storno.

### Narudžba nije poslana nakon 24 sata

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 24 sata ostala u statusu `02 Odobren` ili `05 Odobren – komisioniranje`.

Predmet: **Chipoteka.hr - Molimo Vas za malo strpljenja za narudžbu broj %s**

> Poštovani/a,
>
> dio Vaše naručene robe (ili sva) se nalazi na našem izdvojenom skladištu. Bit će nam potrebno još malo više vremena kako bi je prikupili i poslali Vam je.
>
> Hvala Vam na strpljenju i razumijevanju.

### Narudžba nije poslana nakon 72 sata

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 72 sata ostala u statusu `02 Odobren` ili `05 Odobren – komisioniranje`.

Predmet: **Chipoteka.hr - I dalje Vas molimo za strpljenje za narudžbu broj %s**

> Poštovani/a,
>
> znamo da ste nestrpljivi, ali imamo neuobičajeno veliki broj narudžbi. A s obzirom da je dodatno i dio Vaše naručene robe (ili sva) na vanjskom skladištu, dolazi do malog kašnjenja.
>
> Hvala Vam puno na strpljenju i razumijevanju.

### Sedam dana nakon statusa „Isporučeno”

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 168 sati ostala u statusu `11 Isporučeno`.

Predmet: **Chipoteka.hr - Vaše zadovoljstvo nam je bitno**

> Poštovani/a,
>
> prije sedam dana ste obavili kupnju na webshopu Chipoteka.hr i puno Vam hvala na povjerenju.
>
> Svaki dan radimo na tome da vaše zadovoljstvo bude što veće, ukoliko nam javite kakvo je Vaše iskustvo pri kupovini bilo, možemo povećati kvalitetu.

## 4. Napuštena košarica

Za napuštenu košaricu trenutačno ne postoji uvjet/triger ni tekst maila. Chipoteka aplikacija kupcu ne šalje abandoned-cart poruku.

## 5. Kada se ne šalje mail

- Ako promjena statusa nije izričito navedena u gornjim tablicama, mail se ne šalje.
- Za prijelaz u status `04 Storno` nema konfiguriranog statusnog maila.
- Za načine plaćanja izvan `cod`, `bank_transfer` i `wspay` nema ovih automatiziranih mailova.
- Ručno uključivanje „obavijesti kupca” pri promjeni statusa ne šalje standardni OpenCart statusni mail.
- Virman narudžba korisničke grupe točno `2` ne zadovoljava ni B2C uvjet `< 2` ni B2B uvjet `> 2`, pa nema odabran početni custom mail.
