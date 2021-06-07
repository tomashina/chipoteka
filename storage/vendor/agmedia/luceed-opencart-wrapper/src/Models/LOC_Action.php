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
        return collect($this->actions);
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
     * @throws \Exception
     */
    public function collectActive()
    {
        $actions = $this->getActions()->where('status', '6');

        foreach ($actions as $key => $action) {
            if (empty($action->poslovne_jedinice)) {
                if (Carbon::createFromFormat('d.m.Y', $action->start_date) < Carbon::now() && Carbon::createFromFormat('d.m.Y', $action->end_date) > Carbon::now()->addDay()) {
                    array_push($this->actions_to_add, $action);
                }
            }

            if ( ! empty($action->poslovne_jedinice)) {
                if (Carbon::createFromFormat('d.m.Y', $action->start_date) < Carbon::now() && Carbon::createFromFormat('d.m.Y', $action->end_date) > Carbon::now()->addDay()) {
                    $enter = false;

                    foreach ($action->poslovne_jedinice as $item) {
                        if ($item->pj_uid == '14916-1') {
                            $enter = true;
                        }
                    }

                    if ($enter) {
                        array_push($this->actions_to_add, $action);
                    }
                }
            }
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function sort()
    {
        $this->insert_query = '';
        $this->count        = 0;

        foreach ($this->getActionsToAdd() as $action) {
            foreach ($action->stavke as $item) {
                $product = Product::where('sku', $item->artikl_uid)->first();

                if ($product) {
                    if ($item->mpc_rabat) {
                        $price = $product->price - ($product->price * ($item->mpc_rabat / 100));
                    } else {
                        $price = $item->mpc;
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

        return $prods->result[0]->akcije;
    }


    /**
     * @throws \Exception
     */
    private function deleteActionsDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_special`");
    }
}