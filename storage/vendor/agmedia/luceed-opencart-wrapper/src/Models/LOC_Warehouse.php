<?php

namespace Agmedia\LuceedOpencartWrapper\Models;
use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Warehouse
{
    private const STORE_TABLE = 'luceed_store';


    /**
     * @var Database
     */
    private $db;


    /**
     * @var array
     */
    private $list = [];

    /**
     * Varijabla da bi odvojili default skladišta od
     * zbrajanja default skldišta na kojima se dopušta kupnja.
     *
     * @var array
     */
    private $strict_default_houses = ['101', '001'];

    /**
     * @var array
     */
    private $warehouses;

    /**
     * @var bool|null
     */
    private $store_table_installed;


    /**
     * LOC_Category constructor.
     *
     * @param null $warehouses
     */
    public function __construct($warehouses = null)
    {
        if ($warehouses) {
            $this->list = $this->setWarehouses($warehouses);
        } else {
            $this->list = $this->load();
        }
    }


    /**
     * @return Collection
     */
    public function getList(): Collection
    {
        return collect($this->list)->where('skladiste', '!=', '')
                                   ->where('naziv', '!=', '');
    }


    /**
     * @return Collection
     */
    public function getWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', $this->getCodesByFlag('is_included', agconf('import.warehouse.included')));
    }


    /**
     * @return Collection
     */
    public function getDefaultWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', $this->getCodesByFlag('is_default', agconf('import.warehouse.default')));
    }


    /**
     * @return Collection
     */
    public function getAvailabilityViewWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', $this->getCodesByFlag('is_availability', agconf('import.warehouse.availability_view')));
    }


    /**
     * Resolve a warehouse group from the editable DB table, with env fallback
     * while the migration has not been installed.
     *
     * @param string $flag
     * @param mixed  $fallback
     */
    public function getCodesByFlag(string $flag, $fallback = []): array
    {
        $allowed_flags = ['is_included', 'is_default', 'is_availability', 'is_store', 'is_pickup'];

        if ($this->storeTableExists() && in_array($flag, $allowed_flags, true)) {
            $db = new Database(DB_DATABASE);
            $query = $db->query(
                "SELECT code FROM `" . DB_PREFIX . self::STORE_TABLE . "`"
                . " WHERE status = '1' AND `" . $flag . "` = '1' ORDER BY sort_order ASC, code ASC"
            );

            return array_values(array_map(static function ($row) {
                return (string) $row['code'];
            }, $query->rows));
        }

        return is_array($fallback) ? array_values($fallback) : [];
    }


    /**
     * @param $product
     *
     * @return Collection
     */
    public function getAvailabilityForProduct(string $product): Collection
    {
        $response = collect();
        $houses = $this->getAvailabilityViewWarehouses();
        $units = $this->getUnitsQuery($houses);
        $houses_default = $this->getDefaultWarehouses();
        $units_default = $this->getUnitsQuery($houses_default);
        $houses_stores = $this->getList()->whereIn(
            'skladiste',
            $this->getCodesByFlag('is_store', agconf('import.warehouse.stores'))
        );
        $units_stores = $this->getUnitsQuery($houses_stores);

        $availables = collect($this->setAvailables(
            LuceedProduct::stock($units, $product)
        ));

        $defaults = collect($this->setAvailables(
            LuceedProduct::stock($units_default, $product)
        ));

        $stores = collect($this->setAvailables(
            LuceedProduct::stock($units_stores, $product)
        ));

        $suplier = collect($this->setSuplierStock(
            LuceedProduct::getSuplierStock($product)
        ))->where('main', 'D')->first();

        $dobavljac_stanje = $suplier->dobavljac_stanje;

        // AVAILABILITY VIEW
        foreach ($houses as $house) {
            $has = $availables->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty = $has->raspolozivo_kol;

                if ($qty < 0) {
                    $qty = 0;
                }

                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => $qty
                ]);

            } else {
                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => 0
                ]);
            }
        }

        $qty_default = 0;
        // DEFAULT WAREHOUSE COUNT
        foreach ($houses_default as $house) {
            if (in_array($house['skladiste'], $this->strict_default_houses)) {
                $has = $defaults->where('skladiste_uid', $house['skladiste_uid'])->first();

                if ($has) {
                    $qty_default += max($has->raspolozivo_kol, 0);
                }
            }
        }

        if ($qty_default < 0) {
            $qty_default = 0;
        }

        $qty_stores = 0;
        // STORES WAREHOUSE COUNT
        foreach ($houses_stores as $house) {
            $has = $stores->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has && $has->raspolozivo_kol >= 0) {
                $qty_stores += $has->raspolozivo_kol;
            }
        }

        if ($qty_stores < 0) {
            $qty_stores = 0;
        }

        // provjeri trgovine bez dubrovnika
        //$qty_stores_without_dubrovnik = 0;
        // STORES WAREHOUSE COUNT
        /*foreach ($houses_stores as $house) {
            if ($house['skladiste'] != '012') {
                $has = $stores->where('skladiste_uid', $house['skladiste_uid'])->first();

                if ($has) {
                    $qty_stores_without_dubrovnik += $has->raspolozivo_kol;
                }
            }
        }

        if ($qty_stores_without_dubrovnik < 0) {
            $qty_stores_without_dubrovnik = 0;
        }*/

        if ( ! $dobavljac_stanje && $qty_stores) {
            $dobavljac_stanje = $qty_stores;
        }

        $title = '';
        $btn = '';
        $button = '';
        $date = '';

        if ($qty_default) {
            $title = 'success';
            $date = Carbon::now()->addWeekdays(1);
            $btn = 'DOSTUPNO ODMAH';
            $date = ($date->diff(Carbon::now())->days < 1) ? 'Šaljemo sutra' : 'Šaljemo do ' . $date->format('d.m.Y');;
        }

        if ( ! $qty_default && ! $dobavljac_stanje && $qty_stores) {
            $title = 'secondary';
            $btn = 'NEDOSTUPNO NA WEBU';
            $button = 'Nedostupno online';
            $date = 0;
        }

        if ( ! $qty_default && ($dobavljac_stanje/* || $qty_stores_without_dubrovnik*/)) {
            $title = 'warning';
            $btn = 'DOSTUPNO NA IZDVOJENOM SKLADIŠTU';
            $button = 'Stavi u košaricu';
            $date = 'Šaljemo do ' . Carbon::now()->addWeekdays(5)->format('d.m.Y');
        }

        if ( ! $qty_default && ! $dobavljac_stanje && ! $qty_stores) {
            $title = 'secondary';
            $btn = 'PROIZVOD NEDOSTUPAN';
            $button = 'Nedostupno';
            $date = 0;
        }

        $response->push([
            'title' => 'Web',
            'address' => '',
            'qty'   => $qty_default
        ]);

        $response->push([
            'title' => 'Dobavljač',
            'address' => '',
            'qty'   => $dobavljac_stanje
        ]);



        $response->push([
            'title' => 'Btn',
            'btn' => $title,
            'button' => $button,
            'address' => $btn,
            'qty'   => $date
        ]);

        $this->db = new Database(DB_DATABASE);

       // $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = '".$qty_default."' + '". $dobavljac_stanje."' WHERE sku = '".$product."'  ");

        return $response;
    }


    /**
     * @return int
     */
    public function import(Collection $list = null)
    {
        $imported = 0;

        if ($list) {
            $imported = file_put_contents(agconf('import.warehouse.json'), $list->toJson());
        }

        return $imported;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        if ($this->storeTableExists()) {
            $db = new Database(DB_DATABASE);
            $query = $db->query(
                "SELECT * FROM `" . DB_PREFIX . self::STORE_TABLE . "`"
                . " WHERE status = '1' ORDER BY sort_order ASC, code ASC"
            );
            $stores = [];

            foreach ($query->rows as $row) {
                $stores[] = [
                    'skladiste_uid' => $row['warehouse_uid'],
                    'skladiste' => $row['code'],
                    'naziv' => $row['name'],
                    'pj_uid' => $row['pj_uid'],
                    'pj' => $row['pj_code'],
                    'pj_naziv' => $row['pj_name'],
                    'adresa' => $row['address'],
                    'postanski_broj' => $row['postal_code'],
                    'mjesto' => $row['city'],
                    'telefax' => $row['contact'],
                    'e_mail' => $row['contact'],
                ];
            }

            return collect($stores);
        }

        $file = json_decode(file_get_contents(agconf('import.warehouse.json')),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
    }


    private function storeTableExists(): bool
    {
        if ($this->store_table_installed !== null) {
            return $this->store_table_installed;
        }

        try {
            $db = new Database(DB_DATABASE);
            $table = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], DB_PREFIX . self::STORE_TABLE);
            $query = $db->query("SHOW TABLES LIKE '" . $db->escape($table) . "'");

            $this->store_table_installed = (bool) $query->num_rows;
        } catch (\Exception $e) {
            $this->store_table_installed = false;
        }

        return $this->store_table_installed;
    }

    /**
     * @param string $sku
     *
     * @return bool
     */
    public function itemHasStock(string $sku): bool
    {
        $warehouses = $this->getAvailabilityForProduct($sku);

        foreach ($warehouses as $warehouse) {
            if ($warehouse['title'] == 'Web' && $warehouse['qty'] > 0) {
                return true;
            }
            if ($warehouse['title'] == 'Dobavljač' && $warehouse['qty'] > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $units
     *
     * @return string
     */
    private function getUnitsQuery($units)
    {
        $string = '[';

        foreach ($units as $unit) {
            $string .= $unit['skladiste'] . ',';
        }

        $string = substr($string, 0, -1);

        $string .= ']';

        return $string;
    }


    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setWarehouses($warehouses): array
    {
        $json = json_decode($warehouses);

        if (isset($json->result[0]->skladista)) {
            return $json->result[0]->skladista;
        }

        return [];
    }

    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setAvailables($items): array
    {
        $json = json_decode($items);

        if (isset($json->result[0]->stanje)) {
            return $json->result[0]->stanje;
        }

        return [];
    }


    /**
     * @param $stock
     *
     * @return mixed
     */
    private function setSuplierStock($stock)
    {
        $json = json_decode($stock);

        if (isset($json->result[0]->artikli_dobavljaci)) {
            return $json->result[0]->artikli_dobavljaci;
        }

        return [];
    }
}
