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
            'group_list'       => 'grupeartikala_lista.json',
            'product_list'     => 'artikli_atribut_uid.json',
            'product_actions'  => 'akcije.json',
            'product_image'    => 'product_image.json',
            'manufacturer_uid' => 'partner_single.json',
            'customer_email'   => 'customer_email.url',
            'customer_create'  => 'customer_create.url',
            'order_create'     => 'order_create.url',
            'stock_get'        => 'stock_get.url',
            'ind_stock_get'    => 'ind_stock_get.url',
            'raspis'           => 'raspis.url'
        ];
    }


    /**
     * @return string[]
     */
    private static function production(): array
    {
        return [
            'group_list'       => 'grupeartikala/lista',
            'product_list'     => 'artikli/atribut/atribut_uid/59-2987',
            'product_actions'  => 'akcije/lista',
            'product_image'    => 'artikli/dokumenti/',
            'manufacturer_uid' => 'partneri/uid/',
            'customer_email'   => 'partneri/email/',
            'customer_create'  => 'partneri/snimi/',
            'order_create'     => 'NaloziProdaje/snimi/',
            'stock_get'        => 'StanjeZalihe/Skladiste/',
            'ind_stock_get'    => 'StanjeZalihe/ArtiklUID/',
            'raspis'           => 'NaloziProdaje/raspis/poslovnica/'
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