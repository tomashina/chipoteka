<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Models\Order\OrderProduct;
use Illuminate\Support\Collection;

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
            $xid = substr($order['shipping_code'], strpos($order['shipping_code'], '_') + 1);

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
     * @param       $br_naloga
     * @param       $pickup
     *
     * @return array
     */
    public static function getNalog(array $products, $order_id, $br_naloga, string $store, $pickup): array
    {
        $stavke = static::getOrderItems($products, $order_id);
        $vrsta  = static::getVrstaIsporuke($store, $pickup['skladiste']);

        $response = [
            'broj_naloga'    => '-' . $br_naloga,
            'iznos'          => $stavke['iznos'],
            'sa__skladiste'  => $store,
            'na__skladiste'  => $pickup['skladiste'],
            'pj'             => $pickup['pj'],
            'skl_dokument'   => 'MSI',
            'vrsta_isporuke' => $vrsta,
            'napomena'       => 'Web shop',
            'stavke'         => $stavke['stavke']
        ];

        return $response;
    }


    private static function getVrstaIsporuke($sa, $na): string
    {
        if (in_array($sa, ['006', '101']) && $na == '001') {
            return '00';
        }

        if (in_array($sa, ['006', '001']) && in_array($na, ['101', '099'])) {
            return '00';
        }

        return '07';
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


    /**
     * @param string     $store_uid
     * @param Collection $warehouses
     *
     * @return mixed|null
     */
    public static function getStoreSifra(string $store_uid, Collection $warehouses)
    {
        $store_sifra = $warehouses->where('skladiste_uid', '=', $store_uid)->first();

        if (isset($store_sifra['skladiste'])) {
            return $store_sifra['skladiste'];
        }

        return null;
    }


    /**
     * @param array $required
     * @param array $availables
     *
     * @return bool
     */
    public static function hasAllInOneStore(array $required, array $availables): bool
    {
        $availables = collect($availables);

        foreach ($required as $uid => $product) {
            $item = $availables->where('uid', $uid)->first();

            if ($item['qty'] < $product['qty']) {
                return false;
            }
        }

        return true;
    }


    public static function getAvailableItemsFromStore(array $required, array $availables)
    {
        $availables = collect($availables);
        $products_required = [];
        $products_available = [];

        foreach ($required as $uid => $product) {
            $item = $availables->where('uid', $uid)->first();

            if ($item['qty'] < $product['qty']) {
                $products_required[$item['uid']] = [
                    'qty' => $product['qty'] - $item['qty']
                ];
                // Uzmi ako ima.
                if ($item['qty']) {
                    $products_available[$item['uid']] = [
                        'qty' => $item['qty']
                    ];
                }
            }

            // Uzmi artikle kojih ima u pickup poslovnici.
            if ($item['qty'] >= $product['qty']) {
                $products_available[$item['uid']] = [
                    'qty' => $product['qty']
                ];
            }
        }

        return [
            'available' => $products_available,
            'required'  => $products_required
        ];
    }
}