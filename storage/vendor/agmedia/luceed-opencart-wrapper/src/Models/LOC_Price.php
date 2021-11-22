<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Price
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var array
     */
    private $prices;

    /**
     * @var array
     */
    private $prices_to_update = [];

    /**
     * @var int
     */
    private $group_id;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($prices = null, $group_id = 0)
    {
        if ($prices) {
            $this->prices = $this->setPrices($prices);
        }

        $this->group_id = $group_id;
        $this->db       = new Database(DB_DATABASE);
    }


    /**
     * @return \string[][]
     */
    public function getGroups(): array
    {
        return [
            [
                'id' => '3',
                'title' => 'N1',
                'url' => '14956-N2'
            ]
        ];
    }


    /**
     * @return Collection
     */
    public function getPrices(): Collection
    {
        return collect($this->prices);
    }


    /**
     * @return Collection
     */
    public function getPricesToUpdate(): Collection
    {
        return collect($this->prices_to_update);
    }


    /**
     * @return $this
     */
    public function collectB2B()
    {
        $this->prices_to_update = $this->getPricesToUpdate();
        $prices                 = $this->getPrices()->first();

        $categories    = collect();
        $manufacturers = collect();
        $cat_man = collect();

        foreach ($prices->rabati as $item) {
            if ($item->grupa_artikla_uid && is_null($item->robna_marka_uid) && ! is_null($item->rabat)) {
                if ($categories->has($item->grupa_artikla_uid)) {
                    if ($item->grupa_artikla_uid > $categories[$item->grupa_artikla_uid]) {
                        $categories->put($item->grupa_artikla_uid, $item->rabat);
                    }
                } else {
                    $categories->put($item->grupa_artikla_uid, $item->rabat);
                }
            }

            if ($item->robna_marka_uid && is_null($item->grupa_artikla_uid) && ! is_null($item->rabat)) {
                if ($manufacturers->has($item->robna_marka_uid)) {
                    if ($item->robna_marka_uid > $manufacturers[$item->robna_marka_uid]) {
                        $manufacturers->put($item->robna_marka_uid, $item->rabat);
                    }
                } else {
                    $manufacturers->put($item->robna_marka_uid, $item->rabat);
                }
            }

            if ($item->grupa_artikla_uid && $item->robna_marka_uid && ! is_null($item->rabat)) {
                $cat_man->put($item->grupa_artikla_uid, [
                    'manufacturer' => $item->robna_marka_uid,
                    'discount' => $item->rabat
                ]);
            }
        }

        foreach ($categories as $sifra => $discount) {
            $category = Category::where('lc_uid', $sifra)->first();

            if ($category) {
                $ids      = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
                $products = Product::whereIn('product_id', $ids)->get();

                foreach ($products as $product) {
                    $this->addForUpdate($product, $discount);
                }
            }
        }

        foreach ($manufacturers as $sifra => $discount) {
            $manufacturer = Manufacturer::where('lc_uid', $sifra)->with('products')->first();

            if ($manufacturer) {
                foreach ($manufacturer->products as $product) {
                    $this->addForUpdate($product, $discount);
                }
            }
        }

        foreach ($cat_man as $sifra => $item) {
            $category = Category::where('lc_uid', $sifra)->first();

            if ($category) {
                $manufacturer = Manufacturer::where('lc_uid', $item['manufacturer'])->first();

                if ($manufacturer) {
                    $ids      = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
                    $products = Product::whereIn('product_id', $ids)->where('manufacturer_id', $manufacturer->manufacturer_id)->get();

                    foreach ($products as $product) {
                        $this->addForUpdate($product, $item['discount']);
                    }
                }
            }
        }

        foreach ($prices->cijene as $item) {
            if ($item->cijena) {
                $product = Product::where('luceed_uid', $item->artikl_uid)->first();

                if ($product) {
                    $this->addForUpdate($product, 0, $item->cijena);
                }
            }
        }

        return $this;
    }


    public function collectAndStore(Collection $products, string $type = 'mpc')
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");

        $products = $products->where('artikl', '!=', '')
                             ->where('naziv', '!=', '')
                             ->where('enabled', '!=', 'N')
                             ->where('webshop', '!=', 'N');

        Log::store('collectAndStore(count) ::: ' . $products->count());

        if ($products->count()) {
            $temp_product = '';

            foreach ($products->all() as $product) {
                $temp_product .= '("' . $product->artikl . '", 0, ' . ($type == 'mpc' ? $this->resolvePrice($product->mpc) : $this->resolvePrice($product->vpc)) . '),';
            }

            Log::store('collectAndStore(sql) ::: ' . $temp_product);

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($temp_product, 0, -1) . ";");

            if ($type == 'mpc') {
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price_2 = pt.price");
            } else {
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.vpc = pt.price");
            }

            //$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");

            return 1;
        }

        return 0;
    }


    /**
     * @param string $type
     *
     * @return int
     * @throws \Exception
     */
    public function update(string $type = 'prices')
    {
        if ($type == 'b2b') {
            if ( ! empty($this->prices_to_update) && $this->prices_to_update->count()) {
                $temp_values = '';

                foreach ($this->prices_to_update as $price) {
                    $temp_values .= '(' . $price['id'] . ', ' . $this->group_id . ', 1, 0, ' . $this->resolvePrice($price['price']) . ', "0000-00-00", "0000-00-00"),';
                }

                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount (product_id, customer_group_id, quantity, priority, price, date_start, date_end) VALUES " . substr($temp_values, 0, -1) . ";");

                return $this->prices_to_update->count();
            }
        }

        return 0;
    }


    /**
     * @throws \Exception
     */
    public function deleteProductDiscountDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_discount`");
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setPrices($prices): array
    {
        $prods = json_decode($prices);

        return $prods->result[0]->partneri;
    }


    /**
     * @param      $product
     * @param      $discount
     * @param null $price
     *
     * @return mixed
     */
    private function addForUpdate($product, $discount, $price = null)
    {
        $price = $price ?: $this->calculateDiscountPrice($product->vpc, $discount);

        if ($this->prices_to_update->has($product->luceed_uid)) {
            $old_price = $this->prices_to_update->filter(function($item) use ($product) {
                return $item->id == $product->product_id;
            })->first();

            Log::store($product->luceed_uid . ' / ' . $product->product_id . ' ::: stara: ' . $old_price['price'] . ' ::: nova: ' . $price);

            if ($old_price['price'] > $price) {
                return $this->prices_to_update->put($product->luceed_uid, [
                    'id'    => $product->product_id,
                    'price' => $price,
                ]);
            }

            return false;
        }

        return $this->prices_to_update->put($product->luceed_uid, [
            'id'    => $product->product_id,
            'price' => $price,
        ]);
    }


    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    private function calculateDiscountPrice(float $price, int $discount)
    {
        if ( ! $discount) {
            return $price;
        }

        return $price - ($price * ($discount / 100));
    }


    /**
     * @param float $price
     *
     * @return float
     */
    private function resolvePrice($price)
    {
        return number_format($price, 2, '.', '');
    }
}