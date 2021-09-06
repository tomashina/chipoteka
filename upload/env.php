<?php
// AGmedia Custom
define('OC_ENV', [
    'env'                         => 'env',
    //
    'free_shipping_amount'        => '%free_shipping_amount%',
    'default_shipping_price'      => '%default_shipping_price%',
    'shipping_collector_price'    => '%shipping_collector_price%',
    'service'                     => [
        // test_url http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/
        // live_url http://luceedapi.tomsoft.hr:3675/datasnap/rest/
        'base_url' => 'service_base_url',
        'username' => 'service_base_username',
        'password' => 'service_base_password',
    ],
    'import'                      => [
        'default_category'    => import_default_category,
        'default_action_category' => import_default_action_category,
        'default_language'    => import_default_language, // HR
        'default_tax_class'   => import_default_tax_class, // PDV
        'default_stock_empty' => import_default_stock_empty,
        'default_stock_full'  => import_default_stock_full,
        'image_path'          => 'import_image_path',
        'image_placeholder'   => 'import_image_placeholder',
        'with_tax'            => 'import_with_tax',
        'category'            => [
            'excluded' => [import_category_excluded]
        ],
        'warehouse'           => [
            'included'          => [warehouse_included],
            'default'           => [warehouse_default],
            'availability_view' => [warehouse_availability_view],
            'json'              => 'warehouse_json'
        ],

    ],
    'luceed'                      => [
        'default_warehouse_uid' => 'luceed_default_warehouse_uid', // Šifra skladišta iz Luceed-a.
        'stock_warehouse_uid'   => 'luceed_stock_warehouse_uid', // Primarna šifra skladišta za provjeru količina.
        'status_uid'            => 'luceed_status_uid',
        'payment'               => [
            'cod'          => 'luceed_payment_cod',
            'card_default' => 'luceed_payment_card_default',
            'cards'        => [
                'DINERS'     => 'luceed_payment_cards_diners',
                'MASTERCARD' => 'luceed_payment_cards_mastercard',
                'VISA'       => 'luceed_payment_cards_visa',
                'MAESTRO'    => 'luceed_payment_cards_maestro'
            ]
        ],
        'shipping_article_uid'  => '%shipping_article_uid%',
        'date'                  => '%date%',
        'datetime'              => '%datetime%',
    ],

]);