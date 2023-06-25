<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Models\Order\OrderProduct;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class OrderHelper
{

    public static function resolvePickup($order)
    {
        $pickup        = false;
        $shipping_code = substr($order['shipping_code'], 0, -1);

        if ($shipping_code == 'xshippingpro.xshippingpro1_') {
            $xid = (int) substr($order['shipping_code'], strpos($order['shipping_code'], '_'));

            foreach (agconf('luceed.pickup') as $key => $item) {
                if ($key == $xid) {
                    $pickup = $item;
                }
            }
        }

        return $pickup;
    }


    /**
     * @param array $units
     *
     * @return string
     */
    public static function getAvailabilityPickupQuery(array $units): string
    {
        $string = '[';

        foreach ($units as $unit) {
            $string .= $unit . ',';
        }

        $string = substr($string, 0, -1);

        $string .= ']';

        return $string;
    }


    /**
     * @param array $products
     *
     * @return array
     */
    public static function getOrderItems(array $products, $order_id): array
    {
        $response = [];
        $items    = [];
        $iznos    = 0;

        foreach ($products as $uid => $product) {
            $order_product = OrderProduct::query()->where('order_id', $order_id)->where('model', $uid)->first();

            $items[] = [
                'artikl'   => $uid,
                'kolicina' => (int) $product['qty'],
                'cijena'   => (float) number_format($order_product->price, 2, '.', ''),
                'rabat'    => 0 //(float) number_format($price['rabat'], 2),
            ];

            $iznos = $iznos + ($order_product->price * $product['qty']);
        }

        $response['stavke'] = $items;
        $response['iznos']  = number_format($iznos, 2, '.', '');

        return $response;
    }


    /**
     * @param array $products
     * @param       $store_uid
     * @param       $br
     * @param       $pickup
     *
     * @return array
     */
    public static function getNalog(array $products, $store_uid, $br, $pickup): array
    {
        $response = [];
        $stavke   = static::getOrderItems($products);

        $response[$store_uid] = [
            'broj_naloga'    => '-' . $br,
            'iznos'          => $stavke['iznos'],
            'sa__skladiste'  => $store_uid,
            'na__skladiste'  => $pickup,
            'skl_dokument'   => 'DP',
            'vrsta_isporuke' => '03',
            'napomena'       => 'Web shop',
            'stavke'         => $stavke['stavke']
        ];

        return $response;
    }


    /**
     * @param array $products
     * @param array $available
     *
     * @return int[]
     */
    public static function resolveRequiredProducts(array $products, array $available): array
    {
        $required = [];
        $products = array_values($products);

        for ($i = 0; $i < count($products); $i++) {
            if ( ! isset($available[$products[$i]['artikl']])) {
                $required[$products[$i]['artikl']] = ['qty' => $products[$i]['kolicina']];

            } else {
                if ($available[$products[$i]['artikl']]['qty'] < $products[$i]['kolicina']) {
                    $products[$i]['kolicina'] = $products[$i]['kolicina'] - $available[$products[$i]['artikl']]['qty'];

                    $required[$products[$i]['artikl']] = ['qty' => $products[$i]['kolicina']];

                } else {
                    unset($products[$products[$i]['artikl']]);
                }
            }
        }

        return [
            'regular'  => $products,
            'required' => $required
        ];
    }
}