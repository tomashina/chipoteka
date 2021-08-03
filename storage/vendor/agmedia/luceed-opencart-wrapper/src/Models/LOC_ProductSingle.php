<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Option\OptionValueDescription;
use Agmedia\Models\Product\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_ProductSingle
{

    /**
     * @var array
     */
    public $products;

    /**
     * @var array
     */
    public $product;

    /**
     * @var array
     */
    private $existing;

    /**
     * @var array
     */
    private $products_to_add = null;

    /**
     * @var int
     */
    private $default_category;

    /**
     * @var int
     */
    private $default_language;

    /**
     * @var string
     */
    private $image_path;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($product)
    {
        $this->product = $product;

        if ($product) {
            $this->product = $this->setProduct($product);
        }

        $this->default_category = agconf('import.default_category');
        $this->default_language = agconf('import.default_language');
        $this->image_path       = agconf('import.image_path');

        $this->resolveData();
    }


    /**
     *
     */
    public function resolveData()
    {
        if ( ! $this->product) {
            $this->products = Product::where('updated', 0)->limit(100)->get();
        }
    }


    /**
     * @return Collection
     */
    public function getProduct(): Collection
    {
        return collect($this->product);
    }



    /**
     * Collect, make and sort the data
     * for 1 products to make.
     *
     * @param $product
     *
     * @return array
     */
    public function make(): array
    {
        $manufacturer  = $this->getManufacturer();
        $stock_status  = $this->product->stanje_kol ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');


        $prod = [
            'model'               => $this->product->artikl,
            'sku'                 => $this->product->artikl,
            'luceed_uid'          => $this->product->artikl_uid,
            'upc'                 => $this->product->barcode,
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '',
            'mpn'                 => '',
            'location'            => '',
            'price'               => $this->product->mpc,
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $this->product->stanje_kol,
            'minimum'             => 1,
            'subtract'            => 1,
            'stock_status_id'     => $stock_status,
            'shipping'            => 1,
            'length'              => '',
            'width'               => '',
            'height'              => '',
            'length_class_id'     => 1,
            'weight'              => '',
            'weight_class_id'     => 1,
            'status'              => $this->product->stanje_kol ? 1 : 1,
            'sort_order'          => 0,
            'manufacturer'        => $manufacturer['name'],
            'manufacturer_id'     => $manufacturer['id'],
            'category'            => '',
            'filter'              => '',
            'download'            => '',
            'related'             => '',
            'image'               => ! empty($this->product->dokumenti) ? $this->getImagePath() : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_description' => $this->getDescriptionArray(),
            'product_image'       => $this->getImages(),
            'product_layout'      => [0 => ''],
            'product_category'    => $this->getCategories(),
            //'product_option'      => $this->getOptions($scale)
        ];

        return $prod;
    }



    /**
     * @return array
     */
    private function getManufacturer(): array
    {
        if (isset($this->product->robna_marka)) {
            $manufacturer = Manufacturer::where('luceed_uid', $this->product->robna_marka)->first();

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
     * @return array
     */
    private function getImages()
    {
        $response = [];
        $default  = collect($this->product->dokumenti);
        $docs     = $default->splice(1);

        for ($i = 0; $i < $docs->count(); $i++) {
            $response[] = [
                'image'      => $this->getImagePath($i + 1),
                'sort_order' => $i
            ];
        }

        return $response;
    }


    /**
     * Traverse through opencart categories tree
     * and sort the response array.
     * If "grupa_artikla" luceed tag is not found in opencart DB,
     * default category is returned.
     *
     * @return array
     */
    private function getCategories()
    {
        $response = [0 => $this->default_category];
        $actual   = Category::where('luceed_uid', $this->product->grupa_artikla)->first();

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
     * Return description with default language.
     * Language_id as response array key.
     *
     * @Implement Loop for more languages from config file.
     *
     * @return array
     */
    private function getDescriptionArray(): array
    {
        // Check if description exist.
        //If not add title for description.
        $description = str_replace("\n", '<br>', $this->product->opis);
        $spec = str_replace("\n", '<br>', $this->product->specifikacija);

        if ( ! $this->product->opis) {
            $description = $this->product->naziv;
        }

        $response[$this->default_language] = [
            'name'              => $this->product->naziv,
            'description'       => $description,
            'spec_description'  => $spec ?: '',
            'short_description' => $description,
            'tag'               => $this->product->naziv,
            'meta_title'        => $this->product->naziv,
            'meta_description'  => str_replace("\n", '. ', $this->product->opis),
            'meta_keyword'      => $this->product->naziv,
        ];

        return $response;
    }


    /**
     * Get the image string from luceed service and
     * return the full path string.
     *
     * @return string
     */
    private function getImagePath(int $key = 0): string
    {
        // Check if the image path exist.
        // Create it if not.
        if ( ! is_dir(DIR_IMAGE . $this->image_path)) {
            mkdir(DIR_IMAGE . $this->image_path, 0777, true);
        }

        // Setup and create the image with GD library.
        $name  = Str::slug($this->product->naziv) . '-'. strtoupper(Str::random(9)) . '.jpg';
        $bin   = base64_decode($this->getImageString($key));
        $image = imagecreatefromstring($bin);

        if ( ! $image) {
            return 'not_valid_image';
        }

        // Save the image in storage.
        imagejpeg($image, DIR_IMAGE . $this->image_path . $name, 90);
        //imagepng($image, DIR_IMAGE . $this->image_path . $name, 0);

        // Return only the image path.
        return $this->image_path . $name;
    }


    /**
     * Get the image string from luceed service
     *
     * @return mixed
     */
    private function getImageString(int $key)
    {
        $result = LuceedProduct::getImage($this->product->dokumenti[$key]->file_uid);

        $image = json_decode($result);

        return $image->result[0]->files[0]->content;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array|null
     */
    public function setProduct($product)
    {
        $prod = json_decode($product);

        if (isset($prod->result[0]->artikli[0])) {
            return $prod->result[0]->artikli[0];
        }

        return null;
    }
}