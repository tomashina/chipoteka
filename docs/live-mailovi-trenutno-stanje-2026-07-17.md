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

Vrijeme se računa od zadnje promjene statusa. Mail se šalje samo ako je narudžba na pragu slanja i dalje u navedenom statusu, i to samo jednom.

### Uplata nije zaprimljena nakon 24 sata

Konfigurirani uvjet: plaćanje virmanom i narudžba je 24 sata ostala u statusu `12 Rezervacija`.

Predmet: **Chipoteka.hr - Podsjetnik za uplatu narudžbe broj %s**

> Poštovani/a,
>
> u ovom trenutku još nemamo evidentiranu uplatu za narudžbu broj %s.
>
> Ako ste uplatu već izvršili, molimo zanemarite ovu poruku jer evidentiranje uplate može potrajati. Ako uplatu još niste izvršili, podatke za plaćanje možete pronaći u ponudi koju smo Vam poslali.
>
> Hvala Vam.

### Uplata nije zaprimljena nakon 48 sati

Konfigurirani uvjet: plaćanje virmanom i narudžba je 48 sati ostala u statusu `12 Rezervacija`.

Predmet: **Chipoteka.hr - Uplata za narudžbu broj %s nije evidentirana**

> Poštovani/a,
>
> ni nakon 48 sati još nemamo evidentiranu uplatu za narudžbu broj %s.
>
> Ako ste uplatu već izvršili, molimo javite nam se na webnarudzba@chipoteka.hr kako bismo je provjerili. Ako uplatu još niste izvršili, a želite nastaviti s narudžbom, također nam se javite kako bismo prije uplate provjerili dostupnost proizvoda.
>
> Hvala Vam.

### Narudžba nije poslana nakon 24 sata

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 24 sata ostala u statusu `02 Odobren` ili `05 Odobren – komisioniranje`.

Predmet: **Chipoteka.hr - Obrada narudžbe broj %s traje nešto dulje**

> Poštovani/a,
>
> Vaša narudžba broj %s još je u obradi. Ispričavamo se što obrada traje nešto dulje od očekivanog.
>
> Čim narudžba bude predana dostavnoj službi, poslat ćemo Vam obavijest e-mailom.
>
> Hvala Vam na strpljenju i razumijevanju.

### Narudžba nije poslana nakon 72 sata

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 72 sata ostala u statusu `02 Odobren` ili `05 Odobren – komisioniranje`.

Predmet: **Chipoteka.hr - Obavijest o statusu narudžbe broj %s**

> Poštovani/a,
>
> Vaša narudžba broj %s još je u obradi i nije predana dostavnoj službi. Ispričavamo se zbog kašnjenja i radimo na tome da je pripremimo što prije.
>
> Ako trebate dodatne informacije, slobodno nam se javite na webnarudzba@chipoteka.hr.
>
> Hvala Vam na strpljenju i razumijevanju.

### Sedam dana nakon statusa „Isporučeno”

Konfigurirani uvjet: pouzeće, virman ili WSPay; narudžba je 168 sati ostala u statusu `11 Isporučeno`.

Predmet: **Chipoteka.hr - Je li sve u redu s narudžbom broj %s?**

> Poštovani/a,
>
> prije otprilike sedam dana Vaša narudžba broj %s označena je kao isporučena. Nadamo se da je sve proteklo u redu i da ste zadovoljni kupnjom.
>
> Ako imate pitanje ili trebate pomoć, slobodno nam se javite na webnarudzba@chipoteka.hr.
>
> Hvala Vam na povjerenju.



