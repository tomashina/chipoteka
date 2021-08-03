<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Models\Attribute\Attribute;
use Agmedia\Models\Attribute\AttributeDescription;
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
class LOC_Product
{

    /**
     * @var array
     */
    private $products;

    /**
     * @var array
     */
    private $product;

    /**
     * @var array
     */
    private $existing;

    /**
     * @var array
     */
    private $products_to_add = [];

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
    public function __construct($products)
    {
        $this->products         = $this->setProducts($products);
        $this->default_category = agconf('import.default_category');
        $this->default_language = agconf('import.default_language');
        $this->image_path       = agconf('import.image_path');
    }


    /**
     * @return Collection
     */
    public function getProducts(): Collection
    {
        return collect($this->products);
    }


    /**
     * @return Collection
     */
    public function getProductsToAdd(): Collection
    {
        return collect($this->products_to_add);
    }


    /**
     * Check the difference between new,
     * and already imported products.
     *
     * @return $this
     */
    public function checkDiff()
    {
        // List of existing product identifiers.
        $this->existing = Product::pluck('sku');

        // List of product identifiers without
        // existing products.
        $list_diff = $this->getProducts()
                          ->where('artikl', '!=', '')
                          ->where('naziv', '!=', '')
                          ->where('enabled', '!=', 'N')
                          ->where('webshop', '!=', 'N')
                          ->pluck('artikl')
                          ->diff($this->existing)
                          ->flatten();

        // Full list of products to add to DB.
        $this->products_to_add = $this->getProducts()->whereIn('artikl', $list_diff);

        return $this;
    }


    /**
     * Get some data only from products
     * that are in local Database.
     *
     * @return $this
     */
    public function sortForUpdate()
    {
        // List of existing product identifiers.
        $this->existing = Product::pluck('sku')->flatten();
        // Full list of products to update.
        $this->products_to_add = $this->getProducts()->whereIn('artikl', $this->existing);

        return $this;
    }


    /**
     * @param string $type
     *
     * @return false
     * @throws \Exception
     */
    public function update(string $type = 'all')
    {
        $db = new Database(DB_DATABASE);

        if ($type != 'quantity' || $type != 'quantities') {
            $updated = $this->updateOptionsPrices();
        }

        // If the options are not updated return false.
        if ( ! $updated) {
            return false;
        }

        // Sort the temporary products DB import string.
        // (uid, price, quantity, stock_id)
        $query_str = '';
        foreach ($this->products_to_add as $item) {
            $stock           = $item->stanje_kol ? $item->stanje_kol : 0;
            $stock_status_id = $item->stanje_kol ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
            $query_str       .= '("' . $item->artikl . '", ' . $item->mpc . ', ' . $stock . ', ' . $stock_status_id . '),';
        }

        $db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, price, quantity, stock_id) VALUES " . substr($query_str, 0, -1) . ";");

        // Check wich type of update to conduct.
        // Price and quantity or each individualy?
        if ($type == 'all') {
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.price = pt.price, p.stock_status_id = pt.stock_id");
        }
        if ($type == 'price' || $type == 'prices') {
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price");
        }
        if ($type == 'quantity' || $type == 'quantities') {
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.stock_status_id = pt.stock_id");
        }

        // Truncate the product_temp table.
        $db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");

        // Return products count if updated.
        // False if update error occurs.
        if ($updated) {
            return $this->products_to_add->count();
        }

        return false;
    }


    /**
     * @return bool
     * @throws \Exception
     */
    private function updateOptionsPrices()
    {
        $db = new Database(DB_DATABASE);

        $query_str = '';
        $uids      = $this->products_to_add->pluck('artikl')->flatten();
        $products  = Product::whereIn('model', $uids)->with('options')->get();

        foreach ($products as $product) {
            $new_price = $this->products_to_add->where('artikl', $product->model)->first()->mpc;

            if ($product->price != $new_price) {
                if ($product->options) {
                    foreach ($product->options as $option) {
                        $price = $new_price * $option->weight;

                        $query_str .= '("' . $option->product_option_value_id . '", ' . $price . ', 0, 0),';
                    }
                }
            }
        }

        if ($query_str == '') {
            return true;
        }

        $db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, price, quantity, stock_id) VALUES " . substr($query_str, 0, -1) . ";");

        $updated = $db->query("UPDATE " . DB_PREFIX . "product_option_value p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.product_option_value_id = pt.uid SET p.price = pt.price");

        $db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");

        return $updated ? true : false;
    }


    /**
     * Collect, make and sort the data
     * for 1 products to make.
     *
     * @param $product
     *
     * @return array
     */
    public function make($product): array
    {
        $this->product = $product;
        $manufacturer  = $this->getManufacturer();
        $stock_status  = $this->product->stanje_kol ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');

        $prod = [
            'model'               => $this->product->artikl,
            'sku'                 => $this->product->artikl,
            'luceed_uid'          => $this->product->artikl_uid,
            'upc'                 => $this->product->barcode,
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $this->product->jamstvo_naziv,
            'location'            => '',
            'price'               => $this->product->mpc,
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $this->product->stanje_kol,
            'minimum'             => 1,
            'subtract'            => 1,
            'stock_status_id'     => $stock_status,
            'shipping'            => 1,
            'date_available'      => Carbon::now()->subDay()->format('Y-m-d'),
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
            'product_attribute'   => $this->getAttributes(),
            'product_description' => $this->getDescriptionArray(),
            'product_image'       => $this->getImages(),
            'product_layout'      => [0 => ''],
            'product_category'    => $this->getCategories(),
            'product_seo_url'     => [0 => $this->getSeoUrl()],
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
        $spec        = str_replace("\n", '<br>', $this->product->specifikacija);
        $clean       = str_replace("\n", '. ', $this->product->opis);

        if ( ! $this->product->opis) {
            $description = $this->product->naziv;
        }

        $response[$this->default_language] = [
            'name'              => $this->product->naziv,
            'description'       => $description,
            'spec_description'  => $spec ?: '',
            'short_description' => substr($clean, 0, 300),
            'tag'               => $this->product->naziv,
            'meta_title'        => $this->product->naziv,
            'meta_description'  => $clean,
            'meta_keyword'      => $this->product->naziv,
        ];

        return $response;
    }


    /**
     * @return array
     */
    private function getSeoUrl(): array
    {
        $slug = Str::slug($this->product->naziv) . '-' . $this->product->artikl;

        return [
            $this->default_language => $slug
        ];
    }


    /**
     * @return array
     */
    private function getAttributes(): array
    {
        $response   = [];
        $attributes = collect($this->product->atributi);

        foreach ($attributes as $attribute) {
            if ($this->checkAttributeForImport($attribute)) {
                $has = Attribute::where('luceed_uid', $attribute->atribut_uid)->first();

                if ($has && $has->count()) {
                    $id = $has->attribute_id;
                } else {
                    $id = $this->makeAttribute($attribute);
                }

                if ($id) {
                    $response[] = [
                        'attribute_id' => $id,
                        'product_attribute_description' => [
                            $this->default_language => [
                                'text' => $attribute->vrijednost
                            ]
                        ]
                    ];
                }
            }
        }

        return $response;
    }


    /**
     * @param $attribute
     *
     * @return bool
     */
    private function checkAttributeForImport($attribute): bool
    {
        if ($attribute->aktivan == 'D' &&
            $attribute->vidljiv == 'D' &&
            $attribute->atribut_uid != '' &&
            $attribute->naziv != '')
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
    private function makeAttribute($attribute)
    {
        $id = Attribute::insertGetId([
            'luceed_uid' => $attribute->atribut_uid,
            'attribute_group_id' => agconf('import.default_attribute_group'),
            'sort_order' => $attribute->redoslijed
        ]);

        if ($id) {
            AttributeDescription::insert([
                'attribute_id' => $id,
                'language_id' => $this->default_language,
                'name' => $attribute->naziv
            ]);

            return $id;
        }

        return false;
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
        $name  = Str::slug($this->product->naziv) . '-' . strtoupper(Str::random(9)) . '.jpg';
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
     * @return array
     */
    private function setProducts($products): array
    {
        $prods = json_decode($products);

        return $prods->result[0]->artikli;
    }
}