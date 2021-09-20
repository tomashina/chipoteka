<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
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
     * @var array
     */
    private $products_to_update = [];

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
    public function __construct($products = null)
    {
        if ($products) {
            $this->products = $this->setProducts($products);
        }

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
    public function sortForUpdate(string $products = null)
    {
        if ($products) {
            $products = str_replace('&quot;', '', $products);
            $products = explode(',', $products);

            $this->products_to_add = $this->getProducts()->whereIn('artikl_uid', $products);

            return $this;
        }
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
     * @return int
     * @throws \Exception
     */
    public function populateLuceedData()
    {
        $count = 0;
        $db = new Database(DB_DATABASE);

        $luceed_products = $this->getProducts()
                                ->where('artikl', '!=', '')
                                ->where('naziv', '!=', '')
                                ->where('webshop', '!=', 'N')
                                ->all();

        $query_str = '';

        foreach ($luceed_products as $product) {
            $data = collect($product)->toJson();

            $query_str .= '("' . $product->artikl_uid . '", "' . $product->artikl . '", "' . htmlspecialchars($data) . '", "' . sha1($data) . '"),';

            $count++;
        }

        $db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_luceed`");
        $db->query("INSERT INTO " . DB_PREFIX . "product_luceed (uid, sifra, `data`, `hash`) VALUES " . substr($query_str, 0, -1) . ";");

        $diff = $db->query("SELECT uid, `hash`
                                FROM (
                                SELECT uid, `hash` FROM oc_product_luceed
                                UNION ALL
                                SELECT luceed_uid, `hash` FROM oc_product
                                ) tbl
                                GROUP BY uid, `hash`
                                HAVING count(*) = 1
                                ORDER BY uid;");

        Log::store($count);
        Log::store($diff->num_rows);

        return [
            'status' => 200,
            'total' => $count,
            'updating' => $count - ($count - ($diff->num_rows / 2))
        ];
    }


    /**
     * @return $this
     */
    public function cleanRevisionTable()
    {
        LuceedProductForRevision::truncate();

        return $this;
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
        $manufacturer  = ProductHelper::getManufacturer($this->product);
        $stock_status  = $this->product->stanje_kol ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');

        $status       = 1;

        $description = ProductHelper::getDescription($this->product);

        if (empty($this->product['opis']) && empty($this->product['dokumenti'])) {
            $status = 0;
            //$this->pushToRevision();
        }

        if ($this->product['enabled'] == 'N') {
            $status = 0;
        }

        $prod = [
            'model'               => $this->product['artikl'],
            'sku'                 => $this->product['artikl'],
            'luceed_uid'          => $this->product['artikl_uid'],
            'upc'                 => $this->product['barcode'],
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $this->product['jamstvo_naziv'] ?: '',
            'location'            => '',
            'price'               => $this->product['mpc'],
            'price_2'             => $this->product['mpc'],
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $this->product['stanje_kol'],
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
            'status'              => $status,
            'sort_order'          => 0,
            'manufacturer'        => $manufacturer['name'],
            'manufacturer_id'     => $manufacturer['id'],
            'category'            => '',
            'filter'              => '',
            'download'            => '',
            'related'             => '',
            'image'               => ! empty($this->product['dokumenti']) ? ProductHelper::getImagePath($this->product) : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_attribute'   => ProductHelper::getAttributes($this->product),
            'product_description' => $description,
            'product_image'       => ProductHelper::getImages($this->product),
            'product_layout'      => [0 => ''],
            'product_category'    => ProductHelper::getCategories($this->product),
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($this->product)],
        ];

        return $prod;
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