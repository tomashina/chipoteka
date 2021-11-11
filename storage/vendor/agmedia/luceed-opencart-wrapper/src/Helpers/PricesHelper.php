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
class PricesHelper
{


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function update(Collection $product): array
    {
        if (isset($product['robna_marka'])) {
            $manufacturer = Manufacturer::where('luceed_uid', $product['robna_marka'])->first();

            if ($manufacturer) {
                return [
                    'id' => $manufacturer->manufacturer_id,
                    'name' => $manufacturer->name
                ];
            }
        }

        return ['id' => 0, 'name' => ''];
    }
}