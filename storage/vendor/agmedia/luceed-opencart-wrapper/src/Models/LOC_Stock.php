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

    private $status;

    private $log_name;

    private $skladista_valid = true;
    private $dobavljaci_valid = true;

    public function isValid(): bool
    {
        return $this->skladista_valid && $this->dobavljaci_valid;
    }


    /**
     * LOC_Product constructor.
     */
    public function __construct()
    {
        $this->db = new Database(DB_DATABASE);
        $this->status = agconf('import.default_stock_empty');
        $this->log_name = 'stock' . date('-d-H');
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

            foreach ($this->skladista->groupBy('artikl_uid')->all() as $key => $item) {
                //$qty = $item->sum('raspolozivo_kol');

                $qty = 0;
                foreach ($item as $stock) {
                    $qty = $qty + max($stock->raspolozivo_kol, 0);
                }

                if ($qty) {
                    $this->status = agconf('import.default_stock_full');
                } else {
                    $this->status = agconf('import.default_stock_empty');
                }

                $this->skladista_stock->push([
                    'artikl_uid' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $this->status
                ]);
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

            foreach ($this->skladista as $stock) {
                $warehouse_uid = trim((string)($stock->skladiste_uid ?? ''));
                $artikl_uid = trim((string)($stock->artikl_uid ?? ''));
                $warehouse_code = $warehouse_codes_by_uid[$warehouse_uid] ?? '';

                if ($warehouse_uid === '' || $warehouse_code === '' || $artikl_uid === '') {
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

                $warehouse_stock[$group_key]['quantity'] += max((int)($stock->raspolozivo_kol ?? 0), 0);
            }

            foreach ($warehouse_stock as $item) {
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

            foreach ($this->dobavljaci->where('main', 'D')->groupBy('sifra_artikla')->all() as $key => $item) {
                //$qty = $item->sum('dobavljac_stanje');

                $qty = 0;
                foreach ($item as $stock) {
                    $qty = $qty + max($stock->dobavljac_stanje, 0);
                }

                if ($this->status == agconf('import.default_stock_empty') && $qty) {
                    $this->status = agconf('import.default_stock_full');
                }

                $this->dobavljaci_stock->push([
                    'artikl' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $this->status
                ]);
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
        if (!$this->skladista_valid) {
            return $this;
        }

        // ako nema ničega za upis -> neće se ni update dogoditi
        if ($this->skladista_sorted && $this->skladista_stock && $this->skladista_stock->isEmpty()) {
            $this->skladista_query = '';
            return $this;
        }

        if ($this->skladista_sorted && $this->skladista_stock) {
            foreach ($this->skladista_stock as $item) {
                $this->skladista_query .= '("' . $item['artikl_uid'] . '", ' . $item['stanje_kol'] . ', ' . $item['stock_status'] . '),';
            }
        }

        if ($this->dobavljaci_sorted && $this->dobavljaci_stock) {
            foreach ($this->dobavljaci_stock as $item) {
                $this->dobavljaci_query .= '("' . $item['artikl'] . '", ' . $item['stanje_kol'] . ', ' . $item['stock_status'] . '),';
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
        //   $this->truncateProductsQuantity();
        if (!$this->skladista_valid) {
            Log::store('SKIP UPDATE: invalid skladista feed', $this->log_name . '-SKIP');
            return 0;
        }

        $did_update = 0;

        if ($this->skladista_query != '') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->skladista_query, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.luceed_uid = pt.uid SET p.quantity = pt.quantity, p.stock_status_id = pt.price, p.status = IF(pt.quantity > 0, 1, p.status)");

            $this->deleteProductTempDB();

            if ($this->dobavljaci_query != '') {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->dobavljaci_query, 0, -1) . ";");
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = p.quantity + pt.quantity, p.stock_status_id = pt.price, p.status = IF((p.quantity + pt.quantity) > 0, 1, p.status)");
            }

            $this->deleteProductTempDB();
            //Log::store($this->log_name . '-UPDATED 1', $this->log_name);
            $did_update = 1;
        }

        $warehouse_synced = $this->syncWarehouseStockTable();

        return ($did_update || $warehouse_synced) ? 1 : 0;
    }

    /**
     * @return int
     */
    public function updateWithoutSupliers(): int
    {
        //   $this->truncateProductsQuantity();
        $did_update = 0;

        if ($this->skladista_query != '') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->skladista_query, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.luceed_uid = pt.uid SET p.quantityws = pt.quantity");

            $this->deleteProductTempDB();

            //Log::store($this->log_name . '-UPDATED 1', $this->log_name);
            $did_update = 1;
        }

        $warehouse_synced = $this->syncWarehouseStockTable();

        return ($did_update || $warehouse_synced) ? 1 : 0;
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



    /**
     * @throws \Exception
     */
    private function deleteProductTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");
    }


    private function getWarehouseCodesByUid(): array
    {
        $codes = array();

        try {
            $warehouses = (new LOC_Warehouse())->getList()->all();

            foreach ($warehouses as $warehouse) {
                $warehouse_uid = trim((string)($warehouse['skladiste_uid'] ?? ''));
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


    private function getProductIdsByLuceedUids(array $luceed_uids): array
    {
        $product_ids = array();

        foreach (array_chunk(array_values(array_unique($luceed_uids)), 500) as $uid_chunk) {
            $escaped = array();

            foreach ($uid_chunk as $uid) {
                $uid = trim((string)$uid);

                if ($uid !== '') {
                    $escaped[] = "'" . $this->db->escape($uid) . "'";
                }
            }

            if ( ! $escaped) {
                continue;
            }

            $query = $this->db->query("SELECT product_id, luceed_uid FROM `" . DB_PREFIX . "product` WHERE luceed_uid IN (" . implode(',', $escaped) . ")");

            foreach ($query->rows as $row) {
                $product_ids[$row['luceed_uid']] = (int)$row['product_id'];
            }
        }

        return $product_ids;
    }


    private function syncWarehouseStockTable(): int
    {
        if ( ! $this->skladista_valid) {
            return 0;
        }

        $this->ensureWarehouseStockTable();
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_warehouse_stock`");

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


    /**
     * @throws \Exception
     */
    private function truncateProductsQuantity(): void
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = 0 WHERE 1");
    }

}
