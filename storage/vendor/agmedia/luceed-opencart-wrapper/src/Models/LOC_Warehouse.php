<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Category\CategoryDescription;
use Agmedia\Models\Category\CategoryPath;
use Agmedia\Models\Category\CategoryToLayout;
use Agmedia\Models\Category\CategoryToStore;
use Agmedia\Models\SeoUrl;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
    public function getAvailabilityForProduct($product): Collection
    {
        $response = collect();
        $houses = $this->getAvailabilityViewWarehouses();
        $units = $this->getUnitsQuery($houses);

        $availables = collect($this->setAvailables(
            LuceedProduct::stock($units, $product)
        ));

        foreach ($houses as $house) {
            $has = $availables->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty = $has->raspolozivo_kol;

                if ($qty < 0) {
                    $qty = 0;
                }

                $response->push([
                    'title' => $house['naziv'],
                    'qty'   => $qty
                ]);

            } else {
                $response->push([
                    'title' => $house['naziv'],
                    'qty'   => 0
                ]);
            }
        }

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
     * @param $warehouses
     *
     * @return array
     */
    private function setWarehouses($warehouses): array
    {
        $cats = json_decode($warehouses);

        return $cats->result[0]->skladista;
    }
}