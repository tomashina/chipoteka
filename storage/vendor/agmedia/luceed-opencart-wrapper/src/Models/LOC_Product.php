<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductDescription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        if ($products !== null) {
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

            $this->products_to_add = $this->getProducts()->whereIn('artikl', $products);

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
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.price = pt.price, p.stock_status_id = pt.stock_id, p.status = IF(pt.quantity > 0, 1, p.status)");
        }
        if ($type == 'price' || $type == 'prices') {
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price");
        }
        if ($type == 'quantity' || $type == 'quantities') {
            $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.stock_status_id = pt.stock_id, p.status = IF(pt.quantity > 0, 1, p.status)");
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

        if ( ! count($luceed_products)) {
            throw new \UnexpectedValueException('Luceed product response does not contain any valid products.');
        }

        $target_table = DB_PREFIX . 'product_luceed';
        $staging_table = DB_PREFIX . 'product_luceed_sync';
        $insert_rows = [];

        $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $staging_table . "`");
        $db->query("CREATE TEMPORARY TABLE `" . $staging_table . "` LIKE `" . $target_table . "`");

        try {
            foreach ($luceed_products as $product) {
                $product_array = ProductHelper::collectLuceedData($product);
                $hash = ProductHelper::hashLuceedData($product_array);

                $insert_rows[] = '("' . $db->escape($product->artikl_uid) . '", "' .
                    $db->escape($product->artikl) . '", "' .
                    $db->escape(base64_encode(serialize($product_array))) . '", "' .
                    $db->escape($hash) . '")';

                $count++;

                if (count($insert_rows) >= 250) {
                    $db->query("INSERT INTO `" . $staging_table . "` (uid, sifra, `data`, `hash`) VALUES " . implode(',', $insert_rows));
                    $insert_rows = [];
                }
            }

            if (count($insert_rows)) {
                $db->query("INSERT INTO `" . $staging_table . "` (uid, sifra, `data`, `hash`) VALUES " . implode(',', $insert_rows));
            }

            $this->checkProductsToDeactivate();

            $db->query("TRUNCATE TABLE `" . $target_table . "`");
            $db->query("INSERT INTO `" . $target_table . "` (uid, sifra, `data`, `hash`) SELECT uid, sifra, `data`, `hash` FROM `" . $staging_table . "`");
        } catch (\Throwable $exception) {
            $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $staging_table . "`");

            throw $exception;
        }

        $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $staging_table . "`");

        $db->query("TRUNCATE TABLE " . DB_PREFIX . "product_luceed_for_update");
        $res = $db->query("SELECT p.luceed_uid FROM `" . DB_PREFIX . "product` p JOIN `" . DB_PREFIX . "product_luceed` pl ON p.luceed_uid = pl.uid WHERE p.hash <> pl.hash");

        if ($res->num_rows) {
            $insert_rows = [];

            foreach ($res->rows as $row) {
                $insert_rows[] = '("' . $db->escape($row['luceed_uid']) . '")';

                if (count($insert_rows) >= 500) {
                    $db->query("INSERT INTO `" . DB_PREFIX . "product_luceed_for_update` (uid) VALUES " . implode(',', $insert_rows));
                    $insert_rows = [];
                }
            }

            if (count($insert_rows)) {
                $db->query("INSERT INTO `" . DB_PREFIX . "product_luceed_for_update` (uid) VALUES " . implode(',', $insert_rows));
            }
        }

        $products_count = Product::pluck('sku')->count();

        return [
            'status' => 200,
            'total' => $count,
            'inserting' => max($count - $products_count, 0),
            'updating' => $res->num_rows,//floor($count - ($count - ($diff->num_rows / 2)))
        ];
    }


    /**
     * @return int
     */
    public function checkProductsToDeactivate()
    {
        $luc = $this->getProducts()
            ->where('artikl', '!=', '')
            ->where('naziv', '!=', '')
            ->where('webshop', '!=', 'N')
            ->pluck('artikl')
            ->flatten();

        if ($luc->count() > 0) {
            $all = Product::query()->get()->count();
            $existing = Product::query()
                ->pluck('sku')
                ->flatten();

            $diff = $existing->diff($luc);

            Log::store($diff->count(), 'diff_p');
            Log::store($diff, 'diff_p');

            if ($diff->count() < $all) {
                Product::query()->whereIn('sku', $diff)->update([
                    'status' => 0,

                ]);
            }

            return $diff->count();
        }

        return 0;
    }



    /**
     * @return $this
     */
    public function cleanRevisionTable($uids = null)
    {
        $exist = Product::pluck('sku');
        $revs = LuceedProductForRevision::pluck('sku');
        LuceedProductForRevision::whereIn('sku', $revs->diff($exist))->delete();

        if ($uids) {
            LuceedProductForRevision::whereIn('uid', $uids)->delete();
        } else {
            LuceedProductForRevision::truncate();
        }

        return $this;
    }


    /**
     * @return mixed
     */
    public function checkRevisionTable()
    {
        $db = new Database(DB_DATABASE);
        $descriptions = ProductDescription::where('description', '')->where('description', 'NOT LIKE', '% ')->pluck('product_id');
        $images = Product::where('image', '')->orWhere('image', 'catalog/products/no-image.jpg')->pluck('product_id');
        $insert = [];

        foreach ($descriptions as $item) {
            $insert[$item]['description'] = 0;
        }

        foreach ($images as $item) {
            $insert[$item]['image'] = 0;
        }

        LuceedProductForRevision::truncate();
        $products = Product::whereIn('product_id', collect($descriptions)->merge($images)->unique())->get();
        $query_str = '';

        foreach ($products as $product) {
            $has_image = isset($insert[$product->product_id]['image']) ? 0 : 1;
            $has_description = isset($insert[$product->product_id]['description']) ? 0 : 1;

            $query_str .= '("' . $product->luceed_uid . '", "' . $product->sku . '", "' . $db->escape($product->description(2)->first()->name) . '", ' . $has_image . ', ' . $has_description . ', 0, "", NOW(), NOW()),';
        }

        try {
            $db->query("INSERT INTO " . DB_PREFIX . "product_luceed_revision (uid, sku, `name`, has_image, has_description, resolved, `data`, date_added, date_modified) VALUES " . substr($query_str, 0, -1) . ";");
        } catch (\Exception $exception) {
            Log::store($exception->getMessage());
        }

        return $products->count();
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
        $product = collect($product);
        $manufacturer = ProductHelper::getManufacturer($product);
        $categories   = ProductHelper::getCategories($product);
        $stock_status = $product['stanje_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = 1;

        $description = ProductHelper::getDescription($product);
        $description[agconf('import.default_language')]['tag'] = ProductHelper::getTags($categories, $manufacturer);

        if ( ! $product['opis'] || empty($product['dokumenti'])) {
            $status = 0;
        }

        if ($product['enabled'] == 'N') {
            $status = 0;
        }

        $attributes = ProductHelper::getAttributes($product);
        $images = ProductHelper::getImages($product);
        $image_path = isset($images[0]['image']) ? $images[0]['image'] : 'image/placeholder.png';
        unset($images[0]);

        $prod = [
            'model'               => $product['artikl'],
            'sku'                 => $product['artikl'],
            'luceed_uid'          => $product['artikl_uid'],
            'upc'                 => $product['barcode'],
            'ean'                 => ProductHelper::setText($product['kataloski_broj']),
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $product['jamstvo_naziv'] ?: '',
            'location'            => '',
            'price'               => $product['mpc'],
            'price_2'             => $product['mpc'],
            'jm'                  => $product['jm'],
            'pakiranje'           => $product['pakiranje'],
            'vpc'                 => $product['vpc'],
            'tax_class_id'        => ProductHelper::getTax($product),
            'quantity'            => $product['stanje_kol'],
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
            'image'               => ! empty($product['dokumenti']) ? $image_path : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_attribute'   => $attributes,
            'product_description' => $description,
            'product_image'       => $images,
            'product_layout'      => [0 => ''],
            'product_category'    => $categories,
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($product)],
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
        if ( ! is_string($products) || trim($products) === '') {
            throw new \UnexpectedValueException('Luceed product service returned an empty response.');
        }

        $prods = json_decode($products);

        if (json_last_error() !== JSON_ERROR_NONE ||
            ! isset($prods->result[0]->artikli) ||
            ! is_array($prods->result[0]->artikli)) {
            throw new \UnexpectedValueException('Luceed product service returned an invalid response.');
        }

        return $prods->result[0]->artikli;
    }
}
