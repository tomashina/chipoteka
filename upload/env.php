<?php
// AGmedia Custom
define('OC_ENV', [
    'env'                    => 'production',
    //
    'free_shipping_amount'   => 66.36,
    'default_shipping_price' => 5.17,
    'service'                => [
        // test_url http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/
        // live_url http://luceedapi.tomsoft.hr:3675/datasnap/rest/
        'base_url' => 'http://sechip.dyndns.org:8889/datasnap/rest/',
        'username' => 'webshop',
        'password' => 'test.bJ8tn63Q',
    ],
    'import'                 => [
        'default_category'        => 0,
        'default_category'        => 0,
        'default_action_category' => 308,
        'default_language'        => 2, // HR
        'default_tax_class'       => 11, // PDV
        'default_stock_empty'     => 5,
        'default_stock_full'      => 7,
        'default_attribute_group' => 4,
        'default_store_id'        => 0,
        'image_path'              => 'catalog/products/',
        'image_placeholder'       => 'catalog/products/no-image.jpg',
        'category'                => [
            'excluded' => ['000000', '900001', '100000', '100001', '100002',
                '900411', '9004DJ', '900DJ', '9004SM', '9004ML', '9004MD', '9004SI', '9004BE', '9004AK', '9004BV', '9004MA', '900006', '9004FF', '9004DA',
                '900400', '230300', '250363', '9004MV', '9004DM', '9004ST', '9004RI', '9004MP', '9004IV', '9004MM', '9004ZG', '9004MZ', '9004RL',
                '9004OS', '9004GI', '9004DP', '9004DM1', '9004DM2', '9004MS', '9004KK', '9004ZD', '9004DB', '9004IJ', '9004TR', '9004FJ', '9004MR', '9004PU', '900600']
        ],
        'warehouse'               => [
            'included'          => ['001', '002', '003', '004', '006', '005', '007', '011', '012', '101'],
            'default'           => ['101', '001', '002', '003', '004', '006', '005', '007', '011', '012'],
            'availability_view' => ['001', '002', '003', '004', '006', '005', '007', '011', '012'],
            'stores'            => ['002', '003', '004', '006', '005', '007', '011', '012'],
            'json'              => DIR_STORAGE . 'upload/assets/skladista.json'
        ],
        'taxes'                   => [
            5  => 12,
            25 => 11,
        ],
        'payments'                => [
            'included' => [
                'VIRMAN MP',
                'GLS POUZEĆE',
                'MAESTRO',
                'MAESTRO RATE',
                'MASTERCARD',
                'MASTERCARD RATE',
                'VISA',
                'VISA RATE'
            ],
            'cards' => [
                'MAESTRO',
                'MAESTRO RATE',
                'MASTERCARD',
                'MASTERCARD RATE',
                'VISA',
                'VISA RATE'
            ],
            'json'     => DIR_STORAGE . 'upload/assets/placanja.json'
        ],
        'product'                 => [
            'chunk' => 100,
        ],
        'orders'                  => [
            'from_date' => '01.10.2022'
        ]
    ],
    'luceed'                 => [
        'with_tax'              => 'D',
        'default_warehouse_uid' => 'P02', // Šifra skladišta iz Luceed-a.
        'stock_warehouse_uid'   => 'P04', // Primarna šifra skladišta za provjeru količina.
        'status_uid'            => '01',
        'payment'               => [
            'cod'           => 'GLS POUZEĆE',
            'bank_transfer' => 'VIRMAN MP',
            'card_default'  => ''
        ],
        'shipping_article_uid'  => 'USL-19',
        'date'                  => 'd.m.Y',
        'datetime'              => 'd.m.Y H:i:s',
        'pickup' => [
            0 => '001',
            1 => '002',
            2 => '003',
            3 => '004',
            4 => '005',
            5 => '011',
            6 => '012',
            7 => '007',
        ]
    ],

    'poslovnice_radno_vrijeme' => [
        '001' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
        '002' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
        '003' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
        '004' => 'pon - pet: 09:00-13:30h i 17:00-20:00h<br>sub: 09:00 - 13:00',
        '005' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
        '011' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
        '012' => 'pon - pet: 08:00 - 20:00<br>sub: 08:00 - 13:00',
        '007' => 'pon - pet: 09:00 - 19:00<br>sub: 09:00 - 13:00',
    ],
    //
    'mail'                   => [
        'cod'           => [
            0   => [
                '02' => [24 => 10, 72 => 11],
                '05' => [24 => 10, 72 => 11],
                '11' => [168 => 6]
            ],
            7 => [
                'from' => '12',
                'to' => '02'
            ],
            3 => [
                'from' => '02',
                'to' => '05'
            ],
            501 => [
                'from' => '02',
                'to' => '10'
            ],
            502 => [
                'from' => '03',
                'to' => '10'
            ],
            503 => [
                'from' => '05',
                'to' => '10'
            ],
            504 => [
                'from' => '06',
                'to' => '10'
            ],
            301 => [
                'from' => '02',
                'to' => '03'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ],
            401 => [
                'from' => '05',
                'to' => '03'
            ],
            402 => [
                'from' => '12',
                'to' => '11'
            ],
            403 => [
                'from' => '02',
                'to' => '11'
            ],
            404 => [
                'from' => '03',
                'to' => '11'
            ]
        ],
        'bank_transfer' => [
            0   => [
                '12' => [24 => 5, 48 => 8],
                '02' => [24 => 10, 72 => 11],
                '05' => [24 => 10, 72 => 11],
                '11' => [168 => 6]
            ],
            9   => [
                'from' => '12',
                'to' => '02'
            ],

            901 => [
                'from' => '12',
                'to' => '03'
            ],

            3 => [
                'from' => '02',
                'to' => '05'
            ],
            501 => [
                'from' => '02',
                'to' => '10'
            ],
            502 => [
                'from' => '03',
                'to' => '10'
            ],
            503 => [
                'from' => '05',
                'to' => '10'
            ],
            504 => [
                'from' => '06',
                'to' => '10'
            ],
            14 => [
                'from' => '12',
                'to' => '05'
            ],

            301 => [
                'from' => '02',
                'to' => '03'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ],
            500 => [
                'from' => '09',
                'to' => '10'
            ],
            401 => [
                'from' => '12',
                'to' => '10'
            ],

            402 => [
                'from' => '12',
                'to' => '11'
            ],
            403 => [
                'from' => '02',
                'to' => '11'
            ],
            404 => [
                'from' => '03',
                'to' => '11'
            ]
        ],
        'wspay'         => [
            0   => [
                '02' => [24 => 10, 72 => 11],
                '05' => [24 => 10, 72 => 11],
                '11' => [168 => 6]
            ],
            1 => [
                'from' => '12',
                'to' => '02'
            ],
            3 => [
                'from' => '02',
                'to' => '05'
            ],
            501 => [
                'from' => '02',
                'to' => '10'
            ],
            502 => [
                'from' => '03',
                'to' => '10'
            ],
            503 => [
                'from' => '05',
                'to' => '10'
            ],
            504 => [
                'from' => '06',
                'to' => '10'
            ],
            301 => [
                'from' => '02',
                'to' => '03'
            ],
            4 => [
                'from' => '05',
                'to' => '11'
            ],
            401 => [
                'from' => '05',
                'to' => '03'
            ],
            500 => [
                'from' => '12',
                'to' => '10'
            ],
            402 => [
                'from' => '12',
                'to' => '11'
            ],
            403 => [
                'from' => '02',
                'to' => '11'
            ],
            404 => [
                'from' => '03',
                'to' => '11'
            ]
        ],
    ],
    'mail_pickup'            => [
        'cod'           => [
            0   => [
                '11' => [48 => 853, 96 => 853]
            ],
            843 => [
                'from' => '03',
                'to' => '05'
            ],
            833 => [
                'from' => '05',
                'to' => '09'
            ]
        ],
        'bank_transfer' => [
            0   => [
                '11' => [48 => 813, 96 => 813]
            ],
            843 => [
                'from' => '03',
                'to' => '05'
            ],
            813 => [
                'from' => '05',
                'to' => '11'
            ]
        ],
        'wspay'         => [
            0   => [
                '11' => [48 => 813, 96 => 813]
            ],
            843 => [
                'from' => '03',
                'to' => '05'
            ],
            813 => [
                'from' => '05',
                'to' => '10'
            ]
        ],
    ]
]);