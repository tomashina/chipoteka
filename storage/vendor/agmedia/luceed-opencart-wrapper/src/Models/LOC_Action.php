<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Action
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var array
     */
    private $actions_to_add = [];

    /**
     * @var array
     */
    private $prices_to_update = [];

    /**
     * @var array
     */
    private $count;

    /**
     * @var array
     */
    private $insert_query;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($actions)
    {
        $this->actions = $this->setActions($actions);
        $this->db      = new Database(DB_DATABASE);
    }


    /**
     * @return Collection
     */
    public function getActions(): Collection
    {
        return collect($this->actions)->where('partner', '==', null);
    }


    /**
     * @return Collection
     */
    public function getActionsToAdd(): Collection
    {
        return collect($this->actions_to_add);
    }


    /**
     * @return $this
     */
    public function collectWebPrices()
    {
        $this->prices_to_update = collect();
        $action = $this->getActions()
                       ->where('naziv', '==', 'web_cijene')
                       ->first();

        $categories = collect();
        $manufacturers = collect();

        foreach ($action->stavke as $item) {
            if ($item->grupa_artikla && $item->mpc_rabat) {

                if ($categories->has($item->grupa_artikla)) {
                    if ($item->grupa_artikla > $categories[$item->grupa_artikla]) {
                        $categories->put($item->grupa_artikla, $item->mpc_rabat);
                    }
                } else {
                    $categories->put($item->grupa_artikla, $item->mpc_rabat);
                }
            }

            if ($item->robna_marka && $item->mpc_rabat) {
                if ($manufacturers->has($item->robna_marka)) {
                    if ($item->robna_marka > $manufacturers[$item->robna_marka]) {
                        $manufacturers->put($item->robna_marka, $item->mpc_rabat);
                    }
                } else {
                    $manufacturers->put($item->robna_marka, $item->mpc_rabat);
                }
            }
        }

        foreach ($categories as $sifra => $discount) {
            $category = Category::where('luceed_uid', $sifra)->with('products')->first();

            if ($category) {
                $ids = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');

                $products = Product::whereIn('product_id', $ids)->get();

                foreach ($products as $product) {
                    $this->prices_to_update->put($product->model, $this->calculateDiscountPrice($product->price_2, $discount));
                }
            }
        }

        foreach ($manufacturers as $sifra => $discount) {
            $manufacturer = Manufacturer::where('luceed_uid', $sifra)->with('products')->first();

            if ($manufacturer) {
                foreach ($manufacturer->products as $product) {
                    $this->prices_to_update->put($product->model, $this->calculateDiscountPrice($product->price_2, $discount));
                }
            }
        }

        foreach ($action->stavke as $item) {
            if ($item->mpc) {
                $this->prices_to_update->put($item->artikl, $item->mpc);
                //$this->prices_to_update->push($item);
            }
        }

        return $this;
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function collectActive()
    {
        //$articles = collect();
        $actions = $this->getActions()
                        ->where('naziv', '!=', 'web_cijene');

        foreach ($actions as $key => $action) {
            if ( ! empty($action->stavke)) {
                if (( ! $action->start_date || Carbon::createFromFormat('d.m.Y', $action->start_date) < Carbon::now()) &&
                    ( ! $action->end_date || Carbon::createFromFormat('d.m.Y', $action->end_date) > Carbon::now()->addDay())
                ) {
                    array_push($this->actions_to_add, $action);
                }
            }
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function sortActions()
    {
        $specials = collect();
        $this->insert_query = '';
        $this->count        = 0;

        foreach ($this->getActionsToAdd() as $action) {
            foreach ($action->stavke as $item) {
                $specials->push($item);
            }
        }

        $temps = $specials->groupBy('artikl_uid')->all();

        foreach ($temps as $item) {
            $product = Product::where('luceed_uid', $item->first()->artikl_uid)->first();

            if ($product) {
                if ($item->first()->mpc_rabat) {
                    $price = $product->price - ($product->price * ($item->first()->mpc_rabat / 100));
                } else {
                    $price = $item->first()->mpc;
                }

                if ($price) {
                    $start = Carbon::createFromFormat('d.m.Y', $action->start_date)->format('Y-m-d');
                    $end   = Carbon::createFromFormat('d.m.Y', $action->end_date)->format('Y-m-d');

                    $end = date('Y-m-d', strtotime("+1 day", strtotime($end)));

                    $this->insert_query .= '(' . $product->product_id . ', 1, 0, ' . $price . ', "' . $start . '", "' . $end . '"),';

                    $this->count++;
                }
            }
        }

        return $this;
    }




    /**
     * @return array|false
     * @throws \Exception
     */
    public function import()
    {
        $this->deleteActionsDB();

        try {
            $inserted = $this->db->query("INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . substr($this->insert_query, 0, -1) . ";");
        }
        catch (\Exception $exception) {
            Log::store($exception->getMessage(), 'import_actions_query');
        }


        if ($inserted) {
            return $this->count;
        }

        return false;
    }


    /**
     * @param string $type
     *
     * @return int
     * @throws \Exception
     */
    public function update(string $type = 'prices')
    {
        if ($type == 'prices') {
            if ( ! empty($this->prices_to_update) && $this->prices_to_update->count()) {
                $this->deleteProductTempDB();

                $temp_product = '';

                foreach ($this->prices_to_update as $sifra => $price) {
                    $temp_product .= '("' . $sifra . '", 0, ' . $price . '),';
                }

                $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($temp_product, 0, -1) . ";");
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price");

                return $this->prices_to_update->count();
            }
        }

        return 0;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setActions($actions): array
    {
        $prods = json_decode($actions);

        return $prods->result[0]->prodajneakcije;
    }


    /**
     * @throws \Exception
     */
    private function deleteActionsDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_special`");
    }


    /**
     * @throws \Exception
     */
    private function deleteProductTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");
    }


    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    private function calculateDiscountPrice(float $price, int $discount)
    {
        return $price - ($price * ($discount / 100));
    }
}