<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Models\Category\Category;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class ProductHelper
{

    /**
     * Traverse through opencart categories tree
     * and sort the response array.
     * If "grupa_artikla" luceed tag is not found in opencart DB,
     * default category is returned.
     *
     * @param Collection $product
     *
     * @return array
     */
    public static function getCategories(Collection $product): array
    {
        $response = [0 => agconf('import.default_category')];
        $actual   = Category::where('luceed_uid', $product->grupa_artikla)->first();

        if ($actual && $actual->count()) {
            $response[0] = $actual->category_id;

            if ($actual->parent_id) {
                $parent = Category::where('category_id', $actual->parent_id)->first();

                if ($parent->count()) {
                    $response[1] = $parent->category_id;

                    if ($parent->parent_id) {
                        $main = Category::where('category_id', $parent->parent_id)->first();

                        if ($main->count()) {
                            $response[2] = $main->category_id;
                        }
                    }
                }
            }
        }

        return $response;
    }
}