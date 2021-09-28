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
        Log::store('3.01', 'product');
        // Check if description exist.
        //If not add title for description.
        $naziv = $product['naziv'];
        $description = static::setDescription($product['opis']);
        $spec = static::setDescription($product['specifikacija']);

        Log::store('3.02', 'product');
        /*if ( ! $product['opis']) {
            $description = $naziv;
        }*/

        if ($old_description) {
            if ( ! $old_description['update_name']) {
                $naziv = $old_description['name'];
            }
            if ( ! $old_description['update_description']) {
                $description = $old_description['description'];
            }
        }

        Log::store('3.03', 'product');

        $response[agconf('import.default_language')] = [
            'name'              => $naziv,
            'update_name'       => $old_description ? $old_description['update_name'] : 1,
            'description'       => $description,
            'update_description'=> $old_description ? $old_description['update_description'] : 1,
            'spec_description'  => $spec ?: '',
            'short_description' => $description,
            'tag'               => $naziv,
            'meta_title'        => $naziv,
            'meta_description'  => $description,
            'meta_keyword'      => $naziv,
        ];

        Log::store('3.04', 'product');

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
            $attribute = collect($attribute);
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
        Log::store('3.10', 'product');

        if (isset($product['dokumenti'][$key])) {
            $image_path = agconf('import.image_path');
            // Check if the image path exist.
            // Create it if not.
            if ( ! is_dir(DIR_IMAGE . $image_path)) {
                mkdir(DIR_IMAGE . $image_path, 0777, true);
            }

            Log::store('3.11', 'product');
            Log::store($product['dokumenti'], 'product');

            if (isset($product['dokumenti'][$key]->filename)) {
                $newstring = substr($product['dokumenti'][$key]->filename, -3);
            } else {
                $newstring = substr($product['dokumenti'][$key]['filename'], -3);
            }

            $name = Str::slug($product['naziv']) . '-' . strtoupper(Str::random(9)) . '.jpg';

            Log::store('3.12', 'product');

            if (in_array($newstring, ['png', 'PNG'])) {
                $name = Str::slug($product['naziv']) . '-' . strtoupper(Str::random(9)) . '.' . $newstring;
            }

            Log::store('3.13', 'product');

            // Setup and create the image with GD library.
            $bin   = base64_decode(static::getImageString($product, $key));

            Log::store('3.14', 'product');

            $errorlevel=error_reporting();
            error_reporting(0);
            Log::store('3.15', 'product');
            $image = imagecreatefromstring($bin);
            Log::store('3.16', 'product');
            error_reporting($errorlevel);

            Log::store('3.17', 'product');

            if ($image !== false) {
                imagejpeg($image, DIR_IMAGE . $image_path . $name, 90);

                // Return only the image path.
                return $image_path . $name;
            }
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
        Log::store('3.30', 'product');

        $response = [];
        $default  = collect($product['dokumenti']);

        Log::store('3.31', 'product');

        if ($default->count() > 1) {

            Log::store('3.310', 'product');

            $docs = $default->splice(1);

            Log::store('3.311', 'product');

            if ($docs->count()) {
                for ($i = 0; $i < $docs->count(); $i++) {
                    if (isset($product['dokumenti'][$i + 1]->file_uid)) {
                        $uid = $product['dokumenti'][$i + 1]->file_uid;
                    } else {
                        $uid = $product['dokumenti'][$i + 1]['file_uid'];
                    }

                    Log::store('3.3110', 'product');

                    $response[] = [
                        'uid'        => $uid,
                        'image'      => static::getImagePath($product, $i + 1),
                        'sort_order' => $i
                    ];

                    Log::store('3.3111', 'product');
                }
            }

            Log::store('3.312', 'product');
        }

        Log::store('3.32', 'product');

        return $response;
    }


    /**
     * @param \stdClass $product
     *
     * @return array
     */
    public static function collectLuceedData(\stdClass $product): array
    {
        $atributi = [];
        $dokumenti = [];

        if ( ! empty($product->atributi)) {
            foreach ($product->atributi as $atr) {
                $atributi[] = [
                    'atribut_uid' => $atr->atribut_uid,
                    'naziv' => $atr->naziv,
                    'aktivan' => $atr->aktivan,
                    'vidljiv' => $atr->vidljiv,
                    'vrijednost' => $atr->vrijednost,
                ];
            }
        }

        if ( ! empty($product->dokumenti)) {
            foreach ($product->dokumenti as $dok) {
                $dokumenti[] = [
                    'file_uid' => $dok->file_uid,
                    'filename' => $dok->filename,
                    'naziv' => $dok->naziv,
                    'md5' => $dok->md5,
                ];
            }
        }

        return [
            'artikl_uid' => $product->artikl_uid,
            'artikl' => $product->artikl,
            'naziv' => $product->naziv,
            'barcode' => $product->barcode,
            'jm' => $product->jm,
            'opis' => static::setDescription($product->opis),
            'vpc' => $product->vpc,
            'mpc' => $product->mpc,
            'enabled' => $product->enabled,
            'specifikacija' => static::setDescription($product->specifikacija),
            'stopa_pdv' => $product->stopa_pdv,
            'nadgrupa_artikla' => $product->nadgrupa_artikla,
            'nadgrupa_artikla_naziv' => $product->nadgrupa_artikla_naziv,
            'grupa_artikla' => $product->grupa_artikla,
            'grupa_artikla_naziv' => $product->grupa_artikla_naziv,
            'robna_marka' => $product->robna_marka,
            'robna_marka_naziv' => $product->robna_marka_naziv,
            'jamstvo_naziv' => $product->jamstvo_naziv,
            'stanje_kol' => $product->stanje_kol,
            'atributi' => $atributi,
            'dokumenti' => $dokumenti,
        ];
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param string|null $text
     *
     * @return string
     */
    private static function setDescription(string $text = null): string
    {
        if ($text) {
            $text = str_replace("\n", '<br>', $text);
            $text = str_replace("\r", '<br>', $text);
            $text = str_replace("\t", '<tab>', $text);

            return $text;
        }

        return '';
    }


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
        Log::store('3.131', 'product');

        if (isset($product['dokumenti'][$key]->file_uid)) {
            $result = LuceedProduct::getImage($product['dokumenti'][$key]->file_uid);
        } else {
            $result = LuceedProduct::getImage($product['dokumenti'][$key]['file_uid']);
        }

        Log::store('3.132', 'product');

        $image = json_decode($result);

        Log::store($image, 'product');

        return $image->result[0]->files[0]->content;
    }
}