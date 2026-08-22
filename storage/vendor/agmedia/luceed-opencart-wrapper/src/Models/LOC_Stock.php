<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Product\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Stock
{

    private const MIN_WAREHOUSE_PRODUCT_COUNT = 6800;
    private const MIN_MAIN_SUPPLIER_PRODUCT_COUNT = 8600;
    private const MIN_STAGED_MATCH_RATIO = 0.50;
    private const MAX_PRODUCT_QUANTITY = 2147483647;
    private const PRODUCT_STOCK_TEMP_TABLE = 'luceed_product_stock_tmp';

    /**
     * @var Database
     */
    private $db;

    /**
     * @var
     */
    public $dobavljaci = null;

    /**
     * @var
     */
    public $skladista = null;

    /**
     * @var null
     */
    private $skladista_stock = null;

    /**
     * @var null
     */
    private $dobavljaci_stock = null;

    /**
     * @var null
     */
    private $skladista_sorted = null;

    private $dobavljaci_sorted = null;

    private $skladista_query = '';

    private $dobavljaci_query = '';

    private $warehouse_stock = null;

    private $warehouse_stock_sorted = null;

    private $log_name;

    private $minimum_warehouse_product_count;

    private $minimum_supplier_product_count;

    private $minimum_staged_match_ratio;

    private $skladista_valid = true;
    private $dobavljaci_valid = true;

    public function isValid(): bool
    {
        return $this->skladista_valid && $this->dobavljaci_valid;
    }


    /**
     * LOC_Product constructor.
     */
    public function __construct(?Database $db = null, array $safety_limits = array())
    {
        $this->db = $db ?: new Database(DB_DATABASE);
        $this->log_name = 'stock' . date('-d-H');
        $this->minimum_warehouse_product_count = isset($safety_limits['warehouse_products'])
            ? max((int)$safety_limits['warehouse_products'], 1)
            : self::MIN_WAREHOUSE_PRODUCT_COUNT;
        $this->minimum_supplier_product_count = isset($safety_limits['supplier_products'])
            ? max((int)$safety_limits['supplier_products'], 1)
            : self::MIN_MAIN_SUPPLIER_PRODUCT_COUNT;
        $this->minimum_staged_match_ratio = isset($safety_limits['match_ratio'])
            ? max(min((float)$safety_limits['match_ratio'], 1), 0)
            : self::MIN_STAGED_MATCH_RATIO;
    }


    /**
     * @return $this
     */
    public function sort()
    {
        // SKLADIŠTA
        if ($this->skladista && ! $this->skladista_sorted) {
            if ( ! $this->skladista_stock) {
                $this->skladista_stock = collect();
            }

            $quantities = array();

            foreach ($this->skladista as $stock) {
                $key = $this->normalizeIdentifier($stock->artikl_uid ?? '');

                if ($key === '') {
                    continue;
                }

                $value = $stock->raspolozivo_kol ?? null;

                if ( ! is_numeric($value)) {
                    $this->skladista_valid = false;
                    Log::store(['artikl_uid' => $key, 'value' => $value], $this->log_name . '-SKLADISTA-INVALID-QUANTITY');
                    continue;
                }

                if ( ! isset($quantities[$key])) {
                    $quantities[$key] = 0;
                }

                $quantities[$key] += max((float)$value, 0);
            }

            foreach ($quantities as $key => $qty) {
                $qty = $this->normalizeQuantity($qty);
                $stock_status = $qty > 0
                    ? agconf('import.default_stock_full')
                    : agconf('import.default_stock_empty');

                $this->skladista_stock->push([
                    'artikl_uid' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $stock_status
                ]);
            }

            if ($this->skladista_stock->count() < $this->minimum_warehouse_product_count) {
                $this->skladista_valid = false;
                Log::store([
                    'products' => $this->skladista_stock->count(),
                    'minimum'  => $this->minimum_warehouse_product_count
                ], $this->log_name . '-SKLADISTA-TOO-SMALL');
            }

            $this->skladista_sorted = true;

            //Log::store($this->skladista_stock->toArray(), $this->log_name . '-sorted');
        }

        if ($this->skladista && ! $this->warehouse_stock_sorted) {
            if ( ! $this->warehouse_stock) {
                $this->warehouse_stock = collect();
            }

            $warehouse_codes_by_uid = $this->getWarehouseCodesByUid();
            $warehouse_stock = array();
            $unknown_warehouse_uids = array();

            if ( ! $warehouse_codes_by_uid) {
                $this->skladista_valid = false;
                Log::store('Warehouse code map is empty.', $this->log_name . '-WAREHOUSE-CODES-EMPTY');
            }

            foreach ($this->skladista as $stock) {
                $warehouse_uid = $this->normalizeIdentifier($stock->skladiste_uid ?? '');
                $artikl_uid = $this->normalizeIdentifier($stock->artikl_uid ?? '');
                $warehouse_code = $warehouse_codes_by_uid[$warehouse_uid] ?? '';

                if ($warehouse_uid === '' || $warehouse_code === '' || $artikl_uid === '') {
                    $unknown_warehouse_uids[$warehouse_uid ?: '(empty)'] = true;
                    continue;
                }

                $group_key = $warehouse_code . '|' . $artikl_uid;

                if ( ! isset($warehouse_stock[$group_key])) {
                    $warehouse_stock[$group_key] = array(
                        'artikl_uid'    => $artikl_uid,
                        'warehouse_uid' => $warehouse_uid,
                        'warehouse_code'=> $warehouse_code,
                        'quantity'      => 0
                    );
                }

                $warehouse_stock[$group_key]['quantity'] += max((float)($stock->raspolozivo_kol ?? 0), 0);
            }

            if ($unknown_warehouse_uids) {
                $this->skladista_valid = false;
                Log::store([
                    'count' => count($unknown_warehouse_uids),
                    'uids'  => array_slice(array_keys($unknown_warehouse_uids), 0, 20)
                ], $this->log_name . '-WAREHOUSE-CODES-UNKNOWN');
            }

            foreach ($warehouse_stock as $item) {
                $item['quantity'] = $this->normalizeQuantity($item['quantity']);

                if ($item['quantity'] > 0) {
                    $this->warehouse_stock->push($item);
                }
            }

            $this->warehouse_stock_sorted = true;
        }


        // DOBAVLJAČI
        if ($this->dobavljaci && ! $this->dobavljaci_sorted) {
            if ( ! $this->dobavljaci_stock) {
                $this->dobavljaci_stock = collect();
            }

            $quantities = array();

            foreach ($this->dobavljaci as $stock) {
                if ((string)($stock->main ?? '') !== 'D') {
                    continue;
                }

                $key = $this->normalizeIdentifier($stock->sifra_artikla ?? '');

                if ($key === '') {
                    continue;
                }

                $value = $stock->dobavljac_stanje ?? null;

                if ( ! is_numeric($value)) {
                    $this->dobavljaci_valid = false;
                    Log::store(['artikl' => $key, 'value' => $value], $this->log_name . '-DOBAVLJACI-INVALID-QUANTITY');
                    continue;
                }

                if ( ! isset($quantities[$key])) {
                    $quantities[$key] = 0;
                }

                $quantities[$key] += max((float)$value, 0);
            }

            foreach ($quantities as $key => $qty) {
                $qty = $this->normalizeQuantity($qty);
                $stock_status = $qty > 0
                    ? agconf('import.default_stock_full')
                    : agconf('import.default_stock_empty');

                $this->dobavljaci_stock->push([
                    'artikl' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $stock_status
                ]);
            }

            if ($this->dobavljaci_stock->count() < $this->minimum_supplier_product_count) {
                $this->dobavljaci_valid = false;
                Log::store([
                    'products' => $this->dobavljaci_stock->count(),
                    'minimum'  => $this->minimum_supplier_product_count
                ], $this->log_name . '-DOBAVLJACI-TOO-SMALL');
            }

            $this->dobavljaci_sorted = true;

            //Log::store($this->dobavljaci_stock->toArray(), $this->log_name . '-sorted');
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function createQuery()
    {
        if ( ! $this->isValid()) {
            return $this;
        }

        // ako nema ničega za upis -> neće se ni update dogoditi
        if ($this->skladista_sorted && $this->skladista_stock && $this->skladista_stock->isEmpty()) {
            $this->skladista_query = '';
            return $this;
        }

        if ($this->skladista_sorted && $this->skladista_stock) {
            foreach ($this->skladista_stock as $item) {
                $this->skladista_query .= "('" . $this->db->escape($item['artikl_uid']) . "', " . (int)$item['stanje_kol'] . ', ' . (int)$item['stock_status'] . '),';
            }
        }

        if ($this->dobavljaci_sorted && $this->dobavljaci_stock) {
            foreach ($this->dobavljaci_stock as $item) {
                $this->dobavljaci_query .= "('" . $this->db->escape($item['artikl']) . "', " . (int)$item['stanje_kol'] . ', ' . (int)$item['stock_status'] . '),';
            }
        }

        Log::store($this->skladista_query, $this->log_name . '-query');
        Log::store($this->dobavljaci_query, $this->log_name . '-query');

        return $this;
    }


    /**
     * @return int
     */
    public function update(): int
    {
        if ( ! $this->isValid() || $this->skladista_query === '' || $this->dobavljaci_query === '') {
            Log::store('SKIP UPDATE: invalid or incomplete stock feed', $this->log_name . '-SKIP');
            return 0;
        }

        $stock_full = (int)agconf('import.default_stock_full');
        $stock_empty = (int)agconf('import.default_stock_empty');

        $transaction_started = false;

        try {
            // Build both feeds completely before any persistent data is touched.
            // TEMPORARY tables are private to this DB connection, unlike the
            // legacy product_temp table shared by price and product cron jobs.
            $this->prepareProductStockTempTable(true);

            // CREATE TABLE can implicitly commit in MySQL, so it must happen
            // before the transaction that updates the persistent tables.
            $this->ensureWarehouseStockTable();
            $this->assertTransactionalTable(DB_PREFIX . 'product');
            $this->assertTransactionalTable(DB_PREFIX . 'product_warehouse_stock');

            $this->db->query('START TRANSACTION');
            $transaction_started = true;

            $this->db->query(
                "UPDATE " . DB_PREFIX . "product p
                INNER JOIN `" . self::PRODUCT_STOCK_TEMP_TABLE . "` ps ON p.product_id = ps.product_id
                SET p.quantity = ps.quantity,
                    p.stock_status_id = IF(ps.quantity > 0, " . $stock_full . ", " . $stock_empty . ")"
            );

            $this->syncWarehouseStockTable(false);

            $this->db->query('COMMIT');
            $transaction_started = false;

            return 1;
        } catch (\Throwable $e) {
            if ($transaction_started) {
                try {
                    $this->db->query('ROLLBACK');
                } catch (\Throwable $rollback_error) {
                    Log::store($rollback_error->getMessage(), $this->log_name . '-ROLLBACK-FAILED');
                }
            }

            Log::store($e->getMessage(), $this->log_name . '-UPDATE-FAILED');

            throw $e;
        } finally {
            $this->dropProductStockTempTable();
        }
    }

    /**
     * @return int
     */
    public function updateWithoutSupliers(): int
    {
        if ( ! $this->skladista_valid || $this->skladista_query === '') {
            Log::store('SKIP UPDATE: invalid or incomplete warehouse feed', $this->log_name . '-SKIP');
            return 0;
        }

        $transaction_started = false;

        try {
            $this->prepareProductStockTempTable(false);
            $this->ensureWarehouseStockTable();
            $this->assertTransactionalTable(DB_PREFIX . 'product');
            $this->assertTransactionalTable(DB_PREFIX . 'product_warehouse_stock');

            $this->db->query('START TRANSACTION');
            $transaction_started = true;

            $this->db->query(
                "UPDATE " . DB_PREFIX . "product p
                INNER JOIN `" . self::PRODUCT_STOCK_TEMP_TABLE . "` ps ON p.product_id = ps.product_id
                SET p.quantityws = ps.quantity"
            );

            $this->syncWarehouseStockTable(false);

            $this->db->query('COMMIT');
            $transaction_started = false;

            return 1;
        } catch (\Throwable $e) {
            if ($transaction_started) {
                try {
                    $this->db->query('ROLLBACK');
                } catch (\Throwable $rollback_error) {
                    Log::store($rollback_error->getMessage(), $this->log_name . '-ROLLBACK-FAILED');
                }
            }

            Log::store($e->getMessage(), $this->log_name . '-WAREHOUSE-UPDATE-FAILED');

            throw $e;
        } finally {
            $this->dropProductStockTempTable();
        }

    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setDobavljaci($dobavljaci)
    {
        $json = json_decode($dobavljaci);

        if (!$json || !isset($json->result[0]->artikli_dobavljaci) || !is_array($json->result[0]->artikli_dobavljaci)) {
            $this->dobavljaci_valid = false;
            $this->dobavljaci = collect();

            Log::store($dobavljaci, $this->log_name . '-DOBAVLJACI-INVALID');
            return $this;
        }

        $this->dobavljaci = collect($json->result[0]->artikli_dobavljaci);

        if ($this->dobavljaci->isEmpty()) {
            $this->dobavljaci_valid = false;
            Log::store($dobavljaci, $this->log_name . '-DOBAVLJACI-EMPTY');
        }

        return $this;
    }



    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setSkladista($skladista)
    {
        $json = json_decode($skladista);

        // JSON nije validan ili nema očekivanu strukturu
        if (!$json || !isset($json->result[0]->stanje) || !is_array($json->result[0]->stanje)) {
            $this->skladista_valid = false;
            $this->skladista = collect();

            Log::store($skladista, $this->log_name . '-SKLADISTA-INVALID');
            return $this;
        }

        $this->skladista = collect($json->result[0]->stanje);

        // Ako očekuješ da batch feed nikad ne smije biti prazan:
        if ($this->skladista->isEmpty()) {
            $this->skladista_valid = false;
            Log::store($skladista, $this->log_name . '-SKLADISTA-EMPTY');
        }

        return $this;
    }



    private function prepareProductStockTempTable(bool $include_supplier): void
    {
        $warehouse_quantities = array();
        $supplier_quantities = array();

        foreach ($this->skladista_stock as $item) {
            $key = $this->normalizeIdentifier($item['artikl_uid']);
            $warehouse_quantities[$key] = $this->normalizeQuantity(
                ($warehouse_quantities[$key] ?? 0) + $item['stanje_kol']
            );
        }

        if ($include_supplier) {
            foreach ($this->dobavljaci_stock as $item) {
                $key = $this->normalizeIdentifier($item['artikl']);
                $supplier_quantities[$key] = $this->normalizeQuantity(
                    ($supplier_quantities[$key] ?? 0) + $item['stanje_kol']
                );
            }
        }

        $products = $this->db->query(
            "SELECT product_id, model, luceed_uid
            FROM `" . DB_PREFIX . "product`
            WHERE luceed_uid IS NOT NULL
              AND TRIM(luceed_uid) <> ''"
        );

        if ( ! isset($products->num_rows) || (int)$products->num_rows === 0) {
            throw new \RuntimeException('Stock sync found no Luceed-managed products.');
        }

        $rows = array();
        $matched_warehouse_uids = array();
        $matched_supplier_models = array();

        foreach ($products->rows as $product) {
            $product_id = (int)$product['product_id'];
            $uid = $this->normalizeIdentifier($product['luceed_uid']);
            $model = $this->normalizeIdentifier($product['model']);
            $warehouse_quantity = 0;
            $supplier_quantity = 0;

            if (array_key_exists($uid, $warehouse_quantities)) {
                $warehouse_quantity = $warehouse_quantities[$uid];
                $matched_warehouse_uids[$uid] = true;
            }

            if ($include_supplier && array_key_exists($model, $supplier_quantities)) {
                $supplier_quantity = $supplier_quantities[$model];
                $matched_supplier_models[$model] = true;
            }

            $rows[] = '(' . $product_id . ', ' . $this->normalizeQuantity($warehouse_quantity + $supplier_quantity) . ')';
        }

        $this->validateStagedMatchRatio(
            'warehouse',
            count($matched_warehouse_uids),
            count($warehouse_quantities)
        );

        if ($include_supplier) {
            $this->validateStagedMatchRatio(
                'supplier',
                count($matched_supplier_models),
                count($supplier_quantities)
            );
        }

        $this->dropProductStockTempTable();
        $this->db->query(
            "CREATE TEMPORARY TABLE `" . self::PRODUCT_STOCK_TEMP_TABLE . "` (
                `product_id` INT(11) NOT NULL,
                `quantity` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB"
        );

        foreach (array_chunk($rows, 1000) as $chunk) {
            $this->db->query(
                "INSERT INTO `" . self::PRODUCT_STOCK_TEMP_TABLE . "` (`product_id`, `quantity`)
                VALUES " . implode(',', $chunk)
            );
        }

        $staged = $this->db->query("SELECT COUNT(*) AS total FROM `" . self::PRODUCT_STOCK_TEMP_TABLE . "`");
        $staged_count = isset($staged->row['total']) ? (int)$staged->row['total'] : 0;

        if ($staged_count !== (int)$products->num_rows) {
            throw new \RuntimeException('Stock sync staging count does not match managed product count.');
        }

        Log::store([
            'managed_products'             => (int)$products->num_rows,
            'warehouse_feed_products'      => count($warehouse_quantities),
            'warehouse_matched_products'   => count($matched_warehouse_uids),
            'supplier_feed_products'       => count($supplier_quantities),
            'supplier_matched_products'    => count($matched_supplier_models),
            'includes_supplier_quantities' => $include_supplier
        ], $this->log_name . '-STAGED');
    }


    private function validateStagedMatchRatio(string $feed, int $matched, int $total): void
    {
        $ratio = $total > 0 ? $matched / $total : 0;

        if ($ratio >= $this->minimum_staged_match_ratio) {
            return;
        }

        Log::store([
            'feed'          => $feed,
            'matched'       => $matched,
            'total'         => $total,
            'ratio'         => $ratio,
            'minimum_ratio' => $this->minimum_staged_match_ratio
        ], $this->log_name . '-STAGED-COVERAGE-TOO-SMALL');

        throw new \RuntimeException('Stock sync ' . $feed . ' feed has insufficient product coverage.');
    }


    private function dropProductStockTempTable(): void
    {
        try {
            $this->db->query("DROP TEMPORARY TABLE IF EXISTS `" . self::PRODUCT_STOCK_TEMP_TABLE . "`");
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), $this->log_name . '-TEMP-TABLE-CLEANUP');
        }
    }


    private function normalizeQuantity($quantity): int
    {
        $number = (float)$quantity;

        if ( ! is_finite($number)) {
            throw new \RuntimeException('Stock feed contains a non-finite quantity.');
        }

        $bounded = min(max($number, 0), (float)self::MAX_PRODUCT_QUANTITY);

        return (int)floor($bounded);
    }


    private function normalizeIdentifier($identifier): string
    {
        return strtolower(trim((string)$identifier));
    }


    private function getWarehouseCodesByUid(): array
    {
        $codes = array();

        try {
            $warehouses = (new LOC_Warehouse())->getList()->all();

            foreach ($warehouses as $warehouse) {
                $warehouse_uid = $this->normalizeIdentifier($warehouse['skladiste_uid'] ?? '');
                $warehouse_code = trim((string)($warehouse['skladiste'] ?? ''));

                if ($warehouse_uid !== '' && $warehouse_code !== '') {
                    $codes[$warehouse_uid] = $warehouse_code;
                }
            }
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), $this->log_name . '-WAREHOUSE-CODES');
        }

        return $codes;
    }


    private function ensureWarehouseStockTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_warehouse_stock` (
                `product_id` INT(11) NOT NULL,
                `luceed_uid` VARCHAR(255) NOT NULL,
                `warehouse_code` VARCHAR(32) NOT NULL,
                `warehouse_uid` VARCHAR(64) NOT NULL,
                `quantity` INT(11) NOT NULL DEFAULT 0,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`warehouse_code`, `product_id`),
                KEY `idx_product_quantity` (`product_id`, `quantity`),
                KEY `idx_luceed_uid` (`luceed_uid`),
                KEY `idx_warehouse_quantity_product` (`warehouse_code`, `quantity`, `product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }


    private function assertTransactionalTable(string $table): void
    {
        $result = $this->db->query(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '" . $this->db->escape($table) . "'
            LIMIT 1"
        );

        $engine = isset($result->row['ENGINE']) ? strtoupper((string)$result->row['ENGINE']) : '';

        if ($engine !== 'INNODB') {
            throw new \RuntimeException('Stock sync requires InnoDB table: ' . $table);
        }
    }


    private function getProductIdsByLuceedUids(array $luceed_uids): array
    {
        $product_ids = array();

        foreach (array_chunk(array_values(array_unique($luceed_uids)), 500) as $uid_chunk) {
            $escaped = array();

            foreach ($uid_chunk as $uid) {
                $uid = $this->normalizeIdentifier($uid);

                if ($uid !== '') {
                    $escaped[] = "'" . $this->db->escape($uid) . "'";
                }
            }

            if ( ! $escaped) {
                continue;
            }

            $query = $this->db->query("SELECT product_id, luceed_uid FROM `" . DB_PREFIX . "product` WHERE luceed_uid IN (" . implode(',', $escaped) . ")");

            foreach ($query->rows as $row) {
                $product_ids[$this->normalizeIdentifier($row['luceed_uid'])] = (int)$row['product_id'];
            }
        }

        return $product_ids;
    }


    private function syncWarehouseStockTable(bool $ensure_table = true): int
    {
        if ( ! $this->skladista_valid) {
            return 0;
        }

        if ($ensure_table) {
            $this->ensureWarehouseStockTable();
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_warehouse_stock`");

        if ( ! $this->warehouse_stock_sorted || ! $this->warehouse_stock || $this->warehouse_stock->isEmpty()) {
            return 1;
        }

        $product_ids = $this->getProductIdsByLuceedUids($this->warehouse_stock->pluck('artikl_uid')->unique()->all());
        $date_modified = date('Y-m-d H:i:s');
        $rows_to_insert = array();

        foreach ($this->warehouse_stock as $item) {
            if (empty($product_ids[$item['artikl_uid']])) {
                continue;
            }

            $rows_to_insert[] =
                '(' .
                (int)$product_ids[$item['artikl_uid']] . ', ' .
                "'" . $this->db->escape($item['artikl_uid']) . "', " .
                "'" . $this->db->escape($item['warehouse_code']) . "', " .
                "'" . $this->db->escape($item['warehouse_uid']) . "', " .
                (int)$item['quantity'] . ', ' .
                "'" . $date_modified . "'" .
                ')';
        }

        foreach (array_chunk($rows_to_insert, 1000) as $chunk) {
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "product_warehouse_stock`
                (`product_id`, `luceed_uid`, `warehouse_code`, `warehouse_uid`, `quantity`, `date_modified`)
                VALUES " . implode(',', $chunk)
            );
        }

        return 1;
    }

}
