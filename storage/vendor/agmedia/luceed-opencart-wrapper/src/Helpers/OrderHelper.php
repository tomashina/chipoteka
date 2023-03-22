<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Models\Attribute\Attribute;
use Agmedia\Models\Attribute\AttributeDescription;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class OrderHelper
{


    public static function resolvePickup($order)
    {
        Log::store('resolvePickup($order) => ' . $order['order_id'], 'pickup');
        Log::store($order, 'pickup');

        $pickup = false;
        $shipping_code = substr($order['shipping_code'], 0, -1);

        Log::store($shipping_code, 'pickup');

        if ($shipping_code == 'xshippingpro.xshippingpro1_') {
            $xid = (int) substr($order['shipping_code'], strpos($order['shipping_code'], '_'));

            Log::store($xid, 'pickup');

            foreach (agconf('luceed.pickup') as $key => $item) {
                if ($key == $xid) {
                    $pickup = $item;
                }
            }

            Log::store($pickup, 'pickup');
        }

        return $pickup;
    }
}