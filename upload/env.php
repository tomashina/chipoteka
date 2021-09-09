<?php
// AGmedia Custom
define('OC_ENV', [
    'env'                    => 'env',
    //
    'free_shipping_amount'   => '%free_shipping_amount%',
    'default_shipping_price' => '%default_shipping_price%',
    'service'                => [
        'base_url' => 'service_base_url',
        'username' => 'service_base_username',
        'password' => 'service_base_password',
    ],
    'import'                 => [
        'default_category'        => 'import_default_category',
        'default_action_category' => 'import_default_action_category',
        'default_language'        => 'import_default_language',
        'default_tax_class'       => 'import_default_tax_class',
        'default_stock_empty'     => 'import_default_stock_empty',
        'default_stock_full'      => 'import_default_stock_full',
        'default_attribute_group' => 'import_default_attribute_group',
        'default_store_id'        => 'import_default_store_id',
        'image_path'              => 'import_image_path',
        'image_placeholder'       => 'import_image_placeholder',
        'category'                => [
            'excluded' => [import_category_excluded]
        ],
        'warehouse'               => [
            'included'          => [warehouse_included],
            'default'           => [warehouse_default],
            'availability_view' => [warehouse_availability_view],
            'stores'            => [warehouse_stores],
            'json'              => 'warehouse_json'
        ],
        'payments' => [
            'included' => [
                payments_included
            ],
            'json' => 'payments_json'
        ],
        'product'                 => [
            'chunk' => product_chunk,
        ],
        'orders' => [
            'from_date' => 'orders_from_date'
        ]
    ],
    'luceed'               => [
        'with_tax'              => 'luceed_with_tax',
        'default_warehouse_uid' => 'luceed_default_warehouse_uid', // Šifra skladišta iz Luceed-a.
        'stock_warehouse_uid'   => 'luceed_stock_warehouse_uid', // Primarna šifra skladišta za provjeru količina.
        'status_uid'            => 'luceed_status_uid',
        'payment'               => [
            'cod'           => 'luceed_payment_cod',
            'bank_transfer' => 'luceed_payment_bank_transfer',
            'card_default'  => 'luceed_payment_card_default'
        ],
        'shipping_article_uid'  => 'luceed_shipping_article_uid',
        'date'                  => 'luceed_date',
        'datetime'              => 'luceed_timedate',

    ],
    //
    'mail' => [
        'cod' => [
            mail_cod
        ],
        'bank_transfer' => [
            mail_bank_transfer
        ],
        'wspay' => [
            mail_wspay
        ],
    ],
],
]);