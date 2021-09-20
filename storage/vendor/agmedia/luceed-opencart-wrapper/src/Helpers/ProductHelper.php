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
        $actual   = Category::where('luceed_uid', $product['grupa_artikla'])->first();

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


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function getManufacturer(Collection $product): array
    {
        if (isset($product['robna_marka'])) {
            $manufacturer = Manufacturer::where('luceed_uid', $product['robna_marka'])->first();

            if ($manufacturer) {
                return [
                    'id'   => $manufacturer->manufacturer_id,
                    'name' => $manufacturer->name
                ];
            }
        }

        return ['id' => 0, 'name' => ''];
    }


    /**
     * Return description with default language.
     * Language_id as response array key.
     *
     * @param Collection $product
     * @param null       $old_description
     *
     * @return array
     */
    public static function getDescription(Collection $product, $old_description = null): array
    {
        // Check if description exist.
        //If not add title for description.
        $naziv = $product['naziv'];
        $description = str_replace("\n", '<br>', $product['opis']);
        $spec = str_replace("\n", '<br>', $product['specifikacija']);

        if ( ! $product['opis']) {
            $description = $naziv;
        }

        if ($old_description) {
            if ( ! $old_description['update_name']) {
                $naziv = $old_description['name'];
            }
            if ( ! $old_description['update_description']) {
                $description = $old_description['description'];
            }
        }

        $response[agconf('import.default_language')] = [
            'name'              => $naziv,
            'description'       => $description,
            'spec_description'  => $spec ?: '',
            'short_description' => $description,
            'tag'               => $naziv,
            'meta_title'        => $naziv,
            'meta_description'  => str_replace("<br>", '. ', $description),
            'meta_keyword'      => $naziv,
        ];

        return $response;
    }


    /**
     * @return array
     */
    public static function getAttributes(Collection $product): array
    {
        $response   = [];
        $attributes = collect($product['atributi']);

        foreach ($attributes as $attribute) {
            if (static::checkAttributeForImport($attribute)) {
                $has = Attribute::where('luceed_uid', $attribute['atribut_uid'])->first();

                if ($has && $has->count()) {
                    $id = $has['attribute_id'];
                } else {
                    $id = static::makeAttribute($attribute);
                }

                if ($id) {
                    $response[] = [
                        'attribute_id' => $id,
                        'product_attribute_description' => [
                            agconf('import.default_language') => [
                                'text' => $attribute['vrijednost']
                            ]
                        ]
                    ];
                }
            }
        }

        return $response;
    }


    /**
     * @return array
     */
    public static function getSeoUrl(Collection $product): array
    {
        $slug = Str::slug($product['naziv']) . '-' . $product['artikl'];

        return [
            agconf('import.default_language') => $slug
        ];
    }


    /**
     * Get the image string from luceed service and
     * return the full path string.
     *
     * @param Collection $product
     * @param int        $key
     *
     * @return string
     */
    public static function getImagePath(Collection $product, int $key = 0): string
    {
        $image_path = agconf('import.image_path');
        // Check if the image path exist.
        // Create it if not.
        if ( ! is_dir(DIR_IMAGE . $image_path)) {
            mkdir(DIR_IMAGE . $image_path, 0777, true);
        }

        $newstring = substr($product['dokumenti'][$key]['filename'], -3);
        $name = Str::slug($product['naziv']) . '-' . strtoupper(Str::random(9)) . '.jpg';

        if (in_array($newstring, ['png', 'PNG'])) {
            $name = Str::slug($product['naziv']) . '-' . strtoupper(Str::random(9)) . '.' . $newstring;
        }

        // Setup and create the image with GD library.
        $bin   = base64_decode(static::getImageString($product, $key));

        $errorlevel=error_reporting();
        error_reporting(0);
        $image = imagecreatefromstring($bin);
        error_reporting($errorlevel);


        if ($image !== false) {
            imagejpeg($image, DIR_IMAGE . $image_path . $name, 90);

            // Return only the image path.
            return $image_path . $name;
        }

        return 'not_valid_image';
    }


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function getImages(Collection $product): array
    {
        $response = [];
        $default  = collect($product['dokumenti']);
        $docs     = $default->splice(1);

        for ($i = 0; $i < $docs->count(); $i++) {
            $response[] = [
                'uid'        => $product['dokumenti'][$i + 1]['file_uid'],
                'image'      => static::getImagePath($product, $i + 1),
                'sort_order' => $i
            ];
        }

        return $response;
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param $attribute
     *
     * @return bool
     */
    private static function checkAttributeForImport($attribute): bool
    {
        if ($attribute['aktivan'] == 'D' &&
            $attribute['vidljiv'] == 'D' &&
            $attribute['atribut_uid'] != '' &&
            $attribute['naziv'] != '')
        {
            return true;
        }

        return false;
    }


    /**
     * @param $attribute
     *
     * @return false|int
     */
    private static function makeAttribute($attribute)
    {
        $id = Attribute::insertGetId([
            'luceed_uid' => $attribute['atribut_uid'],
            'attribute_group_id' => agconf('import.default_attribute_group'),
            'sort_order' => $attribute['redoslijed'] ?: 9
        ]);

        if ($id) {
            AttributeDescription::insert([
                'attribute_id' => $id,
                'language_id' => agconf('import.default_language'),
                'name' => $attribute['naziv']
            ]);

            return $id;
        }

        return false;
    }


    /**
     * Get the image string from luceed service.
     *
     * @param Collection $product
     * @param int        $key
     *
     * @return mixed
     */
    private static function getImageString(Collection $product, int $key)
    {
        $result = LuceedProduct::getImage($product['dokumenti'][$key]['file_uid']);

        $image = json_decode($result);

        return $image->result[0]->files[0]->content;
    }
}