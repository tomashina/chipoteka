<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Related
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var array
     */
    private $related;

    /**
     * @var array
     */
    private $active_related;

    /**
     * @var array
     */
    private $count;

    /**
     * @var string
     */
    private $insert_query;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($related)
    {
        $this->related = $this->setRelated($related);
        $this->db      = new Database(DB_DATABASE);
    }


    /**
     * @return Collection
     */
    public function getRelated(): Collection
    {
        return collect($this->related);
    }


    /**
     * @return $this
     */
    public function collectActive()
    {
        $items = $this->getRelated()
                      ->where('enabled', '!=', 'N')
                      ->where('webshop', '!=', 'N')
                      ->all();

        foreach ($items as $item) {
            if ( ! empty($item->dodaci)) {
                $this->active_related[$item->artikl] = $item->dodaci;
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

        foreach ($this->active_related as $key => $items) {
           // $main = Product::where('sku', $key)->first();
            $main = Product::query()->where('sku', '=', $key)->first();
            $count = count($items);

            if ( ! $main) {
                $main = Product::query()->where('model', '=', $key)->first();
            }

            if ($key == '1099900923') {
                Log::store($key, 'related_testing');
                Log::store($main, 'related_testing');
                Log::store($count, 'related_testing');
            }

            if ($main) {
                foreach ($items as $item) {
                    $related = Product::where('sku', $item->dodatak__artikl)->first();

                    if ($key == '1099900923') {
                        Log::store($related->toArray(), 'related_testing');
                        Log::store($main->product_id, 'related_testing');
                    }

                    if ($related && isset($related->product_id) && $related->product_id) {
                        $this->insert_query .= '(' . $main->product_id . ', ' . $related->product_id . '),';
                    }
                }

                if ($count < 5) {
                    $category = ProductCategory::where('product_id', $main->product_id)->orderBy('category_id', 'DESC')->first();

                    if ($category) {
                        $products = ProductCategory::where('category_id', $category->category_id)->where('product_id','!=', $main->product_id)->get()->toArray();

                        if (count($products)) {
                            for ($i = 0; $i < 5 - $count; $i++) {
                                if (isset($products[$i]['product_id']) && $products[$i]['product_id']) {
                                    $this->insert_query .= '(' . $main->product_id . ', ' . $products[$i]['product_id'] . '),';
                                }
                            }
                        }
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
        $inserted = 0;
        $this->deleteRelatedDB();

        try {
            $sql = "INSERT INTO " . DB_PREFIX . "product_related (product_id, related_id) VALUES " . substr($this->insert_query, 0, -1) . ";";

            $inserted = $this->db->query($sql);
        }
        catch (\Exception $exception) {
            Log::store($exception->getMessage(), 'import_related_query');
        }

        if ($inserted) {
            return count($this->active_related);
        }

        return false;
    }


    /**
     * @throws \Exception
     */
    private function deleteRelatedDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_related`");
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $related
     *
     * @return array
     */
    private function setRelated($related): array
    {
        $response = json_decode($related);

        return $response->result[0]->artikli;
    }
}