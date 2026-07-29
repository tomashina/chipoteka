-- Chipoteka: početni hrvatski sadržaj za stranicu Česta pitanja
-- Preduvjet: prvo pokrenuti 042_create_faq.sql.
-- Skripta je idempotentna: postojeće kategorije i pitanja ne duplicira niti briše.

START TRANSACTION;

SET @faq_language_id := (
  SELECT `language_id`
  FROM `oc_language`
  WHERE `status` = 1
    AND (`code` = 'hr-hr' OR `name` = 'Hrvatski')
  ORDER BY (`code` = 'hr-hr') DESC
  LIMIT 1
);

-- 1. Narudžbe i plaćanje
SET @faq_category_id := (
  SELECT fcd.`faq_category_id`
  FROM `oc_faq_category_description` fcd
  WHERE fcd.`language_id` = @faq_language_id
    AND fcd.`name` = 'Narudžbe i plaćanje'
  LIMIT 1
);

INSERT INTO `oc_faq_category`
  (`sort_order`, `status`, `date_added`, `date_modified`)
SELECT 10, 1, NOW(), NOW()
WHERE @faq_language_id IS NOT NULL
  AND @faq_category_id IS NULL;

SET @faq_category_id := IF(
  @faq_category_id IS NULL AND @faq_language_id IS NOT NULL,
  LAST_INSERT_ID(),
  @faq_category_id
);

INSERT INTO `oc_faq_category_description`
  (`faq_category_id`, `language_id`, `name`)
SELECT @faq_category_id, @faq_language_id, 'Narudžbe i plaćanje'
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_category_description`
    WHERE `faq_category_id` = @faq_category_id
      AND `language_id` = @faq_language_id
  );

INSERT INTO `oc_faq_item`
  (`faq_category_id`, `language_id`, `question`, `answer`, `sort_order`, `status`, `date_added`, `date_modified`)
SELECT
  @faq_category_id,
  @faq_language_id,
  seed.`question`,
  seed.`answer`,
  seed.`sort_order`,
  1,
  NOW(),
  NOW()
FROM (
  SELECT
    'Kako mogu napraviti narudžbu?' AS `question`,
    '<p>Odaberite željeni proizvod, dodajte ga u košaricu i otvorite košaricu kada ste spremni završiti kupnju. Unesite podatke za dostavu, odaberite ponuđeni način dostave i plaćanja te prije potvrde još jednom provjerite sve podatke.</p>' AS `answer`,
    10 AS `sort_order`
  UNION ALL
  SELECT
    'Koje načine plaćanja mogu koristiti?',
    '<p>Svi načini plaćanja dostupni za vašu narudžbu prikazat će se u koraku <strong>Plaćanje</strong>. Dostupne opcije mogu ovisiti o vrijednosti narudžbe, odabranom načinu dostave i drugim uvjetima kupnje.</p>',
    20
  UNION ALL
  SELECT
    'Kako znam da je narudžba uspješno zaprimljena?',
    '<p>Nakon uspješne predaje narudžbe prikazat će se potvrda, a na unesenu e-mail adresu trebala bi stići poruka s podacima narudžbe. Ako poruku ne vidite, provjerite mape Neželjena pošta i Promocije.</p>',
    30
  UNION ALL
  SELECT
    'Mogu li izmijeniti ili otkazati narudžbu?',
    '<p>Javite nam se što prije putem <a href="index.php?route=information/contact">kontakt obrasca</a> i navedite broj narudžbe. Ako obrada ili slanje još nisu započeli, provjerit ćemo koje su izmjene moguće.</p>',
    40
  UNION ALL
  SELECT
    'Gdje mogu pronaći podatke o svojoj narudžbi?',
    '<p>Registrirani korisnici svoje narudžbe mogu pregledati u odjeljku <a href="index.php?route=account/order">Povijest narudžbi</a>. Osnovni podaci nalaze se i u e-mail potvrdi poslanoj nakon predaje narudžbe.</p>',
    50
) AS seed
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_item` fi
    WHERE fi.`faq_category_id` = @faq_category_id
      AND fi.`language_id` = @faq_language_id
      AND fi.`question` = seed.`question`
  );

-- 2. Dostava i preuzimanje
SET @faq_category_id := (
  SELECT fcd.`faq_category_id`
  FROM `oc_faq_category_description` fcd
  WHERE fcd.`language_id` = @faq_language_id
    AND fcd.`name` = 'Dostava i preuzimanje'
  LIMIT 1
);

INSERT INTO `oc_faq_category`
  (`sort_order`, `status`, `date_added`, `date_modified`)
SELECT 20, 1, NOW(), NOW()
WHERE @faq_language_id IS NOT NULL
  AND @faq_category_id IS NULL;

SET @faq_category_id := IF(
  @faq_category_id IS NULL AND @faq_language_id IS NOT NULL,
  LAST_INSERT_ID(),
  @faq_category_id
);

INSERT INTO `oc_faq_category_description`
  (`faq_category_id`, `language_id`, `name`)
SELECT @faq_category_id, @faq_language_id, 'Dostava i preuzimanje'
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_category_description`
    WHERE `faq_category_id` = @faq_category_id
      AND `language_id` = @faq_language_id
  );

INSERT INTO `oc_faq_item`
  (`faq_category_id`, `language_id`, `question`, `answer`, `sort_order`, `status`, `date_added`, `date_modified`)
SELECT
  @faq_category_id,
  @faq_language_id,
  seed.`question`,
  seed.`answer`,
  seed.`sort_order`,
  1,
  NOW(),
  NOW()
FROM (
  SELECT
    'Koje su opcije dostave?' AS `question`,
    '<p>Opcije dostave koje su dostupne za proizvode u vašoj košarici prikazuju se tijekom završetka kupnje. Odaberite opciju koja vam najviše odgovara prije potvrde narudžbe.</p>' AS `answer`,
    10 AS `sort_order`
  UNION ALL
  SELECT
    'Gdje mogu provjeriti trošak dostave?',
    '<p>Trošak dostave prikazuje se u košarici odnosno tijekom završetka kupnje, prije konačne potvrde narudžbe. Više informacija možete pronaći i na stranici <a href="index.php?route=information/information&amp;information_id=4">Dostava</a>.</p>',
    20
  UNION ALL
  SELECT
    'Kako mogu pratiti pošiljku?',
    '<p>Ako je praćenje dostupno, podatke za praćenje dobit ćete e-mailom nakon što pošiljka bude predana dostavnoj službi. Status registrirane narudžbe možete provjeriti i u svom korisničkom računu.</p>',
    30
  UNION ALL
  SELECT
    'Mogu li osobno preuzeti narudžbu?',
    '<p>Ako je osobno preuzimanje dostupno za odabrane proizvode, ta će se opcija prikazati tijekom završetka kupnje. Pričekajte potvrdu da je narudžba spremna prije dolaska na mjesto preuzimanja.</p>',
    40
  UNION ALL
  SELECT
    'Što trebam učiniti ako paket stigne oštećen?',
    '<p>Prilikom preuzimanja pregledajte vanjsko stanje paketa. Ako primijetite oštećenje, fotografirajte paket i sadržaj, sačuvajte ambalažu te nam se što prije javite putem <a href="index.php?route=information/contact">kontakt obrasca</a> s brojem narudžbe.</p>',
    50
) AS seed
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_item` fi
    WHERE fi.`faq_category_id` = @faq_category_id
      AND fi.`language_id` = @faq_language_id
      AND fi.`question` = seed.`question`
  );

-- 3. Povrati i reklamacije
SET @faq_category_id := (
  SELECT fcd.`faq_category_id`
  FROM `oc_faq_category_description` fcd
  WHERE fcd.`language_id` = @faq_language_id
    AND fcd.`name` = 'Povrati i reklamacije'
  LIMIT 1
);

INSERT INTO `oc_faq_category`
  (`sort_order`, `status`, `date_added`, `date_modified`)
SELECT 30, 1, NOW(), NOW()
WHERE @faq_language_id IS NOT NULL
  AND @faq_category_id IS NULL;

SET @faq_category_id := IF(
  @faq_category_id IS NULL AND @faq_language_id IS NOT NULL,
  LAST_INSERT_ID(),
  @faq_category_id
);

INSERT INTO `oc_faq_category_description`
  (`faq_category_id`, `language_id`, `name`)
SELECT @faq_category_id, @faq_language_id, 'Povrati i reklamacije'
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_category_description`
    WHERE `faq_category_id` = @faq_category_id
      AND `language_id` = @faq_language_id
  );

INSERT INTO `oc_faq_item`
  (`faq_category_id`, `language_id`, `question`, `answer`, `sort_order`, `status`, `date_added`, `date_modified`)
SELECT
  @faq_category_id,
  @faq_language_id,
  seed.`question`,
  seed.`answer`,
  seed.`sort_order`,
  1,
  NOW(),
  NOW()
FROM (
  SELECT
    'Kako mogu prijaviti reklamaciju?' AS `question`,
    '<p>Pošaljite nam opis problema putem <a href="index.php?route=information/contact">kontakt obrasca</a>. Kako bismo zahtjev brže obradili, navedite <strong>broj narudžbe ili računa</strong>, naziv proizvoda i opis poteškoće te po mogućnosti priložite fotografije.</p>' AS `answer`,
    10 AS `sort_order`
  UNION ALL
  SELECT
    'Koje podatke trebam pripremiti za reklamaciju?',
    '<p>Pripremite sljedeće podatke:</p><ul><li>broj narudžbe ili računa,</li><li>naziv i oznaku proizvoda,</li><li>jasan opis problema,</li><li>fotografije ili video ako mogu pomoći u utvrđivanju kvara.</li></ul>',
    20
  UNION ALL
  SELECT
    'Kako mogu zatražiti povrat proizvoda?',
    '<p>Prije slanja proizvoda javite nam se kako biste dobili odgovarajuće upute. Uvjeti i postupak ovise o razlogu povrata, vrsti proizvoda i načinu kupnje. Detalji su dostupni u <a href="index.php?route=information/information&amp;information_id=3">Uvjetima kupovine</a>.</p>',
    30
  UNION ALL
  SELECT
    'Tko snosi trošak slanja kod povrata ili reklamacije?',
    '<p>Trošak i način slanja ovise o razlogu povrata te pravilima koja se primjenjuju na konkretan zahtjev. Prije slanja kontaktirajte podršku kako biste dobili točne upute i izbjegli nepotrebne troškove.</p>',
    40
  UNION ALL
  SELECT
    'Kada mogu očekivati povrat sredstava?',
    '<p>Povrat sredstava obrađuje se nakon zaprimanja i provjere vraćenog proizvoda, u skladu s primjenjivim uvjetima i korištenim načinom plaćanja. O statusu zahtjeva bit ćete obaviješteni putem kontakta navedenog u narudžbi.</p>',
    50
) AS seed
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_item` fi
    WHERE fi.`faq_category_id` = @faq_category_id
      AND fi.`language_id` = @faq_language_id
      AND fi.`question` = seed.`question`
  );

-- 4. Proizvodi i dostupnost
SET @faq_category_id := (
  SELECT fcd.`faq_category_id`
  FROM `oc_faq_category_description` fcd
  WHERE fcd.`language_id` = @faq_language_id
    AND fcd.`name` = 'Proizvodi i dostupnost'
  LIMIT 1
);

INSERT INTO `oc_faq_category`
  (`sort_order`, `status`, `date_added`, `date_modified`)
SELECT 40, 1, NOW(), NOW()
WHERE @faq_language_id IS NOT NULL
  AND @faq_category_id IS NULL;

SET @faq_category_id := IF(
  @faq_category_id IS NULL AND @faq_language_id IS NOT NULL,
  LAST_INSERT_ID(),
  @faq_category_id
);

INSERT INTO `oc_faq_category_description`
  (`faq_category_id`, `language_id`, `name`)
SELECT @faq_category_id, @faq_language_id, 'Proizvodi i dostupnost'
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_category_description`
    WHERE `faq_category_id` = @faq_category_id
      AND `language_id` = @faq_language_id
  );

INSERT INTO `oc_faq_item`
  (`faq_category_id`, `language_id`, `question`, `answer`, `sort_order`, `status`, `date_added`, `date_modified`)
SELECT
  @faq_category_id,
  @faq_language_id,
  seed.`question`,
  seed.`answer`,
  seed.`sort_order`,
  1,
  NOW(),
  NOW()
FROM (
  SELECT
    'Kako mogu provjeriti je li proizvod dostupan?' AS `question`,
    '<p>Informacija o dostupnosti prikazuje se na stranici proizvoda. Budući da se zalihe mogu mijenjati, konačna dostupnost potvrđuje se tijekom obrade narudžbe.</p>' AS `answer`,
    10 AS `sort_order`
  UNION ALL
  SELECT
    'Mogu li rezervirati proizvod?',
    '<p>Mogućnost rezervacije ovisi o proizvodu i trenutnoj zalihi. Pošaljite upit putem <a href="index.php?route=information/contact">kontakt obrasca</a> s nazivom ili šifrom proizvoda i željenim mjestom preuzimanja.</p>',
    20
  UNION ALL
  SELECT
    'Gdje mogu pronaći tehničke podatke proizvoda?',
    '<p>Tehničke značajke, opis i sadržaj pakiranja navedeni su na stranici proizvoda. Za detalje koji nisu navedeni pošaljite nam naziv ili šifru proizvoda putem kontakt obrasca.</p>',
    30
  UNION ALL
  SELECT
    'Kako mogu provjeriti kompatibilnost proizvoda?',
    '<p>Usporedite oznaku modela i tehničke zahtjeve navedene u opisu proizvoda s uređajem koji već imate. Ako niste sigurni, javite nam točan model uređaja i šifru proizvoda koji želite kupiti.</p>',
    40
  UNION ALL
  SELECT
    'Koliko traje jamstvo za proizvod?',
    '<p>Trajanje i uvjeti jamstva ovise o proizvodu i proizvođaču. Provjerite podatke na stranici proizvoda, u pripadajućoj dokumentaciji i na računu, a za dodatnu provjeru kontaktirajte podršku.</p>',
    50
) AS seed
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_item` fi
    WHERE fi.`faq_category_id` = @faq_category_id
      AND fi.`language_id` = @faq_language_id
      AND fi.`question` = seed.`question`
  );

-- 5. Korisnički račun i podrška
SET @faq_category_id := (
  SELECT fcd.`faq_category_id`
  FROM `oc_faq_category_description` fcd
  WHERE fcd.`language_id` = @faq_language_id
    AND fcd.`name` = 'Korisnički račun i podrška'
  LIMIT 1
);

INSERT INTO `oc_faq_category`
  (`sort_order`, `status`, `date_added`, `date_modified`)
SELECT 50, 1, NOW(), NOW()
WHERE @faq_language_id IS NOT NULL
  AND @faq_category_id IS NULL;

SET @faq_category_id := IF(
  @faq_category_id IS NULL AND @faq_language_id IS NOT NULL,
  LAST_INSERT_ID(),
  @faq_category_id
);

INSERT INTO `oc_faq_category_description`
  (`faq_category_id`, `language_id`, `name`)
SELECT @faq_category_id, @faq_language_id, 'Korisnički račun i podrška'
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_category_description`
    WHERE `faq_category_id` = @faq_category_id
      AND `language_id` = @faq_language_id
  );

INSERT INTO `oc_faq_item`
  (`faq_category_id`, `language_id`, `question`, `answer`, `sort_order`, `status`, `date_added`, `date_modified`)
SELECT
  @faq_category_id,
  @faq_language_id,
  seed.`question`,
  seed.`answer`,
  seed.`sort_order`,
  1,
  NOW(),
  NOW()
FROM (
  SELECT
    'Kako mogu otvoriti korisnički račun?' AS `question`,
    '<p>Otvorite stranicu za <a href="index.php?route=account/register">registraciju</a>, unesite tražene podatke i potvrdite obrazac. Nakon prijave možete jednostavnije pratiti narudžbe i upravljati svojim podacima.</p>' AS `answer`,
    10 AS `sort_order`
  UNION ALL
  SELECT
    'Zaboravio sam lozinku. Kako mogu postaviti novu?',
    '<p>Na stranici za prijavu odaberite opciju <a href="index.php?route=account/forgotten">Zaboravljena lozinka</a> i unesite e-mail adresu povezanu s računom. Slijedite upute iz poruke koju ćete primiti.</p>',
    20
  UNION ALL
  SELECT
    'Kako mogu promijeniti svoje osobne podatke?',
    '<p>Nakon prijave otvorite korisnički račun i odaberite uređivanje osobnih podataka ili adresara. Prije spremanja provjerite jesu li e-mail adresa, telefonski broj i adresa točni.</p>',
    30
  UNION ALL
  SELECT
    'Gdje mogu pregledati prethodne narudžbe?',
    '<p>Prijavite se u korisnički račun i otvorite <a href="index.php?route=account/order">Povijest narudžbi</a>. Ondje možete pronaći osnovne podatke i trenutačno dostupne informacije o narudžbama povezanim s računom.</p>',
    40
  UNION ALL
  SELECT
    'Kako mogu kontaktirati korisničku podršku?',
    '<p>Pošaljite poruku putem <a href="index.php?route=information/contact">kontakt obrasca</a>. Za upite o narudžbi navedite broj narudžbe, a za pitanja o proizvodu njegov naziv ili šifru kako bismo vam mogli brže pomoći.</p>',
    50
) AS seed
WHERE @faq_category_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_faq_item` fi
    WHERE fi.`faq_category_id` = @faq_category_id
      AND fi.`language_id` = @faq_language_id
      AND fi.`question` = seed.`question`
  );

COMMIT;
