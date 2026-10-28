# Automatska aktivacija artikala iz Luceeda

## Pravila

`updateQuantities` nakon uspješnog upisa zalihe automatski usklađuje aktivaciju.
Ista pravila koriste uvoz i ažuriranje artikala te `checkMinQty`.

- Postojeći deaktivirani artikl može se vratiti kada Luceed izričito vraća
  `enabled=D` i `webshop=D`, pozitivnu cijenu, opis i sliku. Web mora također
  imati cijenu, opis na zadanom jeziku i postojeću lokalnu sliku.
- Ponovna aktivacija zahtijeva pozitivnu zalihu. Ako je zadan pozitivan
  `Min_kol_web`, zaliha mora biti veća ili jednaka toj vrijednosti.
- `Min_kol_web` vrijedi za pojedini artikl. Vrijednost 2 nije opći minimum.
  Artikl bez tog atributa može se ponovno aktivirati već s jednim komadom.
- Odsutan ili nulti `Min_kol_web` ne uvodi novu zabranu prikaza već aktivnih
  artikala bez zalihe. Pozitivan zadani minimum deaktivira artikl kada zaliha
  padne ispod njega i dopušta povrat nakon dopune zalihe.
- Ovo pravilo nije OpenCart polje `minimum`, koje određuje minimalnu količinu
  u narudžbi. Ne mijenja količinu pakiranja.
- Buduće ručno deaktiviranje u obrascu artikla ili brzom uređivanju pamti se
  kao zabrana automatske aktivacije. Ručno aktiviranje uklanja tu zabranu.
  U običnom obrascu zabrana nastaje promjenom statusa; u brzom uređivanju
  izričit izbor deaktivacije vrijedi i ako je artikl već deaktiviran.
- Postojeće deaktivacije bez zapisa o ručnoj zabrani usklađuju se s Luceedom,
  prema dogovorenom pravilu oporavka.

## Zaštita od prekida

Nepotpuni, neispravni i sumnjivo smanjeni odgovori ne zamjenjuju prihvaćeni
popis. Izričita Luceed cijena `null` znači da artikl nema raspoloživu cijenu i
ne zadovoljava uvjet aktivacije; ne zaustavlja ostale artikle. Pojedini artikl
s nedostajućim ili neispravnim podacima za aktivaciju/minimum odgađa se do
sljedećeg valjanog popisa, bez promjene njegova statusa ili izmišljanja minimuma.
Njegov postojeći zadatak ostaje u redu čekanja, dok se ostali nastavljaju
obrađivati. Broj odgođenih artikala vidi se u odgovoru crona kao
`snapshot_skipped_products`, a razlozi u `luceed-product-snapshot-skipped.log`.
Prevelik broj neispravnih artikala i dalje odbija cijeli popis kroz provjeru
pokrivenosti. Sama odsutnost artikla iz odgovora nikada nije nalog za deaktiviranje
ili brisanje. Za to se koriste izričite Luceed oznake i zadani minimum.

Prihvaćeni popis i red čekanja spremaju se u jednoj transakciji. Nedovršeni
artikli ostaju u redu, a pojedini artikl uklanja se tek nakon uspješnog upisa.
Zalihe i artikli koriste zajedničko zaključavanje protiv istodobnog crona.

Cron za zalihe sam dohvaća novi popis artikala nakon ugradnje izmjene i zatim
jednom dnevno. Ako dohvat ne uspije, neovisno valjan upis zalihe i dalje može
završiti. Aktivacija tada smije koristiti prethodno provjerene podatke najviše
48 sati; stariji podaci ne mijenjaju automatski status. Odgovor HTTP 503 jasno
navodi djelomičan neuspjeh i sljedeći cron ponovno pokušava.

## Ugradnja na poslužitelju

1. U postojećem checkoutu preuzeti granu `main-plushosting`:

   ```sh
   git pull --ff-only origin main-plushosting
   ```

2. U OpenCart administraciji otvoriti **Proširenja → Modifikacije → Osvježi**,
   kako eventualne generirane kopije modela također koriste novi kod.
3. Jednom ručno pokrenuti postojeći cron **updateQuantities**, ili u
   **Luceed Sync. Manageru** odabrati **Update Količina**. Prvi prolaz sam
   priprema potrebna polja/tablice i dohvaća svježa pravila iz Luceeda.
4. Provjeriti odgovor. Uspjeh vraća `status: 200` i brojeve `activated` i
   `deactivated` unutar `activation`. HTTP 503 s porukom da su zalihe upisane
   označava problem provjere aktivacije, a ne poništen upis zalihe.

Ne treba zaseban cron za aktivaciju niti ručno izvršavanje migracijskog SQL-a.
Postojeći raspored za `updateQuantities` ostaje isti. Korisnik baze mora imati
pravo stvaranja i izmjene tablica; dvije pomoćne tablice pri prvoj uspješnoj
obnovi prelaze s MyISAM-a na InnoDB radi transakcija.

## Provjera izmjene

Na lokalnom razvojnom okruženju:

```sh
php scripts/tests/luceed_product_snapshot_test.php
php scripts/tests/luceed_product_status_test.php
php scripts/tests/luceed_sync_controller_test.php
```

Test statusa koristi zasebnu privremenu lokalnu bazu i privremene slike, koje
uklanja nakon izvođenja. Ostala dva testa ne pristupaju bazi ni udaljenim
servisima. Testovi ne mijenjaju aplikacijske podatke niti šalju mailove.

Relevantni logovi u `storage/logs/`: `luceed_product_sync_error.log`,
`luceed-product-snapshot-error.log`, `luceed-product-snapshot-rejected.log`,
`luceed-product-snapshot-skipped.log`,
`luceed_stock_sync_error.log` i `luceed_product_status_error.log`.
