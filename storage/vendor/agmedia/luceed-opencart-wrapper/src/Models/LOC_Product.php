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
        $db = new Database(DB_DATABASE);
        $target_table = DB_PREFIX . 'product_luceed';
        $staging_table = DB_PREFIX . 'product_luceed_sync';
        $queue_table = DB_PREFIX . 'product_luceed_for_update';
        $queue_staging_table = DB_PREFIX . 'product_luceed_queue_sync';
        $transaction_started = false;

        try {
            // Identity/flag failures invalidate the whole feed. Content failures
            // are isolated per article before any persistent data is changed.
            $products = $this->validateSnapshotProducts($this->getProducts()->all());
            $existing = $db->query("SELECT DISTINCT luceed_uid FROM `" . DB_PREFIX . "product` WHERE luceed_uid IS NOT NULL AND luceed_uid <> ''");
            $existing_uids = [];

            foreach ($existing->rows as $product) {
                $existing_uids[(string)$product['luceed_uid']] = true;
            }

            $records = [];
            $skipped_products = [];

            foreach ($products as $product) {
                // Keep explicit webshop=N for existing products so their status
                // can be reconciled, without importing every non-web ERP item.
                if ($product->webshop === 'N' && ! isset($existing_uids[$product->artikl_uid])) {
                    continue;
                }

                try {
                    $this->validateSnapshotActivationData($product);
                    $product_array = ProductHelper::collectLuceedData($product);
                    $records[] = [
                        'uid' => $product->artikl_uid,
                        'sifra' => $product->artikl,
                        'data' => base64_encode(serialize($product_array)),
                        'hash' => ProductHelper::hashLuceedData($product_array),
                        'eligible' => ProductHelper::isLuceedProductEligible($product_array) ? 1 : 0,
                        'min_quantity' => ProductHelper::getActivationMinimum($product_array),
                    ];
                } catch (\UnexpectedValueException $exception) {
                    // Omitting an untrusted article from the new snapshot leaves
                    // its current status/stock untouched; never guess its data.
                    $skipped_products[] = [
                        'sku' => $product->artikl,
                        'uid' => $product->artikl_uid,
                        'reason' => $exception->getMessage(),
                    ];
                }
            }

            if ($skipped_products) {
                Log::store(['count' => count($skipped_products), 'products' => $skipped_products], 'luceed-product-snapshot-skipped');
            }

            $count = count($records);
            $previous = $db->query("SELECT COUNT(*) AS total FROM `" . $target_table . "`");
            $previous_count = (int)($previous->row['total'] ?? 0);
            // Retired products can remain mapped to Luceed long after they leave
            // its web feed. Only published managed products provide a fallback
            // catalog size when no complete previous snapshot is available.
            $active = $db->query("SELECT COUNT(DISTINCT luceed_uid) AS total FROM `" . DB_PREFIX . "product`
                WHERE status = 1 AND luceed_uid IS NOT NULL AND luceed_uid <> ''");
            $active_count = (int)($active->row['total'] ?? 0);
            $baseline = max($previous_count, $active_count);

            if ($count === 0 || ($baseline > 0 && $count < $baseline * 0.8)) {
                Log::store([
                    'received' => count($products),
                    'retained' => $count,
                    'previous' => $previous_count,
                    'managed' => count($existing_uids),
                    'active_managed' => $active_count,
                    'skipped_products' => count($skipped_products),
                    'minimum_ratio' => 0.8,
                ], 'luceed-product-snapshot-rejected');

                throw new \UnexpectedValueException('Luceed product snapshot is empty or more than 20% smaller than the known catalog. ' .
                    'Counts: received=' . count($products) . ', retained=' . $count . ', previous=' . $previous_count .
                    ', active_managed=' . $active_count . ', skipped=' . count($skipped_products) . '.');
            }

            // Legacy installs use MyISAM. DDL must finish before START TRANSACTION.
            $this->ensureSnapshotSchema($db, $target_table);
            $this->ensureSnapshotTransactionalTable($db, $target_table);
            $this->ensureSnapshotTransactionalTable($db, $queue_table);
            $this->ensureSnapshotUidIndex($db, $target_table);
            $this->ensureSnapshotUidIndex($db, $queue_table);

            $accepted = $db->query('SELECT NOW() AS synced_at');
            $synced_at = $db->escape($accepted->row['synced_at']);
            $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $staging_table . "`");
            $db->query("CREATE TEMPORARY TABLE `" . $staging_table . "` LIKE `" . $target_table . "`");
            $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $queue_staging_table . "`");
            $db->query("CREATE TEMPORARY TABLE `" . $queue_staging_table . "` LIKE `" . $queue_table . "`");

            foreach (array_chunk($records, 250) as $chunk) {
                $insert_rows = [];

                foreach ($chunk as $record) {
                    $insert_rows[] = "('" . $db->escape($record['uid']) . "', '" .
                        $db->escape($record['sifra']) . "', '" . $db->escape($record['data']) . "', '" .
                        $db->escape($record['hash']) . "', " . $record['eligible'] . ', ' .
                        (int)$record['min_quantity'] . ", '" . $synced_at . "')";
                }

                $db->query("INSERT INTO `" . $staging_table . "` (uid, sifra, `data`, `hash`, eligible, min_quantity, synced_at) VALUES " . implode(',', $insert_rows));
            }

            $staged = $db->query("SELECT COUNT(*) AS total FROM `" . $staging_table . "`");

            if ((int)($staged->row['total'] ?? 0) !== $count) {
                throw new \RuntimeException('Luceed product snapshot staging count does not match the validated feed.');
            }

            $db->query('START TRANSACTION');
            $transaction_started = true;

            // Retain pending work still represented by this feed, including jobs
            // whose hash was already marked checked before an interrupted run.
            $db->query("INSERT INTO `" . $queue_staging_table . "` (uid)
                SELECT DISTINCT p.luceed_uid
                FROM `" . DB_PREFIX . "product` p
                INNER JOIN `" . $staging_table . "` pl ON p.luceed_uid = pl.uid
                LEFT JOIN `" . $queue_table . "` q ON p.luceed_uid = q.uid
                WHERE q.uid IS NOT NULL OR NOT (p.hash <=> pl.hash)");

            // Preserve explicit pending work for known, skipped articles. Workers
            // select pending UIDs joined to the cache, so these wait safely until
            // a later complete feed restores their data, even with the same hash.
            foreach (array_chunk(array_column($skipped_products, 'uid'), 500) as $chunk) {
                $uids = [];

                foreach ($chunk as $uid) {
                    $uids[] = "'" . $db->escape($uid) . "'";
                }

                $db->query("INSERT INTO `" . $queue_staging_table . "` (uid)
                    SELECT DISTINCT q.uid FROM `" . $queue_table . "` q
                    INNER JOIN `" . DB_PREFIX . "product` p ON p.luceed_uid = q.uid
                    WHERE q.uid IN (" . implode(',', $uids) . ")");
            }

            $queued = $db->query("SELECT COUNT(*) AS total FROM `" . $queue_staging_table . "`");
            $inserting = $db->query("SELECT COUNT(*) AS total FROM `" . $staging_table . "` pl
                LEFT JOIN `" . DB_PREFIX . "product` p ON p.luceed_uid = pl.uid WHERE p.product_id IS NULL");

            $columns = 'uid, sifra, `data`, `hash`, eligible, min_quantity, synced_at';
            $db->query("DELETE FROM `" . $target_table . "`");
            $db->query("INSERT INTO `" . $target_table . "` (" . $columns . ") SELECT " . $columns . " FROM `" . $staging_table . "`");
            $db->query("DELETE FROM `" . $queue_table . "`");
            $db->query("INSERT INTO `" . $queue_table . "` (uid) SELECT uid FROM `" . $queue_staging_table . "`");

            $db->query('COMMIT');
            $transaction_started = false;

            return [
                'status' => 200,
                'total' => $count,
                'inserting' => (int)$inserting->row['total'],
                'updating' => (int)$queued->row['total'],
                'skipped_products' => count($skipped_products),
            ];
        } catch (\Throwable $exception) {
            if ($transaction_started) {
                try {
                    $db->query('ROLLBACK');
                } catch (\Throwable $rollback_error) {
                    Log::store($rollback_error->getMessage(), 'luceed-product-snapshot-rollback-error');
                }
            }

            Log::store($exception->getMessage(), 'luceed-product-snapshot-error');

            throw $exception;
        } finally {
            foreach ([$staging_table, $queue_staging_table] as $table) {
                try {
                    $db->query("DROP TEMPORARY TABLE IF EXISTS `" . $table . "`");
                } catch (\Throwable $cleanup_error) {
                    Log::store($cleanup_error->getMessage(), 'luceed-product-snapshot-cleanup-error');
                }
            }
        }
    }


    /**
     * @deprecated Absence from an API response never proves ERP deactivation.
     * @return int
     */
    public function checkProductsToDeactivate()
    {
        return 0;
    }


    private function validateSnapshotProducts(array $products): array
    {
        if ( ! $products) {
            throw new \UnexpectedValueException('Luceed product response does not contain any products.');
        }

        $uids = [];
        $skus = [];

        foreach ($products as $index => $product) {
            if ( ! $product instanceof \stdClass) {
                throw new \UnexpectedValueException('Luceed product response contains a non-object record at ' . $index . '.');
            }

            foreach (['artikl_uid', 'artikl', 'naziv'] as $field) {
                if ( ! isset($product->{$field}) || ! is_scalar($product->{$field}) || trim((string)$product->{$field}) === '') {
                    throw new \UnexpectedValueException('Luceed product response has an empty ' . $field . ' at record ' . $index . '.');
                }

                $product->{$field} = trim((string)$product->{$field});
            }

            if (strlen($product->artikl_uid) > 191 || strlen($product->artikl) > 191 ||
                isset($uids[$product->artikl_uid]) || isset($skus[$product->artikl])) {
                throw new \UnexpectedValueException('Luceed product response contains duplicate or oversized identifiers at record ' . $index . '.');
            }

            $uids[$product->artikl_uid] = true;
            $skus[$product->artikl] = true;

            foreach (['enabled', 'webshop'] as $field) {
                if ( ! isset($product->{$field}) || ! in_array($product->{$field}, ['D', 'N'], true)) {
                    throw new \UnexpectedValueException('Luceed product response has an invalid ' . $field . ' flag for ' . $product->artikl . '.');
                }
            }

        }

        return $products;
    }


    private function validateSnapshotActivationData(\stdClass $product): void
    {
        // Luceed omits empty child collections. This matches collectLuceedData's
        // existing empty() handling; present malformed collections remain errors.
        foreach (['dokumenti', 'atributi'] as $field) {
            if ( ! property_exists($product, $field) || $product->{$field} === null) {
                $product->{$field} = [];
            }
        }

        foreach (['opis', 'mpc'] as $field) {
            if ( ! property_exists($product, $field)) {
                throw new \UnexpectedValueException('Missing activation field ' . $field . '.');
            }
        }

        if ($product->opis !== null && ! is_string($product->opis)) {
            throw new \UnexpectedValueException('Invalid activation field opis.');
        }

        // Explicit null is a legitimate unavailable price and remains ineligible.
        // A missing or malformed price must never become an assumed zero price.
        if ($product->mpc !== null && (! is_numeric($product->mpc) || ! is_finite((float)$product->mpc))) {
            throw new \UnexpectedValueException('Invalid activation field mpc.');
        }

        foreach (['dokumenti' => ['file_uid', 'filename', 'naziv', 'md5'],
            'atributi' => ['atribut_uid', 'naziv', 'aktivan', 'vidljiv', 'vrijednost']] as $field => $required) {
            if ($product->{$field} !== null && ! is_array($product->{$field})) {
                throw new \UnexpectedValueException('Invalid activation field ' . $field . '.');
            }

            foreach (($product->{$field} ?? []) as $index => $entry) {
                if ( ! $entry instanceof \stdClass) {
                    throw new \UnexpectedValueException('Invalid activation entry ' . $field . '[' . $index . '].');
                }

                foreach ($required as $key) {
                    if ( ! property_exists($entry, $key) || ($entry->{$key} !== null && ! is_scalar($entry->{$key}))) {
                        throw new \UnexpectedValueException('Missing or invalid activation field ' . $field . '[' . $index . '].' . $key . '.');
                    }
                }
            }
        }
    }


    private function ensureSnapshotSchema(Database $db, string $table): void
    {
        $result = $db->query("SHOW COLUMNS FROM `" . $table . "`");
        $columns = array_column($result->rows, 'Field');
        $changes = [];

        if ( ! in_array('eligible', $columns, true)) {
            $changes[] = 'ADD COLUMN `eligible` TINYINT(1) NOT NULL DEFAULT 0';
        }

        if ( ! in_array('synced_at', $columns, true)) {
            $changes[] = 'ADD COLUMN `synced_at` DATETIME NULL DEFAULT NULL';
        }

        if ( ! in_array('min_quantity', $columns, true)) {
            $changes[] = 'ADD COLUMN `min_quantity` INT UNSIGNED NOT NULL DEFAULT 0';
        }

        if ($changes) {
            $db->query("ALTER TABLE `" . $table . "` " . implode(', ', $changes));
        }
    }


    private function ensureSnapshotTransactionalTable(Database $db, string $table): void
    {
        $query = "SELECT ENGINE FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $db->escape($table) . "' LIMIT 1";
        $result = $db->query($query);
        $engine = strtoupper((string)($result->row['ENGINE'] ?? ''));

        if ($engine === 'MYISAM') {
            $db->query("ALTER TABLE `" . $table . "` ENGINE=InnoDB");
            $result = $db->query($query);
            $engine = strtoupper((string)($result->row['ENGINE'] ?? ''));
        }

        if ($engine !== 'INNODB') {
            throw new \RuntimeException('Luceed product snapshot requires an InnoDB table: ' . $table);
        }
    }


    private function ensureSnapshotUidIndex(Database $db, string $table): void
    {
        $indexes = $db->query("SHOW INDEX FROM `" . $table . "`");

        foreach ($indexes->rows as $index) {
            if ((int)$index['Seq_in_index'] === 1 && $index['Column_name'] === 'uid') {
                return;
            }
        }

        $db->query("ALTER TABLE `" . $table . "` ADD INDEX `idx_luceed_uid` (`uid`)");
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

        $description = ProductHelper::getDescription($product);
        $description[agconf('import.default_language')]['tag'] = ProductHelper::getTags($categories, $manufacturer);

        $attributes = ProductHelper::getAttributes($product);
        $images = ProductHelper::getImages($product);
        $image_path = isset($images[0]['image']) ? $images[0]['image'] : 'image/placeholder.png';
        unset($images[0]);
        $status = $this->resolveImportStatus($product->toArray(), $image_path);

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


    private function resolveImportStatus(array $product, string $image): int
    {
        $image = trim($image);
        $placeholder = (string)agconf('import.image_placeholder');

        if ($image === '' || $image === 'not_valid_image' || $image === $placeholder ||
            preg_match('~(^|/)(no[_-]?image|placeholder)\.(png|jpe?g|gif|webp)$~i', $image)) {
            return 0;
        }

        return ProductHelper::isLuceedProductEligible($product) &&
            (float)($product['stanje_kol'] ?? 0) >= ProductHelper::getActivationMinimum($product) ? 1 : 0;
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
