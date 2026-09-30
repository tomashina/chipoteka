<?php

declare(strict_types=1);

/**
 * Builds a completely static review site for the Chipoteka customer-mail matrix.
 *
 * Usage:
 *   php scripts/build_mail_preview.php
 */

$projectRoot = dirname(__DIR__);
$outputRoot  = $projectRoot . '/upload/testni-mailovi-preview';
$mailRoot    = $outputRoot . '/mails';
$assetRoot   = $outputRoot . '/assets';

// The preview renders the exact Twig used by live status/timed emails.
// The installed Twig version emits deprecation notices on newer PHP versions;
// those notices do not affect the generated HTML.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require_once $projectRoot . '/storage/vendor/autoload.php';

$twigLoader = new \Twig\Loader\FilesystemLoader($projectRoot . '/upload/admin/view/template');
$twig       = new \Twig\Environment($twigLoader, [
    'autoescape' => false,
    'cache'      => false,
]);

$order = [
    'number'          => '128457',
    'date'            => '30.09.2026. u 11:45',
    'pickup_date'     => '02.10.2026',
    'subtotal'        => '111,60 €',
    'shipping'        => '5,17 €',
    'pickup_shipping' => '0,00 €',
    'total'           => '116,77 €',
    'pickup_total'    => '111,60 €',
    'iban'            => 'HR12 0000 0000 0000 0000 0',
    'model'           => 'HR00',
    'reference'       => '1284572609',
    'products'        => [
        [
            'sku'      => 'TEST-GAN-65W',
            'name'     => 'USB-C punjač GaN 65 W s tri priključka i odvojivim kabelom',
            'quantity' => 1,
            'price'    => '49,90 €',
            'total'    => '49,90 €',
        ],
        [
            'sku'      => 'TEST-PROD-3M',
            'name'     => 'Produžni kabel, 6 utičnica, 3 m, s prenaponskom zaštitom',
            'quantity' => 2,
            'price'    => '18,40 €',
            'total'    => '36,80 €',
        ],
        [
            'sku'      => 'TEST-HDMI-USBC',
            'name'     => 'Adapter USB-C na HDMI 4K — aluminijsko kućište',
            'quantity' => 1,
            'price'    => '24,90 €',
            'total'    => '24,90 €',
        ],
    ],
];

$profiles = [
    'b2c' => [
        'label'        => 'B2C kupac — različita adresa dostave',
        'customer'     => 'Ana-Marija Kovačević',
        'company'      => '',
        'oib'          => '',
        'email'        => 'ana-marija.kovacevic@example.test',
        'phone'        => '+385 91 234 5678',
        'billing'      => [
            'street'  => 'Ulica grada Vukovara',
            'number'  => '12 A',
            'line'    => 'Ulica grada Vukovara 12 A',
            'postcode'=> '10000',
            'city'    => 'Zagreb',
            'country' => 'Hrvatska',
        ],
        'shipping'     => [
            'street'  => 'Prilaz baruna Filipovića',
            'number'  => '23/2',
            'line'    => 'Prilaz baruna Filipovića 23/2',
            'postcode'=> '10000',
            'city'    => 'Zagreb',
            'country' => 'Hrvatska',
        ],
    ],
    'boxnow' => [
        'label'        => 'B2C kupac — BOX NOW paketomat',
        'customer'     => 'Ana-Marija Kovačević',
        'company'      => '',
        'oib'          => '',
        'email'        => 'ana-marija.kovacevic@example.test',
        'phone'        => '+385 91 234 5678',
        'billing'      => [
            'street'  => 'Ulica grada Vukovara',
            'number'  => '12 A',
            'line'    => 'Ulica grada Vukovara 12 A',
            'postcode'=> '10000',
            'city'    => 'Zagreb',
            'country' => 'Hrvatska',
        ],
        'shipping'     => [
            'street'  => 'BOX NOW paketomat ZAG-104, Heinzelova',
            'number'  => '47 A',
            'line'    => 'BOX NOW paketomat ZAG-104, Heinzelova 47 A',
            'postcode'=> '10000',
            'city'    => 'Zagreb',
            'country' => 'Hrvatska',
        ],
    ],
    'b2b' => [
        'label'        => 'B2B kupac — tvrtka i kontakt osoba',
        'customer'     => 'Ivan Horvat',
        'company'      => 'ELEKTRO TEST d.o.o.',
        'oib'          => '12345678901',
        'email'        => 'nabava@elektro-test.example.test',
        'phone'        => '+385 1 555 0199',
        'billing'      => [
            'street'  => 'Industrijska cesta',
            'number'  => '7 B',
            'line'    => 'Industrijska cesta 7 B',
            'postcode'=> '10431',
            'city'    => 'Sveta Nedelja',
            'country' => 'Hrvatska',
        ],
        'shipping'     => [
            'street'  => 'Skladišna ulica',
            'number'  => '2, ulaz C',
            'line'    => 'Skladišna ulica 2, ulaz C',
            'postcode'=> '10431',
            'city'    => 'Sveta Nedelja',
            'country' => 'Hrvatska',
        ],
    ],
    'pickup' => [
        'label'        => 'B2C kupac — preuzimanje u poslovnici',
        'customer'     => 'Petra Radić',
        'company'      => '',
        'oib'          => '',
        'email'        => 'petra.radic@example.test',
        'phone'        => '+385 98 765 432',
        'billing'      => [
            'street'  => 'Vukovarska ulica',
            'number'  => '206',
            'line'    => 'Vukovarska ulica 206',
            'postcode'=> '31000',
            'city'    => 'Osijek',
            'country' => 'Hrvatska',
        ],
        'shipping'     => [
            'street'  => 'Chipoteka Zagreb — Ulica grada Vukovara',
            'number'  => '269 D',
            'line'    => 'Chipoteka Zagreb — Ulica grada Vukovara 269 D',
            'postcode'=> '10000',
            'city'    => 'Zagreb',
            'country' => 'Hrvatska',
        ],
    ],
];

$channels = [
    'cod_standard' => [
        'label'       => 'Pouzeće — standardna dostava',
        'short'       => 'Pouzeće',
        'payment'     => 'Plaćanje pouzećem',
        'fulfilment'  => 'GLS dostava na adresu',
        'profile'     => 'b2c',
        'kind'        => 'delivery',
        'customerType'=> 'B2C',
    ],
    'cod_boxnow' => [
        'label'       => 'Pouzeće — BOX NOW',
        'short'       => 'BOX NOW',
        'payment'     => 'PAY ON THE GO — poveznica SMS-om',
        'fulfilment'  => 'BOX NOW paketomat',
        'profile'     => 'boxnow',
        'kind'        => 'delivery',
        'customerType'=> 'B2C',
    ],
    'wspay' => [
        'label'       => 'Kartično plaćanje — WSPay',
        'short'       => 'WSPay',
        'payment'     => 'Kartično plaćanje — WSPay',
        'fulfilment'  => 'GLS dostava na adresu',
        'profile'     => 'b2c',
        'kind'        => 'delivery',
        'customerType'=> 'B2C',
    ],
    'bank_transfer' => [
        'label'       => 'Virman — dostava (B2C)',
        'short'       => 'Virman B2C',
        'payment'     => 'Virman',
        'fulfilment'  => 'GLS dostava na adresu',
        'profile'     => 'b2c',
        'kind'        => 'delivery',
        'customerType'=> 'B2C',
    ],
    'bank_transfer_b2b' => [
        'label'       => 'Virman — dostava (B2B)',
        'short'       => 'Virman B2B',
        'payment'     => 'Virman',
        'fulfilment'  => 'Dostava na skladište kupca',
        'profile'     => 'b2b',
        'kind'        => 'delivery',
        'customerType'=> 'B2B',
    ],
    'pickup_pay' => [
        'label'       => 'Preuzimanje u poslovnici — plaćanje',
        'short'       => 'Poslovnica / plaćanje',
        'payment'     => 'Plaćanje u poslovnici',
        'fulfilment'  => 'Preuzimanje u poslovnici',
        'profile'     => 'pickup',
        'kind'        => 'pickup',
        'customerType'=> 'B2C',
    ],
    'pickup_bank_transfer' => [
        'label'       => 'Preuzimanje u poslovnici — virman',
        'short'       => 'Poslovnica / virman',
        'payment'     => 'Virman',
        'fulfilment'  => 'Preuzimanje u poslovnici',
        'profile'     => 'pickup',
        'kind'        => 'pickup',
        'customerType'=> 'B2C',
    ],
    'pickup_card' => [
        'label'       => 'Preuzimanje u poslovnici — kartično',
        'short'       => 'Poslovnica / kartica',
        'payment'     => 'Kartično plaćanje',
        'fulfilment'  => 'Preuzimanje u poslovnici',
        'profile'     => 'pickup',
        'kind'        => 'pickup',
        'customerType'=> 'B2C',
    ],
];

$deliveryChannels = ['cod_standard', 'cod_boxnow', 'wspay', 'bank_transfer'];
$matrixChannels   = ['cod_standard', 'cod_boxnow', 'wspay', 'bank_transfer', 'pickup_pay', 'pickup_bank_transfer', 'pickup_card'];
$pickupChannels   = ['pickup_pay', 'pickup_bank_transfer', 'pickup_card'];

$scenarios = [];

function slugify(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $ascii = $ascii === false ? $value : $ascii;
    $ascii = strtolower($ascii);
    $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii);

    return trim((string) $ascii, '-');
}

function replaceOrderNumber(string $value, string $orderNumber): string
{
    return str_replace('%s', $orderNumber, $value);
}

function addScenario(
    array &$scenarios,
    array $channels,
    array $profiles,
    array $order,
    string $code,
    string $type,
    string $from,
    string $to,
    string $channelKey,
    string $subject,
    array $paragraphs,
    array $options = []
): void {
    if (!isset($channels[$channelKey])) {
        throw new RuntimeException('Unknown channel: ' . $channelKey);
    }

    $channel = $channels[$channelKey];
    $profile = $profiles[$channel['profile']];
    $id      = strtolower($code) . '-' . slugify($channelKey);
    $subject = replaceOrderNumber($subject, $order['number']);

    foreach ($paragraphs as &$paragraph) {
        if (is_string($paragraph)) {
            $paragraph = ['text' => replaceOrderNumber($paragraph, $order['number']), 'tone' => 'normal'];
        } else {
            $paragraph['text'] = replaceOrderNumber($paragraph['text'], $order['number']);
            $paragraph['tone'] = $paragraph['tone'] ?? 'normal';
        }
    }
    unset($paragraph);

    $scenarios[] = [
        'id'              => $id,
        'code'            => $code,
        'type'            => $type,
        'from'            => $from,
        'to'              => $to,
        'channelKey'      => $channelKey,
        'channel'         => $channel['label'],
        'channelShort'    => $channel['short'],
        'payment'         => $channel['payment'],
        'fulfilment'      => $channel['fulfilment'],
        'kind'            => $channel['kind'],
        'customerType'    => $channel['customerType'],
        'profileKey'      => $channel['profile'],
        'profileLabel'    => $profile['label'],
        'customer'        => $profile,
        'subject'         => $subject,
        'paragraphs'      => $paragraphs,
        'timing'          => $options['timing'] ?? '',
        'workingDays'     => $options['workingDays'] ?? false,
        'warnings'        => $options['warnings'] ?? [],
        'sourceNote'      => $options['sourceNote'] ?? '',
        'showBankDetails' => $options['showBankDetails'] ?? false,
        'file'            => 'mails/' . $id . '.html',
    ];
}

// STATUS 1 — created in reservation.
addScenario(
    $scenarios, $channels, $profiles, $order,
    'S01', 'status', 'Kreirana narudžba', '12 — Rezervacija', 'bank_transfer',
    'Chipoteka.hr - Ponuda broj %s',
    [
        'Pozdrav,',
        'zahvaljujemo na narudžbi.',
        'Za nastavak obrade potrebno je izvršiti uplatu prema podacima navedenima u ponudi. Narudžbu i pripadajuću robu držimo rezerviranu 2 radna dana. Ako u tom roku ne zaprimimo uplatu, narudžbu ćemo nažalost morati otkazati.',
        'Podaci za uplatu nalaze se u nastavku poruke. Chipoteka Mail sadrži i iznos, IBAN, model, poziv na broj i 2D barkod za uplatu.',
    ],
    ['workingDays' => true, 'timing' => 'Odmah nakon kreiranja u statusu 12; rok u mailu: 2 radna dana.', 'showBankDetails' => true]
);

addScenario(
    $scenarios, $channels, $profiles, $order,
    'S01', 'status', 'Kreirana narudžba', '12 — Rezervacija', 'bank_transfer_b2b',
    'Chipoteka.hr - Ponuda broj %s',
    [
        'Pozdrav,',
        'zahvaljujemo na narudžbi.',
        'Podaci za uplatu nalaze se u nastavku poruke.',
    ],
    [
        'sourceNote' => 'Specifikacija navodi poseban B2B template i predmet, ali ne daje puni B2B tekst. Preview koristi neutralnu poruku bez roka od 48 sati.',
        'warnings'   => ['B2B tijelo poruke nije potpuno definirano u izvornoj matrici — potrebno potvrditi tekst.'],
    ]
);

addScenario(
    $scenarios, $channels, $profiles, $order,
    'S01', 'status', 'Kreirana narudžba', '12 — Rezervacija', 'pickup_bank_transfer',
    'Chipoteka.hr - Ponuda broj %s',
    [
        'Pozdrav,',
        'zahvaljujemo na narudžbi.',
        'Za nastavak obrade potrebno je izvršiti uplatu prema podacima navedenima u ponudi. Narudžbu i pripadajuću robu držimo rezerviranu 2 radna dana. Ako u tom roku ne zaprimimo uplatu, narudžbu ćemo nažalost morati otkazati.',
        'Podaci za uplatu nalaze se u nastavku poruke. Chipoteka Mail sadrži i iznos, IBAN, model, poziv na broj i 2D barkod za uplatu.',
    ],
    ['workingDays' => true, 'timing' => 'Odmah nakon kreiranja u statusu 12; rok u mailu: 2 radna dana.', 'showBankDetails' => true]
);

// STATUS 2 — created/approved.
foreach ($matrixChannels as $channelKey) {
    if ($channelKey === 'cod_standard') {
        $subject = 'Chipoteka.hr - Narudžba broj %s';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je zaprimljena.',
            'Plaćanje ćete izvršiti prilikom preuzimanja paketa. Ako ste u mogućnosti, molimo pripremite točan iznos za plaćanje.',
            'Sad je red na nama. Čim krenemo s obradom narudžbe, poslat ćemo novu obavijest.',
            'Zahvaljujemo na narudžbi.',
        ];
    } elseif ($channelKey === 'cod_boxnow') {
        $subject = 'Chipoteka.hr - Narudžba broj %s';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je zaprimljena.',
            'Plaćanje ćete izvršiti putem poveznice koju ćete dobiti SMS porukom prije preuzimanja paketa.',
            'Sad je red na nama. Čim krenemo s obradom narudžbe, poslat ćemo novu obavijest.',
            'Zahvaljujemo na narudžbi.',
        ];
    } elseif ($channelKey === 'bank_transfer' || $channelKey === 'pickup_bank_transfer') {
        $subject = 'Chipoteka.hr - Uplata za narudžbu broj %s je zaprimljena';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je zaprimljena, a uplata je evidentirana.',
            'Sad je red na nama. Čim krenemo s obradom narudžbe, poslat ćemo novu obavijest.',
            'Zahvaljujemo na narudžbi.',
        ];
    } elseif ($channelKey === 'pickup_pay') {
        $subject = 'Chipoteka.hr - Rezervacija broj %s';
        $body = [
            'Pozdrav,',
            'rezervacija broj %s je zaprimljena.',
            'Iznos za plaćanje je 111,60 € u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
            'Sad je red na nama. Čim krenemo s obradom rezervacije, poslat ćemo novu obavijest.',
            'Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.',
            ['text' => 'Pravo na jednostrani raskid ugovora unutar 14 dana vrijedi isključivo za kupovinu na daljinu. Budući da se preuzimanje i plaćanje vrše u poslovnici, naknadni otkaz ugovora po toj osnovi nije moguć.', 'tone' => 'legal'],
            'Zahvaljujemo na rezervaciji.',
        ];
    } else {
        $subject = 'Chipoteka.hr - Narudžba broj %s';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je zaprimljena, a uplata je evidentirana.',
            'Sad je red na nama. Čim krenemo s obradom narudžbe, poslat ćemo novu obavijest.',
            'Zahvaljujemo na narudžbi.',
        ];
    }

    addScenario($scenarios, $channels, $profiles, $order, 'S02', 'status', 'Kreirana narudžba', '02 — Odobren', $channelKey, $subject, $body);
}

// STATUS 3 — processing.
foreach ($matrixChannels as $channelKey) {
    if ($channelKey === 'pickup_pay') {
        $subject = 'Chipoteka.hr - Rezervacija broj %s se obrađuje';
        $body = [
            'Pozdrav,',
            'rezervacija broj %s se trenutno obrađuje i paket će uskoro biti spreman za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
            'Čim paket bude spreman za preuzimanje, poslat ćemo novu obavijest. Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.',
            ['text' => 'Pravo na jednostrani raskid ugovora unutar 14 dana vrijedi isključivo za kupovinu na daljinu. Budući da se preuzimanje i plaćanje vrše u poslovnici, naknadni otkaz ugovora po toj osnovi nije moguć.', 'tone' => 'legal'],
            'Hvala na strpljenju i razumijevanju.',
        ];
    } elseif ($channels[$channelKey]['kind'] === 'pickup') {
        $subject = 'Chipoteka.hr - Narudžba broj %s se obrađuje';
        $body = [
            'Pozdrav,',
            'narudžba broj %s se trenutno obrađuje i paket će uskoro biti spreman za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
            'Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00. Čim paket bude spreman za preuzimanje, poslat ćemo novu obavijest.',
            'Hvala na strpljenju i razumijevanju.',
        ];
    } else {
        $subject = 'Chipoteka.hr - Narudžba broj %s se obrađuje';
        $body = [
            'Pozdrav,',
            'narudžba broj %s se trenutno obrađuje na našem skladištu i paket će uskoro biti spreman za slanje.',
            'Hvala na strpljenju i razumijevanju.',
        ];
    }

    addScenario($scenarios, $channels, $profiles, $order, 'S03', 'status', '02 — Odobren', '05 — Odobren / komisioniranje', $channelKey, $subject, $body);
}

// STATUS 4 — packed / ready.
foreach ($matrixChannels as $channelKey) {
    if ($channelKey === 'pickup_pay') {
        $subject = 'Chipoteka.hr - Vaš paket je spreman za preuzimanje';
        $body = [
            'Pozdrav,',
            'rezervacija broj %s je spakirana i spremna za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
            'Paket možete preuzeti unutar 4 radna dana, nakon čega će se rezervacija otkazati.',
            'Plaćanje na blagajni iznosi 111,60 €. Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.',
            ['text' => 'Pravo na jednostrani raskid ugovora unutar 14 dana vrijedi isključivo za kupovinu na daljinu. Budući da se preuzimanje i plaćanje vrše u poslovnici, naknadni otkaz ugovora po toj osnovi nije moguć.', 'tone' => 'legal'],
            'Zahvaljujemo na povjerenju.',
        ];
        $options = ['workingDays' => true];
    } elseif ($channels[$channelKey]['kind'] === 'pickup') {
        $subject = 'Chipoteka.hr - Vaš paket je spreman za preuzimanje';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je spakirana i spremna za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
            'Paket možete preuzeti unutar 4 radna dana.',
            'Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.',
            'Zahvaljujemo na povjerenju.',
        ];
        $options = ['workingDays' => true];
    } else {
        $subject = 'Chipoteka.hr - Paket je spreman za prikup od strane dostavne službe';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je spakirana i spremna za prikup od strane dostavne službe.',
            'Čim dostavna služba preuzme paket, poslat ćemo vam obavijest.',
            'Zahvaljujemo na povjerenju.',
        ];
        $options = ['warnings' => ['Izraz „prikup” preuzet je iz specifikacije; preporučena je jezična potvrda.']];
    }

    addScenario($scenarios, $channels, $profiles, $order, 'S04', 'status', '05 — Odobren / komisioniranje', '09 — Nalog spreman za isporuku', $channelKey, $subject, $body, $options);
}

// STATUS 5 — handed to delivery service (delivery channels only).
foreach ($deliveryChannels as $channelKey) {
    $body = [
        'Pozdrav,',
        'narudžba broj %s je spakirana i predana dostavnoj službi.',
        'Uskoro ćete od dostavne službe dobiti obavijest s informacijama za praćenje pošiljke.',
    ];

    if ($channelKey === 'cod_standard') {
        $body[] = 'Podsjećamo da se plaćanje obavlja prilikom preuzimanja paketa. Ako ste u mogućnosti, molimo pripremite točan iznos za plaćanje.';
    } elseif ($channelKey === 'cod_boxnow') {
        $body[] = 'Podsjećamo da se plaćanje obavlja putem poveznice koju ćete dobiti SMS porukom prije preuzimanja paketa.';
    }

    $body[] = [
        'text' => 'Kupac je dužan prilikom preuzimanja pošiljke pregledati njezino pakiranje. Ako su na pakiranju pošiljke vidljiva oštećenja, kupac treba odbiti primitak pošiljke uz napomenu da pošiljka sadrži nedostatak, jer postoji mogućnost da je proizvod unutar takvog pakiranja oštećen. Odbijanjem primitka, dostavna služba će pošiljku vratiti Z-EL d.o.o. Ako kupac preuzme pošiljku koja ima vidljiva vanjska oštećenja na pakiranju ili drugi vidljivi nedostatak proizvoda, dužan je isti dan dostaviti fotografije oštećenja pakiranja i proizvoda na e-mail adresu webnarudzba@chipoteka.hr.',
        'tone' => 'legal',
    ];
    $body[] = [
        'text' => 'Prigovori dostavljeni sljedeći dan ili kasnije neće biti prihvaćeni.',
        'tone' => 'legal',
    ];
    $body[] = 'Zahvaljujemo na povjerenju.';

    addScenario(
        $scenarios, $channels, $profiles, $order,
        'S05', 'status', '09 — Nalog spreman za isporuku', '10 — Otpremljeno', $channelKey,
        'Chipoteka.hr - Narudžba broj %s je predana dostavnoj službi',
        $body
    );
}

// STATUS 6 — cancellation.
foreach ($matrixChannels as $channelKey) {
    if ($channelKey === 'bank_transfer' || $channelKey === 'pickup_bank_transfer') {
        $subject = 'Chipoteka.hr - Narudžba broj %s je otkazana';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je otkazana jer uplata nije zaprimljena u predviđenom roku.',
            'Ako ste uplatu ipak izvršili, a narudžba je otkazana, molimo javite nam se na webnarudzba@chipoteka.hr kako bismo provjerili što se dogodilo.',
            'Ako i dalje želite naručiti proizvode, slobodno nam se javite prije nove narudžbe kako bismo provjerili njihovu dostupnost.',
            'Hvala na razumijevanju.',
        ];
        $options = ['workingDays' => true];
    } elseif ($channelKey === 'pickup_pay') {
        $subject = 'Chipoteka.hr - Rezervacija broj %s je otkazana';
        $body = [
            'Pozdrav,',
            'rezervacija broj %s je otkazana.',
            'Ako imate pitanja ili želite ponovno rezervirati, slobodno nam se javite na webnarudzba@chipoteka.hr.',
            'Hvala na razumijevanju.',
        ];
        $options = [];
    } else {
        $subject = 'Chipoteka.hr - Narudžba broj %s je otkazana';
        $body = [
            'Pozdrav,',
            'narudžba broj %s je otkazana.',
            'Ako imate pitanja ili želite ponovno naručiti, slobodno nam se javite na webnarudzba@chipoteka.hr.',
            'Hvala na razumijevanju.',
        ];
        $options = [];
    }

    addScenario($scenarios, $channels, $profiles, $order, 'S06', 'status', 'Bilo koji status', '04 — Otkazivanje / storno', $channelKey, $subject, $body, $options);
}

// V1 — unpaid after 24 h.
addScenario(
    $scenarios, $channels, $profiles, $order,
    'V01', 'time', '12 — Rezervacija', 'Podsjetnik nakon 24 sata', 'bank_transfer',
    'Chipoteka.hr - Uplata za narudžbu broj %s nije evidentirana',
    [
        'Pozdrav,',
        'za narudžbu broj %s još uvijek nemamo evidentiranu uplatu.',
        'Ako ste uplatu već izvršili, slobodno zanemarite ovu poruku jer evidentiranje uplate može potrajati. Ako uplata još nije izvršena, podatke za plaćanje možete pronaći u potvrdi narudžbe koju smo vam poslali.',
        'Hvala na razumijevanju.',
    ],
    ['timing' => '24 sata u statusu 12; globalna napomena traži uvažavanje vikenda i HR neradnih dana.', 'workingDays' => true]
);

// V2 — unpaid after 48 h.
addScenario(
    $scenarios, $channels, $profiles, $order,
    'V02', 'time', '12 — Rezervacija', 'Uplata nije evidentirana nakon 48 sati', 'bank_transfer',
    'Chipoteka.hr - Uplata za narudžbu broj %s nije evidentirana',
    [
        'Pozdrav,',
        'za narudžbu broj %s još uvijek nemamo evidentiranu uplatu.',
        'Ako ste uplatu već izvršili, molimo javite nam se na webnarudzba@chipoteka.hr kako bismo provjerili uplatu.',
        'Ako uplata nije izvršena, a želite nastaviti s narudžbom, također nam se javite kako bismo prije uplate provjerili dostupnost proizvoda. Ako ne zaprimimo uplatu ili potvrdu da želite nastaviti s narudžbom, narudžba će biti otkazana.',
        'Hvala na razumijevanju.',
    ],
    ['timing' => '48 sati u statusu 12; globalna napomena traži uvažavanje vikenda i HR neradnih dana.', 'workingDays' => true]
);

addScenario(
    $scenarios, $channels, $profiles, $order,
    'V02', 'time', '12 — Rezervacija', 'Uplata nije evidentirana nakon 48 sati', 'pickup_bank_transfer',
    'Chipoteka.hr - Uplata za narudžbu broj %s nije evidentirana',
    [
        'Pozdrav,',
        'za narudžbu broj %s još uvijek nemamo evidentiranu uplatu.',
        'Ako ste uplatu već izvršili, slobodno zanemarite ovu poruku jer evidentiranje uplate može potrajati. Ako uplata još nije izvršena, podatke za plaćanje možete pronaći u potvrdi narudžbe koju smo vam poslali.',
        'Hvala na razumijevanju.',
    ],
    [
        'timing'      => '48 sati u statusu 12; globalna napomena traži uvažavanje vikenda i HR neradnih dana.',
        'workingDays' => true,
        'warnings'    => ['Legenda propisuje otkazivanje preuzimanja uz virman nakon 48 sati, dok V9 predviđa još jedan podsjetnik nakon 96 sati. Potrebno je potvrditi koji rok vrijedi.'],
    ]
);

// V3 — processing has not started after 48 h.
foreach ($matrixChannels as $channelKey) {
    $reservation = $channelKey === 'pickup_pay';
    $noun        = $reservation ? 'rezervacija' : 'narudžba';
    $nounAcc     = $reservation ? 'rezervacije' : 'narudžbe';
    $subject     = 'Chipoteka.hr - Obrada ' . $nounAcc . ' broj %s još nije započela';
    $body        = [
        'Pozdrav,',
        $noun . ' broj %s je zaprimljena, ali obrada još nije započela.',
        'Čim krenemo s obradom ' . $nounAcc . ', poslat ćemo novu obavijest.',
        'Hvala na strpljenju i razumijevanju.',
    ];

    addScenario($scenarios, $channels, $profiles, $order, 'V03', 'time', '02 — Odobren', 'Obrada nije započela nakon 48 sati', $channelKey, $subject, $body, [
        'timing' => '48 sati u statusu 02; globalna napomena traži uvažavanje vikenda i HR neradnih dana.',
        'workingDays' => true,
    ]);
}

// V4 — processing has not started after 72 h.
foreach ($matrixChannels as $channelKey) {
    $reservation = $channelKey === 'pickup_pay';
    $noun        = $reservation ? 'rezervacija' : 'narudžba';
    $nounAcc     = $reservation ? 'rezervacije' : 'narudžbe';
    $subject     = 'Chipoteka.hr - Ispričavamo se zbog kašnjenja ' . $nounAcc . ' broj %s';
    $body        = [
        'Pozdrav,',
        $noun . ' broj %s još uvijek nije krenula u obradu.',
        'Svjesni smo da čekanje traje dulje nego što bi trebalo i ispričavamo se zbog kašnjenja. Radimo na tome da ' . $noun . ' što prije krene u obradu.',
        'Ako trebate dodatne informacije, slobodno nam se javite na webnarudzba@chipoteka.hr.',
        'Hvala na strpljenju i razumijevanju.',
    ];

    addScenario($scenarios, $channels, $profiles, $order, 'V04', 'time', '02 — Odobren', 'Obrada nije započela nakon 72 sata', $channelKey, $subject, $body, [
        'timing' => '72 sata u statusu 02; globalna napomena traži uvažavanje vikenda i HR neradnih dana.',
        'workingDays' => true,
    ]);
}

// V5 — processing takes longer after 48 h in status 05.
foreach ($matrixChannels as $channelKey) {
    $reservation = $channelKey === 'pickup_pay';
    $noun        = $reservation ? 'rezervacija' : 'narudžba';
    $nounGen     = $reservation ? 'rezervacije' : 'narudžbe';
    $subject     = 'Chipoteka.hr - Priprema ' . $nounGen . ' broj %s traje nešto dulje';
    $destination = $reservation ? 'pripremom rezervacije za preuzimanje u poslovnici' : 'pakiranjem narudžbe za slanje';
    $body        = [
        'Pozdrav,',
        $noun . ' broj %s trenutno se obrađuje na našem skladištu, ali obrada traje nešto dulje nego što smo očekivali.',
        'Radimo na tome da što prije završimo s ' . $destination . '.',
        'Ako trebate dodatne informacije, slobodno nam se javite na webnarudzba@chipoteka.hr.',
        'Hvala na strpljenju i razumijevanju.',
    ];

    addScenario($scenarios, $channels, $profiles, $order, 'V05', 'time', '05 — Odobren / komisioniranje', 'Obrada traje dulje od 48 sati', $channelKey, $subject, $body, [
        'timing' => '48 sati u statusu 05; globalna napomena traži uvažavanje vikenda i HR neradnih dana.',
        'workingDays' => true,
    ]);
}

// V6 — seven days after delivery.
foreach ($matrixChannels as $channelKey) {
    $reservation = $channelKey === 'pickup_pay';
    $noun        = $reservation ? 'rezervacija' : 'narudžba';
    $verb        = $reservation ? 'preuzeta' : 'isporučena';
    $body        = [
        'Pozdrav,',
        $noun . ' broj %s je ' . $verb . '.',
        'Nadamo se da je nova oprema već pronašla svoje mjesto i da ste zadovoljni kupnjom.',
        'Hvala na povjerenju.',
    ];

    addScenario($scenarios, $channels, $profiles, $order, 'V06', 'time', '10 — prema tekstu specifikacije', 'Zahvala nakon 168 sati', $channelKey,
        'Chipoteka.hr - Tu smo ako nešto zatreba', $body, [
            'timing'      => '168 sati nakon isporuke; šalje se samo zahvala, uz otvorenu mogućnost naknadne ankete.',
            'workingDays' => true,
            'warnings'    => ['Specifikacija za V6 naziva status 10 „Isporučeno”, dok statusna tablica status 10 naziva „Otpremljeno”. Potrebno potvrditi stvarni okidač (10 ili 11).'],
        ]
    );
}

// V7 — pickup reminder after 48 h.
foreach ($pickupChannels as $channelKey) {
    $payInStore = $channelKey === 'pickup_pay';
    $noun       = $payInStore ? 'rezervacija' : 'narudžba';
    $body       = [
        'Pozdrav,',
        $noun . ' broj %s je spakirana i spremna za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
        ['text' => 'Paket možete preuzeti unutar 2 radna dana' . ($payInStore ? ', nakon čega će se rezervacija otkazati.' : '.'), 'tone' => 'emphasis'],
    ];
    if ($payInStore) {
        $body[] = ['text' => 'Plaćanje na blagajni iznosi 111,60 €.', 'tone' => 'emphasis'];
    }
    $body[] = ['text' => 'Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.', 'tone' => 'emphasis'];
    if ($payInStore) {
        $body[] = ['text' => 'Pravo na jednostrani raskid ugovora unutar 14 dana vrijedi isključivo za kupovinu na daljinu. Budući da se preuzimanje i plaćanje vrše u poslovnici, naknadni otkaz ugovora po toj osnovi nije moguć.', 'tone' => 'legal'];
    }
    $body[] = 'Zahvaljujemo na povjerenju.';

    addScenario($scenarios, $channels, $profiles, $order, 'V07', 'time', '09 — prema tekstu specifikacije', 'Podsjetnik za preuzimanje nakon 48 sati', $channelKey,
        'Chipoteka.hr - Vaš paket je spreman za preuzimanje', $body, [
            'timing'      => '48 sati čekanja na preuzimanje; rok: još 2 radna dana.',
            'workingDays' => true,
            'warnings'    => ['Specifikacija V7 status 09 opisuje kao „Rezervacija”, dok statusna tablica 09 opisuje kao „Nalog spreman za isporuku”. Potrebno potvrditi mapiranje.'],
        ]
    );
}

// V8 — final pickup reminder after 96 h.
foreach ($pickupChannels as $channelKey) {
    $payInStore = $channelKey === 'pickup_pay';
    $noun       = $payInStore ? 'rezervacija' : 'narudžba';
    $body       = [
        'Pozdrav,',
        $noun . ' broj %s je spakirana i spremna za preuzimanje u poslovnici Chipoteka Zagreb — Ulica grada Vukovara 269 D.',
        ['text' => 'Paket možete preuzeti danas, ' . $order['pickup_date'] . ($payInStore ? ', nakon čega će se rezervacija otkazati.' : '.'), 'tone' => 'emphasis'],
    ];
    if ($payInStore) {
        $body[] = ['text' => 'Plaćanje na blagajni iznosi 111,60 €.', 'tone' => 'emphasis'];
    }
    $body[] = ['text' => 'Radno vrijeme poslovnice: pon–pet 08:00–20:00, sub 09:00–14:00.', 'tone' => 'emphasis'];
    if ($payInStore) {
        $body[] = ['text' => 'Pravo na jednostrani raskid ugovora unutar 14 dana vrijedi isključivo za kupovinu na daljinu. Budući da se preuzimanje i plaćanje vrše u poslovnici, naknadni otkaz ugovora po toj osnovi nije moguć.', 'tone' => 'legal'];
    }
    $body[] = 'Zahvaljujemo na povjerenju.';

    addScenario($scenarios, $channels, $profiles, $order, 'V08', 'time', '09 — prema tekstu specifikacije', 'Zadnji podsjetnik za preuzimanje nakon 96 sati', $channelKey,
        'Chipoteka.hr - Vaš paket je spreman za preuzimanje', $body, [
            'timing'      => '96 sati čekanja na preuzimanje; zadnji dan preuzimanja prikazan je konkretnim datumom.',
            'workingDays' => true,
            'warnings'    => ['Specifikacija V8 status 09 opisuje kao „Rezervacija”, dok statusna tablica 09 opisuje kao „Nalog spreman za isporuku”. Potrebno potvrditi mapiranje.'],
        ]
    );
}

// V9 — unpaid pickup order after 96 h.
addScenario(
    $scenarios, $channels, $profiles, $order,
    'V09', 'time', '12 — Rezervacija', 'Uplata nije evidentirana nakon 96 sati', 'pickup_bank_transfer',
    'Chipoteka.hr - Uplata za narudžbu broj %s nije evidentirana',
    [
        'Pozdrav,',
        'za narudžbu broj %s još uvijek nemamo evidentiranu uplatu.',
        'Ako ste uplatu već izvršili, molimo javite nam se na webnarudzba@chipoteka.hr kako bismo provjerili uplatu.',
        'Ako uplata nije izvršena, a želite nastaviti s narudžbom, također nam se javite kako bismo prije uplate provjerili dostupnost proizvoda. Ako ne zaprimimo uplatu ili potvrdu da želite nastaviti s narudžbom, narudžba će biti otkazana.',
        'Hvala na razumijevanju.',
    ],
    [
        'timing'      => '96 sati u statusu 12; globalna napomena traži uvažavanje vikenda i HR neradnih dana.',
        'workingDays' => true,
        'warnings'    => ['Legenda propisuje otkazivanje preuzimanja uz virman nakon 48 sati, ali V9 se šalje tek nakon 96 sati. Ako otkazivanje nakon 48 sati vrijedi, V9 se ne može poslati.'],
    ]
);

if (count($scenarios) !== 73) {
    throw new RuntimeException('Expected 73 scenarios, generated ' . count($scenarios) . '.');
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderEmail(array $scenario, array $order, \Twig\Environment $twig): string
{
    $customer  = $scenario['customer'];
    $isPickup  = $scenario['kind'] === 'pickup';
    $isB2b     = $scenario['customerType'] === 'B2B';
    $nameParts = explode(' ', $customer['customer'], 2);
    $firstName = $nameParts[0];
    $lastName  = $nameParts[1] ?? '';

    $paragraphStyle = 'color: inherit;font-family: Arial, Helvetica, sans-serif;margin-top: 0px;margin-bottom: 15px;word-break: break-word;font-size: 16px;line-height: 26px;';
    $mailText = '';

    foreach ($scenario['paragraphs'] as $paragraph) {
        $text = escapeHtml($paragraph['text']);
        $text = str_replace(
            'webnarudzba@chipoteka.hr',
            '<a style="color:#E31318;text-decoration:none;" href="mailto:webnarudzba@chipoteka.hr">webnarudzba@chipoteka.hr</a>',
            $text
        );

        if ($paragraph['tone'] === 'legal') {
            $text = '<em>' . $text . '</em>';
        } elseif ($paragraph['tone'] === 'emphasis') {
            $text = '<strong><em>' . $text . '</em></strong>';
        }

        $mailText .= '<p style="' . $paragraphStyle . '">' . $text . '</p>';
    }

    // Every new status/timed text except the initial offer ends with the
    // Chipoteka signature in the supplied matrix.
    if ($scenario['code'] !== 'S01') {
        $mailText .= '<p style="' . $paragraphStyle . '">Chipoteka</p>';
    }

    $productPrices = [49.90, 18.40, 24.90];
    $products = [];
    foreach ($order['products'] as $index => $product) {
        $products[] = [
            'image'    => '../assets/placeholder.png',
            'name'     => $product['name'],
            'model'    => $product['sku'],
            'quantity' => $product['quantity'],
            'price'    => $productPrices[$index],
        ];
    }

    $shippingValue = $isPickup ? 0.00 : 5.17;
    $totalValue    = $isPickup ? 111.60 : 116.77;
    $context = [
        'mail_title'         => $scenario['subject'],
        'mail_text'          => $mailText,
        'mail_logo'          => '../assets/chipoteka-hd.png',
        // Full order data is intentionally shown in every preview so address
        // concatenation and responsive layout can be checked in all variants.
        'mail_data'          => '1',
        'order_id'           => $order['number'],
        'store_url'          => '../assets/',
        'payment_code'       => in_array($scenario['channelKey'], ['bank_transfer', 'bank_transfer_b2b', 'pickup_bank_transfer'], true) ? 'bank_transfer' : $scenario['channelKey'],
        'b2b'                => $scenario['showBankDetails'] ? '2' : '0',
        'is_b2b'             => $isB2b ? '1' : '0',
        'total'              => $totalValue,
        'mail_poziv_na_broj' => $order['reference'],
        'payment_firstname'  => $firstName,
        'payment_lastname'   => $lastName,
        'payment_address_1'  => $customer['billing']['line'],
        'payment_postcode'   => $customer['billing']['postcode'],
        'payment_city'       => $customer['billing']['city'],
        'payment_country'    => $customer['billing']['country'],
        'shipping_firstname' => $firstName,
        'shipping_lastname'  => $lastName,
        'shipping_address_1' => $customer['shipping']['line'],
        'shipping_postcode'  => $customer['shipping']['postcode'],
        'shipping_city'      => $customer['shipping']['city'],
        'shipping_country'   => $customer['shipping']['country'],
        'telephone'          => $customer['phone'],
        'email'              => $customer['email'],
        'tvrtka'             => $customer['company'],
        'oib'                => $customer['oib'],
        'payment_method'     => $scenario['payment'],
        'shipping_method'    => $scenario['fulfilment'],
        'products'           => $products,
        'totals'             => [
            ['title' => 'Međuzbroj', 'value' => 111.60],
            ['title' => 'Dostava', 'value' => $shippingValue],
            ['title' => 'Ukupno', 'value' => $totalValue],
        ],
        'b2b_products'       => [],
    ];

    $html = $twig->render('mail/mail.twig', $context);

    // Keep every standalone preview out of search indexes as well as the
    // navigation page that embeds it.
    $html = str_replace(
        '<head>',
        "<head>\n<meta name=\"robots\" content=\"noindex,nofollow,noarchive\">",
        $html
    );

    // Keep the exact live markup while using local copies of image assets.
    $html = str_replace(
        ['https://www.chipoteka.hr/image/facebook_white.png', '../assets/image/tmp/' . $order['number'] . '.png'],
        ['../assets/facebook_white.png', '../assets/test-payment-barcode.svg'],
        $html
    );

    return $html;
}

function buildIndexHtml(int $scenarioCount): string
{
    return '<!doctype html>
<html lang="hr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Chipoteka · pregled mailova kupcima</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="compact-header">
  <div class="compact-brand">CHIPO<span>TEKA</span></div>
  <div class="compact-title">Pregled mailova <span>statusni i vremenski</span></div>
  <div class="compact-meta"><span>' . $scenarioCount . ' scenarija</span><span>testni podaci</span><span>bez slanja</span></div>
</header>

<details class="spec-issues">
  <summary>Otvorena pitanja i kontradikcije za potvrdu prije programiranja</summary>
  <ul>
    <li><strong>V6:</strong> tekst navodi 168 sati nakon ulaska u status <em>10 — Isporučeno</em>, dok statusna tablica status 10 naziva <em>Otpremljeno</em>.</li>
    <li><strong>V7/V8:</strong> status 09 opisan je kao <em>Rezervacija</em>, a u statusnoj tablici kao <em>Nalog spreman za isporuku</em>.</li>
    <li><strong>Virman / preuzimanje:</strong> legenda propisuje otkazivanje nakon 48 sati, ali V9 predviđa podsjetnik tek nakon 96 sati.</li>
    <li><strong>Računanje vremena:</strong> pragovi su zadani u satima, uz zaseban zahtjev da se preskoče vikendi i hrvatski neradni dani; potrebno je potvrditi točan način računanja.</li>
    <li><strong>B2B početni virman:</strong> zadan je predmet i uvjet, ali nije naveden potpuni tekst tijela poruke.</li>
    <li><strong>S04:</strong> izraz „prikup od strane dostavne službe” izgleda kao tipfeler i ostavljen je za jezičnu potvrdu.</li>
    <li><strong>Postojeći live predložak:</strong> podnožje spaja adresu bez razmaka i još prikazuje ©2021; preview to namjerno ne mijenja.</li>
  </ul>
</details>

<main class="workspace">
  <aside class="sidebar">
    <div class="sidebar-head"><h2>Scenariji</h2><span id="result-count"></span></div>
    <div class="segmented" role="group" aria-label="Vrsta maila">
      <button class="type-filter active" data-type="all">Svi</button>
      <button class="type-filter" data-type="status">Statusni</button>
      <button class="type-filter" data-type="time">Vremenski</button>
    </div>
    <label class="field"><span>Pretraži</span><input id="search" type="search" placeholder="status, predmet, kanal…"></label>
    <label class="field"><span>Način plaćanja / isporuke</span><select id="channel-filter"><option value="all">Svi kanali</option></select></label>
    <label class="field"><span>Status ili vremensko pravilo</span><select id="code-filter"><option value="all">Sva pravila</option></select></label>
    <div id="scenario-list" class="scenario-list" aria-live="polite"></div>
  </aside>

  <section class="review-panel">
    <div class="review-toolbar">
      <div><span class="scenario-code" id="scenario-code"></span><h2 id="scenario-title"></h2></div>
      <div class="toolbar-actions">
        <div class="viewport-switch"><button class="viewport active" data-viewport="desktop" aria-label="Desktop prikaz">Desktop</button><button class="viewport" data-viewport="mobile" aria-label="Mobilni prikaz">Mobitel</button></div>
        <a id="open-mail" class="open-mail" target="_blank" rel="noopener">Otvori HTML</a>
      </div>
    </div>

    <div class="detail-grid">
      <div><span>Okidač</span><strong id="trigger"></strong></div>
      <div><span>Kanal</span><strong id="channel"></strong></div>
      <div><span>Kupac</span><strong id="buyer"></strong></div>
      <div><span>Predmet</span><strong id="subject"></strong></div>
    </div>

    <div id="timing-note" class="timing-note" hidden></div>
    <div id="warning-list" class="warning-list" hidden></div>

    <details class="qa-details">
      <summary>Kontrolni podaci kupca i adrese</summary>
      <div class="qa-grid">
        <div><span>Ulica za račun</span><strong id="billing-street"></strong></div>
        <div><span>Kućni broj</span><strong id="billing-number"></strong></div>
        <div><span>Spojeni prikaz</span><strong id="billing-line"></strong></div>
        <div><span>Dostava / poslovnica</span><strong id="shipping-line"></strong></div>
        <div><span>Kontakt</span><strong id="contact"></strong></div>
        <div><span>Profil</span><strong id="profile"></strong></div>
      </div>
    </details>

    <div id="preview-frame-shell" class="preview-frame-shell desktop">
      <iframe id="mail-frame" title="Pregled odabranog maila"></iframe>
    </div>
  </section>
</main>

<footer class="page-footer"><span>Chipoteka · testni pregled mailova</span><span>Generirano lokalno · bez slanja mailova i bez promjena produkcije</span></footer>

<script src="assets/scenarios.js"></script>
<script src="assets/app.js"></script>
</body>
</html>';
}

$styles = <<<'CSS'
:root{--red:#e31318;--ink:#1f2023;--muted:#676b72;--line:#e1e3e6;--panel:#fff;--soft:#f4f5f6;--warning:#fff4e5;--shadow:0 18px 45px rgba(26,28,32,.10)}*{box-sizing:border-box}html{background:#edeff1}body{margin:0;color:var(--ink);font-family:Inter,ui-sans-serif,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#edeff1}.page-header{padding:28px clamp(22px,4vw,64px) 34px;background:#17181b;color:#fff;display:grid;grid-template-columns:1fr minmax(420px,1.8fr) auto;gap:34px;align-items:end;border-bottom:5px solid var(--red)}.brand-row{align-self:start}.brand{font-size:29px;font-weight:900;letter-spacing:-1.5px;color:var(--red)}.brand span{color:#fff}.environment{display:inline-block;margin-top:9px;padding:5px 8px;border:1px solid #44474d;border-radius:999px;color:#b8bbc0;font-size:11px;text-transform:uppercase;letter-spacing:.09em}.header-copy .kicker{margin:0 0 8px;color:#ff8f93;font-size:12px;text-transform:uppercase;letter-spacing:.14em;font-weight:700}.header-copy h1{font-size:clamp(26px,3vw,42px);line-height:1.08;letter-spacing:-.03em;margin:0 0 12px}.header-copy p:last-child{max-width:780px;margin:0;color:#c7c9cd;line-height:1.55}.header-stats{display:flex;gap:1px;background:#34363b;border:1px solid #34363b}.header-stats div{min-width:108px;background:#24262a;padding:18px 16px}.header-stats strong,.header-stats span{display:block}.header-stats strong{font-size:25px;color:#fff}.header-stats span{margin-top:4px;color:#aeb1b6;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.global-notes{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#d9dcdf;border-bottom:1px solid #d9dcdf}.global-notes div{background:#fff;padding:17px clamp(20px,3vw,42px);display:flex;gap:12px;align-items:flex-start}.global-notes strong{white-space:nowrap;color:var(--red);font-size:13px}.global-notes span{color:#5f6369;font-size:12px;line-height:1.5}.spec-issues{margin:18px auto 0;max-width:1640px;background:#fff8ec;border:1px solid #edcc95;border-radius:8px;color:#654218}.spec-issues summary{cursor:pointer;padding:14px 18px;font-weight:800;font-size:13px}.spec-issues ul{margin:0;padding:0 34px 17px 39px}.spec-issues li{margin:7px 0;font-size:12px;line-height:1.5}.workspace{display:grid;grid-template-columns:360px minmax(0,1fr);max-width:1700px;margin:0 auto;min-height:calc(100vh - 290px)}.sidebar{background:#f7f8f9;border-right:1px solid #d7d9dc;padding:24px 18px 30px;position:sticky;top:0;height:100vh;overflow:auto}.sidebar-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}.sidebar-head h2{margin:0;font-size:20px}.sidebar-head span{font-size:12px;color:var(--muted)}.segmented{display:grid;grid-template-columns:repeat(3,1fr);background:#e7e9eb;padding:3px;border-radius:8px;margin-bottom:18px}.segmented button,.viewport-switch button{appearance:none;border:0;background:transparent;padding:9px 8px;border-radius:6px;color:#5b5f65;font-weight:700;cursor:pointer}.segmented button.active,.viewport-switch button.active{background:#fff;color:var(--ink);box-shadow:0 2px 8px rgba(0,0,0,.08)}.field{display:block;margin-bottom:12px}.field span{display:block;margin-bottom:6px;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#6b6f75;font-weight:700}.field input,.field select{width:100%;border:1px solid #d7d9dc;background:#fff;border-radius:7px;padding:11px 12px;font:inherit;color:var(--ink);outline:none}.field input:focus,.field select:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(227,19,24,.09)}.scenario-list{display:flex;flex-direction:column;gap:7px;margin-top:18px}.scenario-card{appearance:none;width:100%;text-align:left;border:1px solid #dde0e3;background:#fff;border-radius:9px;padding:13px;cursor:pointer;transition:.16s ease}.scenario-card:hover{transform:translateY(-1px);border-color:#bfc3c8;box-shadow:0 6px 15px rgba(0,0,0,.06)}.scenario-card.active{border-color:var(--red);box-shadow:0 0 0 2px rgba(227,19,24,.10)}.scenario-card .top{display:flex;justify-content:space-between;gap:10px;align-items:center}.scenario-card .code{font-size:11px;color:#fff;background:#36383d;padding:4px 6px;border-radius:4px;font-weight:800}.scenario-card .type{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#888c92}.scenario-card strong{display:block;margin-top:9px;font-size:13px;line-height:1.35}.scenario-card small{display:block;margin-top:6px;color:#73777d;line-height:1.35}.scenario-card .warn-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#e38b13;margin-left:5px}.empty-state{padding:25px 10px;text-align:center;color:#777;font-size:13px}.review-panel{padding:28px clamp(20px,3vw,48px) 50px;min-width:0}.review-toolbar{display:flex;justify-content:space-between;gap:24px;align-items:flex-end}.scenario-code{font-size:11px;color:var(--red);font-weight:900;letter-spacing:.12em;text-transform:uppercase}.review-toolbar h2{margin:5px 0 0;font-size:clamp(22px,2.2vw,32px);letter-spacing:-.025em}.toolbar-actions{display:flex;gap:10px;align-items:center}.viewport-switch{display:flex;background:#dfe2e5;padding:3px;border-radius:8px}.viewport-switch button{padding:8px 12px}.open-mail{display:inline-flex;align-items:center;padding:10px 13px;background:var(--ink);color:#fff;text-decoration:none;border-radius:7px;font-size:13px;font-weight:700}.detail-grid{display:grid;grid-template-columns:1.1fr 1fr .9fr 1.7fr;gap:1px;background:#dfe1e4;border:1px solid #dfe1e4;margin:22px 0 0}.detail-grid>div{background:#fff;padding:14px}.detail-grid span,.qa-grid span{display:block;color:#85898f;font-size:10px;text-transform:uppercase;letter-spacing:.09em;margin-bottom:6px}.detail-grid strong,.qa-grid strong{display:block;font-size:12px;line-height:1.45;overflow-wrap:anywhere}.timing-note,.warning-list{margin-top:12px;padding:13px 15px;border-radius:7px;font-size:12px;line-height:1.5}.timing-note{background:#edf5ff;color:#254c73;border:1px solid #d2e4f8}.warning-list{background:var(--warning);color:#714415;border:1px solid #efd2aa}.warning-list ul{margin:0;padding-left:18px}.qa-details{margin-top:12px;background:#fff;border:1px solid #dfe1e4;border-radius:7px}.qa-details summary{cursor:pointer;padding:13px 15px;font-size:12px;font-weight:800}.qa-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#e4e5e7;border-top:1px solid #e4e5e7}.qa-grid>div{padding:13px 15px;background:#fafafa}.preview-frame-shell{margin:24px auto 0;background:#d9dcdf;padding:18px;border-radius:12px;box-shadow:var(--shadow);transition:max-width .25s ease}.preview-frame-shell.desktop{max-width:900px}.preview-frame-shell.mobile{max-width:430px;padding:12px}.preview-frame-shell iframe{display:block;width:100%;height:1120px;border:0;background:#f5f5f5;border-radius:5px}.page-footer{display:flex;justify-content:space-between;padding:18px clamp(22px,4vw,64px);background:#17181b;color:#8f9399;font-size:11px}.page-footer span:first-child{color:#fff;font-weight:700}@media(max-width:1100px){.page-header{grid-template-columns:1fr 2fr}.header-stats{grid-column:1/-1}.workspace{grid-template-columns:320px minmax(0,1fr)}.detail-grid{grid-template-columns:repeat(2,1fr)}.global-notes{grid-template-columns:1fr}.global-notes div{padding:13px 24px}.qa-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:760px){.page-header{display:block;padding:22px}.header-copy{margin-top:24px}.header-stats{margin-top:22px;overflow:auto}.spec-issues{margin:14px}.workspace{display:block}.sidebar{position:static;height:auto;border-right:0;border-bottom:1px solid #d7d9dc}.scenario-list{max-height:430px;overflow:auto}.review-panel{padding:24px 14px 36px}.review-toolbar{display:block}.toolbar-actions{margin-top:16px;justify-content:space-between}.detail-grid,.qa-grid{grid-template-columns:1fr}.preview-frame-shell{padding:8px;margin-top:16px}.preview-frame-shell iframe{height:900px}.page-footer{display:block}.page-footer span{display:block;margin:5px 0}}
CSS;

$styles .= <<<'CSS'

/* Compact review shell: keep the email itself above the fold. */
.compact-header{min-height:56px;padding:0 24px;display:flex;align-items:center;gap:22px;background:#17181b;color:#fff;border-bottom:3px solid var(--red)}
.compact-brand{font-size:22px;font-weight:900;letter-spacing:-1.1px;color:var(--red);white-space:nowrap}
.compact-brand span{color:#fff}
.compact-title{font-size:16px;font-weight:800}
.compact-title span{margin-left:7px;color:#92969c;font-size:12px;font-weight:500}
.compact-meta{margin-left:auto;display:flex;align-items:center;gap:8px}
.compact-meta span{padding:5px 8px;border:1px solid #3b3e43;border-radius:999px;color:#b9bcc1;font-size:10px;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
.spec-issues{margin:0;max-width:none;border-width:0 0 1px;border-radius:0;background:#fff8ec}
.spec-issues summary{padding:10px 24px}
.spec-issues ul{padding-bottom:12px}
.workspace{max-width:none;margin:0;min-height:calc(100vh - 99px);align-items:start}
.sidebar{padding:16px 16px 24px;height:calc(100vh - 56px)}
.sidebar-head{margin-bottom:10px}
.segmented{margin-bottom:12px}
.field{margin-bottom:9px}
.scenario-list{margin-top:12px}
.review-panel{padding:16px 24px 28px}
.review-toolbar{align-items:center}
.review-toolbar h2{margin-top:2px;font-size:26px}
.detail-grid{margin-top:12px}
.detail-grid>div{padding:10px 12px}
.detail-grid span,.qa-grid span{margin-bottom:4px}
.timing-note,.warning-list{margin-top:8px;padding:8px 12px}
.qa-details{margin-top:8px}
.qa-details summary{padding:9px 12px}
.preview-frame-shell{margin-top:12px;padding:10px;border-radius:7px;box-shadow:none}
.preview-frame-shell iframe{height:calc(100vh - 255px);min-height:720px}
.page-footer{display:none}
@media(max-width:760px){.compact-header{padding:10px 14px;gap:10px;flex-wrap:wrap}.compact-title span{display:none}.compact-meta{width:100%;margin-left:0}.compact-meta span{font-size:9px}.spec-issues summary{padding:10px 14px}.sidebar{height:auto;padding:14px}.review-panel{padding:14px}.preview-frame-shell iframe{height:780px;min-height:0}}
CSS;

$appJs = <<<'JS'
(function(){
  'use strict';
  const scenarios = window.MAIL_PREVIEW_SCENARIOS || [];
  const state = {type:'all', channel:'all', code:'all', search:'', selected:null};
  const $ = (id) => document.getElementById(id);
  const list = $('scenario-list');
  const channelFilter = $('channel-filter');
  const codeFilter = $('code-filter');

  function uniqueSorted(values){return Array.from(new Set(values)).sort((a,b)=>a.localeCompare(b,'hr'));}
  function option(value,label){const el=document.createElement('option');el.value=value;el.textContent=label;return el;}

  uniqueSorted(scenarios.map(s=>s.channel)).forEach(label=>channelFilter.appendChild(option(label,label)));
  const codes = uniqueSorted(scenarios.map(s=>s.code));
  codes.forEach(code=>{const first=scenarios.find(s=>s.code===code);codeFilter.appendChild(option(code,code+' · '+first.to));});

  function filtered(){
    const needle=state.search.trim().toLocaleLowerCase('hr');
    return scenarios.filter(s=>{
      if(state.type!=='all'&&s.type!==state.type)return false;
      if(state.channel!=='all'&&s.channel!==state.channel)return false;
      if(state.code!=='all'&&s.code!==state.code)return false;
      if(!needle)return true;
      return [s.code,s.from,s.to,s.channel,s.subject,s.customer.customer,s.payment,s.fulfilment].join(' ').toLocaleLowerCase('hr').includes(needle);
    });
  }

  function renderList(){
    const rows=filtered();
    $('result-count').textContent=rows.length+' / '+scenarios.length;
    list.replaceChildren();
    if(!rows.length){const empty=document.createElement('div');empty.className='empty-state';empty.textContent='Nema scenarija za odabrane filtre.';list.appendChild(empty);return;}
    if(!state.selected||!rows.some(s=>s.id===state.selected)){state.selected=rows[0].id;}
    rows.forEach(s=>{
      const button=document.createElement('button');button.type='button';button.className='scenario-card'+(s.id===state.selected?' active':'');button.dataset.id=s.id;
      const top=document.createElement('div');top.className='top';
      const code=document.createElement('span');code.className='code';code.textContent=s.code;
      const type=document.createElement('span');type.className='type';type.textContent=s.type==='status'?'Statusni':'Vremenski';
      if(s.warnings.length){const dot=document.createElement('i');dot.className='warn-dot';dot.title='Scenarij ima napomenu za potvrdu';type.appendChild(dot);}
      top.append(code,type);
      const strong=document.createElement('strong');strong.textContent=s.channelShort;
      const small=document.createElement('small');small.textContent=s.from+' → '+s.to;
      button.append(top,strong,small);
      button.addEventListener('click',()=>{state.selected=s.id;renderList();renderSelected();});
      list.appendChild(button);
    });
    renderSelected();
  }

  function setText(id,value){$(id).textContent=value||'—';}
  function renderSelected(){
    const s=scenarios.find(item=>item.id===state.selected)||filtered()[0];
    if(!s)return;
    state.selected=s.id;
    setText('scenario-code',s.code+' · '+(s.type==='status'?'statusni mail':'vremenski mail'));
    setText('scenario-title',s.channelShort);
    setText('trigger',s.from+' → '+s.to);
    setText('channel',s.channel);
    setText('buyer',s.customer.customer+(s.customer.company?' · '+s.customer.company:''));
    setText('subject',s.subject);
    setText('billing-street',s.customer.billing.street);
    setText('billing-number',s.customer.billing.number);
    setText('billing-line',s.customer.billing.line);
    setText('shipping-line',s.customer.shipping.line+', '+s.customer.shipping.postcode+' '+s.customer.shipping.city);
    setText('contact',s.customer.phone+' · '+s.customer.email);
    setText('profile',s.profileLabel);
    const timing=$('timing-note');
    if(s.timing){timing.hidden=false;timing.textContent=(s.workingDays?'Radni dani / rok: ':'Vrijeme: ')+s.timing;}else{timing.hidden=true;timing.textContent='';}
    const warning=$('warning-list');
    const notes=[...(s.warnings||[])];if(s.sourceNote)notes.push(s.sourceNote);
    if(notes.length){warning.hidden=false;warning.innerHTML='<ul>'+notes.map(n=>'<li>'+escapeHtml(n)+'</li>').join('')+'</ul>';}else{warning.hidden=true;warning.textContent='';}
    $('mail-frame').src=s.file;
    $('open-mail').href=s.file;
    history.replaceState(null,'','#'+encodeURIComponent(s.id));
  }

  function escapeHtml(value){return String(value).replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));}

  document.querySelectorAll('.type-filter').forEach(button=>button.addEventListener('click',()=>{
    document.querySelectorAll('.type-filter').forEach(item=>item.classList.toggle('active',item===button));
    state.type=button.dataset.type;renderList();
  }));
  channelFilter.addEventListener('change',()=>{state.channel=channelFilter.value;renderList();});
  codeFilter.addEventListener('change',()=>{state.code=codeFilter.value;renderList();});
  $('search').addEventListener('input',event=>{state.search=event.target.value;renderList();});
  document.querySelectorAll('.viewport').forEach(button=>button.addEventListener('click',()=>{
    document.querySelectorAll('.viewport').forEach(item=>item.classList.toggle('active',item===button));
    const shell=$('preview-frame-shell');shell.classList.toggle('mobile',button.dataset.viewport==='mobile');shell.classList.toggle('desktop',button.dataset.viewport!=='mobile');
  }));

  const hash=decodeURIComponent(location.hash.slice(1));
  if(hash&&scenarios.some(s=>s.id===hash))state.selected=hash;
  renderList();
})();
JS;

$readme = <<<'MD'
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
MD;

foreach ([$outputRoot, $mailRoot, $assetRoot] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create directory: ' . $directory);
    }
}

$assetCopies = [
    $projectRoot . '/upload/image/chipoteka-hd.png' => $assetRoot . '/chipoteka-hd.png',
    $projectRoot . '/upload/image/facebook_white.png' => $assetRoot . '/facebook_white.png',
    $projectRoot . '/upload/image/placeholder.png' => $assetRoot . '/placeholder.png',
];

foreach ($assetCopies as $source => $destination) {
    if (!copy($source, $destination)) {
        throw new RuntimeException('Could not copy preview asset: ' . $source);
    }
}

$testBarcodeSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="706" height="220" viewBox="0 0 706 220" role="img" aria-label="Testni 2D barkod, ne plaćati">
  <rect width="706" height="220" fill="#fff"/>
  <g fill="#111">
    <path d="M22 22h7v142h-7zm13 0h3v142h-3zm9 0h9v142h-9zm15 0h4v142h-4zm11 0h12v142H70zm19 0h5v142h-5zm12 0h8v142h-8zm14 0h3v142h-3zm10 0h11v142h-11zm18 0h5v142h-5zm12 0h14v142h-14zm21 0h3v142h-3zm10 0h8v142h-8zm15 0h5v142h-5zm12 0h12v142h-12zm19 0h4v142h-4zm11 0h9v142h-9zm15 0h3v142h-3zm10 0h14v142h-14zm21 0h5v142h-5zm12 0h8v142h-8zm14 0h4v142h-4zm11 0h13v142h-13zm20 0h5v142h-5zm12 0h10v142h-10zm17 0h3v142h-3zm10 0h12v142h-12zm19 0h4v142h-4zm11 0h8v142h-8zm15 0h5v142h-5zm12 0h14v142h-14zm21 0h3v142h-3zm10 0h9v142h-9zm16 0h5v142h-5zm12 0h12v142h-12zm19 0h4v142h-4zm11 0h8v142h-8zm15 0h3v142h-3zm10 0h14v142h-14zm21 0h5v142h-5zm12 0h9v142h-9zm16 0h4v142h-4zm11 0h12v142h-12zm19 0h3v142h-3zm10 0h10v142h-10zm17 0h5v142h-5zm12 0h13v142h-13zm20 0h4v142h-4zm11 0h9v142h-9zm16 0h3v142h-3zm10 0h12v142h-12z"/>
    <path d="M22 42h662v4H22zm0 19h662v7H22zm0 27h662v3H22zm0 18h662v6H22zm0 25h662v4H22zm0 20h662v8H22z" fill="#fff"/>
  </g>
  <rect x="22" y="181" width="662" height="25" rx="3" fill="#f5f5f5"/>
  <text x="353" y="199" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="13" font-weight="700" fill="#E31318">TESTNI 2D BARKOD — NE PLAĆATI</text>
</svg>
SVG;
file_put_contents($assetRoot . '/test-payment-barcode.svg', $testBarcodeSvg . "\n");

foreach (glob($mailRoot . '/*.html') ?: [] as $oldMail) {
    unlink($oldMail);
}

foreach ($scenarios as $scenario) {
    $path = $outputRoot . '/' . $scenario['file'];
    file_put_contents($path, renderEmail($scenario, $order, $twig));
}

$browserScenarios = array_map(static function (array $scenario): array {
    unset($scenario['paragraphs'], $scenario['showBankDetails']);

    return $scenario;
}, $scenarios);

file_put_contents($assetRoot . '/scenarios.js', 'window.MAIL_PREVIEW_SCENARIOS = ' . json_encode(
    $browserScenarios,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
) . ";\n");
file_put_contents($assetRoot . '/styles.css', $styles . "\n");
file_put_contents($assetRoot . '/app.js', $appJs . "\n");
file_put_contents($outputRoot . '/index.html', buildIndexHtml(count($scenarios)) . "\n");
file_put_contents($outputRoot . '/README.md', $readme . "\n");

echo 'Generated ' . count($scenarios) . ' mail previews in ' . $outputRoot . PHP_EOL;
