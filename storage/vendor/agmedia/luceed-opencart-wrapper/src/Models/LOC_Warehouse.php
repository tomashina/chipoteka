<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

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

    /**
     * @var array
     */
    private $list = [];

    /**
     * @var array
     */
    private $warehouses;


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
                    ->whereIn('skladiste', agconf('import.warehouse.included'));
    }


    /**
     * @return Collection
     */
    public function getDefaultWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.default'));
    }


    /**
     * @return Collection
     */
    public function getAvailabilityViewWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.availability_view'));
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
        $houses_stores = $this->getList()->whereIn('skladiste', agconf('import.warehouse.stores'));
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
            $has = $defaults->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty_default += $has->raspolozivo_kol;
            }
        }

        $qty_stores = 0;
        // STORES WAREHOUSE COUNT
        foreach ($houses_stores as $house) {
            $has = $defaults->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty_stores += $has->raspolozivo_kol;
            }
        }

        $title = '';
        $btn = '';
        $date = '';

        if ($qty_default) {
            $title = 'success';
            $btn = 'DOSTUPNO ODMAH - ŠALJEMO SUTRA';
            $date = Carbon::now()->addWeekdays(1)->format('d.m.Y');
        }

        if ( ! $qty_default && ! $suplier->dobavljac_stanje && $qty_stores) {
            $title = 'secondary';
            $btn = 'NEDOSTUPNO NA WEBU';
            $date = 0;
        }

        if ( ! $qty_default && $suplier->dobavljac_stanje) {
            $title = 'warning';
            $btn = 'DOSTUPNO ODMAH - ŠALJEMO [+5]';
            $date = Carbon::now()->addWeekdays(5)->format('d.m.Y');
        }

        if ( ! $qty_default && ! $suplier->dobavljac_stanje && $qty_stores) {
            $title = 'secondary';
            $btn = 'PROIZVOD NEDOSTUPAN';
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
            'qty'   => $suplier->dobavljac_stanje
        ]);

        $response->push([
            'title' => 'Btn',
            'btn' => $title,
            'address' => $btn,
            'qty'   => $date
        ]);

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
        $file = json_decode(file_get_contents(agconf('import.warehouse.json')),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
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

        return $json->result[0]->skladista;
    }

    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setAvailables($items): array
    {
        $json = json_decode($items);

        return $json->result[0]->stanje;
    }


    /**
     * @param $stock
     *
     * @return mixed
     */
    private function setSuplierStock($stock)
    {
        $json = json_decode($stock);

        return $json->result[0]->artikli_dobavljaci;
    }
}