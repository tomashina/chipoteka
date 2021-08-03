<?php

namespace Agmedia\Luceed;

/**
 * Class LuceedEndPoints
 * @package Agmedia\Luceed
 */
class LuceedEndPoints
{

    /**
     * @return string[]
     */
    private static function local(): array
    {
        return [
            'group_list'        => 'grupeartikala_lista.json',
            'manufacturer_list' => 'robnemarke_lista.json',
            'warehouse_list'    => 'skladista_lista.json',
            'product_list'      => 'artikli_lista.json',
            'product_actions'   => 'akcije.json',
            'product_image'     => 'product_image.json',
            'manufacturer_uid'  => 'partner_single.json',
            'customer_email'    => 'customer_email.url',
            'customer_create'   => 'customer_create.url',
            'order_create'      => 'order_create.url',
            'stock_get'         => 'stock_get.url',
            'ind_stock_get'     => 'ind_stock_get.url',
            'raspis'            => 'raspis.url',
            //
            'product_0730222201' => 'artikl_0730222201.json',
            'product_0730161301' => 'artikl_0730161301.json',
            'product_9001002044' => 'artikl_9001002044.json',
        ];
    }


    /**
     * @return string[]
     */
    private static function production(): array
    {
        return [
            'group_list'        => 'grupeartikala/lista',
            'manufacturer_list' => 'robnemarke/lista',
            'warehouse_list'    => 'skladista/lista',
            'product_list'      => 'artikli/naziv//[0,300]',
            'product'           => 'artikli/sifra/',
            'product_actions'   => 'prodajneakcije/lista',
            'product_image'     => 'artikli/dokumenti/',
            'manufacturer_uid'  => 'partneri/uid/',
            'customer_email'    => 'partneri/email/',
            'customer_create'   => 'partneri/snimi/',
            'order_create'      => 'NaloziProdaje/snimi/',
            'stock_get'         => 'StanjeZalihe/Skladiste/',
            'ind_stock_get'     => 'StanjeZalihe/ArtiklUID/',
            'raspis'            => 'NaloziProdaje/raspis/poslovnica/'
        ];
    }


    /**
     * @param $env
     *
     * @return string[]
     */
    public static function get($env)
    {
        if ($env == 'local') {
            return static::local();
        }

        return static::production();
    }
}