<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Models\LuceedProduct;
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
class LOC_ProductSingle
{

    /**
     * @var array
     */
    public $product = null;

    /**
     * @var Collection
     */
    public $product_to_update = null;

    /**
     * @var Collection
     */
    public $product_to_insert = null;

    /**
     * @var Collection
     */
    private $luceed_product = null;

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @var array
     */
    private $products_for_revision = [];


    /**
     * LOC_ProductSingle constructor.
     */
    public function __construct()
    {
    }


    /**
     * @return Collection
     */
    public function resolveLuceedProductData(): Collection
    {
        /*return collect(json_decode(
            htmlspecialchars_decode($this->luceed_product->data),
            true
        ));*/
        return collect(unserialize(base64_decode($this->luceed_product->data)));
    }


    /**
     * @return bool
     */
    public function hasForUpdate(): bool
    {
        Log::store('01', 'product_update');

        $uids_list = LuceedProduct::pluck('uid');
        $this->product_to_update = Product::whereIn('luceed_uid', $uids_list)->where('updated', 0)->first();

        if ($this->product_to_update && isset($this->product_to_update['luceed_uid'])) {
            /*$this->luceed_product = LuceedProduct::query()->where('uid', '=', $this->product_to_update['luceed_uid'])
                                                 ->where('hash', '!=', $this->product_to_update['hash'])
                                                 ->first();

            $db = new Database(DB_DATABASE);
            $uid = $this->product_to_update['luceed_uid'];
            $hash = $this->product_to_update['hash'];
            $res = $db->query("SELECT * FROM oc_product_luceed WHERE uid = '" . $uid . "' AND `hash` != '" . $hash . "';");

            Log::store($this->luceed_product, 'product_for_update');
            Log::store('$res :::', 'product_for_update');
            Log::store($res, 'product_for_update');*/


            $this->luceed_product = LuceedProduct::where('uid', $this->product_to_update['luceed_uid'])
                                                 ->first();

            if ($this->product_to_update['sku'] == '1099900638') {
                $arr = [
                    'u' => $this->product_to_update['luceed_uid'],
                    's' => $this->product_to_update['sku'],
                    'h' => $this->product_to_update['hash'],
                    'uu' => $this->luceed_product['uid'],
                    'ss' => $this->luceed_product['sku'],
                    'hh' => $this->luceed_product['hash']
                ];

                Log::store($arr, 'product_for_update_2');
            }

            if ($this->luceed_product['hash'] != $this->product_to_update['hash']) {
                Log::store($this->luceed_product, 'product_for_update');

                $this->product = $this->resolveLuceedProductData();

                Log::store($this->product, 'product_for_update');

                return true;
            }

            /*if ($this->luceed_product) {
                $this->product = $this->resolveLuceedProductData();

                Log::store('02', 'product_update');
                Log::store($this->product, 'product_for_update');

                return true;
            }*/
        }

        Log::store('03', 'product_update');

        return false;
    }


    /**
     * @param \stdClass $product
     */
    public function setForUpdate(array $product)
    {
        $this->hash = sha1(collect($product)->toJson());
        $this->product_to_update = Product::where('luceed_uid', $product['artikl_uid'])->first();

        if ($this->product_to_update) {
            $this->product = collect($product);
        } else {
            $this->product_to_insert = collect($product);
        }

        return $this;
    }


    /**
     * @return bool
     */
    public function hasForInsert(): bool
    {
        $existing = Product::pluck('luceed_uid');
        $diff     = LuceedProduct::whereNotIn('uid', $existing)->first();

        if ($diff) {
            $this->luceed_product = $diff;

            if ($this->luceed_product) {
                $this->product = $this->resolveLuceedProductData();

                return true;
            }
        }

        return false;
    }


    /**
     * @return array
     */
    public function finishInsert(): array
    {
        $this->markChecked('insert');

        return [
            'status'  => 200,
            'message' => 'inserted'
        ];
    }


    /**
     * @return mixed
     */
    public function finishUpdate(): array
    {
        if ($this->product_to_update) {
            $this->markChecked();

            return [
                'status'  => 200,
                'message' => 'updated'
            ];
        }
    }


    /**
     * @return mixed
     */
    public function finishUpdateError(): array
    {
        $this->pushToRevision();
        $this->markChecked();

        return [
            'status'  => 200,
            'message' => 'error'
        ];
    }


    /**
     * @return array
     */
    public function finish(): array
    {
        return [
            'status'  => 200,
            'message' => 'finish'
        ];
    }


    /**
     * @param array $old_product
     *
     * @return array
     */
    public function makeForUpdate(array $old_product): array
    {
        if ( ! $this->product) {
            return false;
        }

        Log::store('31', 'product_update');

        $product                     = $this->make();

        Log::store('32', 'product_update');

        $product['product_discount'] = $old_product['product_discount'];
        $product['product_special']  = $old_product['product_special'];
        $product['product_download'] = $old_product['product_download'];
        $product['product_filter']   = $old_product['product_filter'];
        $product['product_related']  = $old_product['product_related'];
        $product['product_reward']   = $old_product['product_reward'];

        return $product;
    }


    /**
     * @return array
     */
    public function makeForInsert(): array
    {
        if ( ! $this->product) {
            return [];
        }

        return $this->make();
    }


    /**
     * Collect, make, sort the data for 1 product.
     *
     * @param $product
     *
     * @return array
     */
    public function make(): array
    {
        Log::store('311', 'product_update');

        $manufacturer = ProductHelper::getManufacturer($this->product);

        Log::store('312', 'product_update');

        $stock_status = $this->product['stanje_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = 1;

        $description = ProductHelper::getDescription($this->product);

        Log::store('313', 'product_update');

        if ($this->product_to_update) {
            $old_description = ProductDescription::where('product_id', $this->product_to_update->product_id)
                                                 ->where('language_id', agconf('import.default_language'))
                                                 ->first();

            $description = ProductHelper::getDescription($this->product, $old_description);
        }

        Log::store('314', 'product_update');

        if ( ! $this->product['opis'] || empty($this->product['dokumenti'])) {
            $status = 0;
            $this->pushToRevision();
        }

        if ($this->product['enabled'] == 'N') {
            $status = 0;
        }

        Log::store('315', 'product_update');

        Log::store('3.1.', 'product');

        $image_path = ProductHelper::getImagePath($this->product);

        Log::store('3.2.', 'product');

        $attributes = ProductHelper::getAttributes($this->product);

        Log::store('3.3.', 'product');

        $images = ProductHelper::getImages($this->product);

        Log::store('3.4.', 'product');

        $prod = [
            'model'               => $this->product['artikl'],
            'sku'                 => $this->product['artikl'],
            'luceed_uid'          => $this->product['artikl_uid'],
            'upc'                 => $this->product['barcode'],
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $this->product['jamstvo_naziv'] ?: '',
            'location'            => '',
            'price'               => $this->product['mpc'],
            'price_2'             => $this->product['mpc'],
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $this->product['stanje_kol'],
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
            'image'               => ! empty($this->product['dokumenti']) ? $image_path : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_attribute'   => $attributes,
            'product_description' => $description,
            'product_image'       => $images,
            'product_layout'      => [0 => ''],
            'product_category'    => ProductHelper::getCategories($this->product),
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($this->product)],
        ];

        Log::store('316', 'product_update');

        return $prod;
    }


    /**
     *
     */
    private function pushToRevision(): void
    {
        $this->product['data'] = '';

        if ($this->product_to_update) {
            $this->product['product_id'] = $this->product_to_update->product_id;

            if ( ! isset($this->product['naziv'])) {
                $this->product['naziv'] = ProductDescription::where('product_id', $this->product_to_update->product_id)->pluck('name')->first();
                $this->product['artikl_uid'] = $this->product_to_update['luceed_uid'];
                $this->product['artikl'] = $this->product_to_update->sku;
                $this->product['data'] = 'Error importing Luceed data..!';
            }
        }

        $this->product['has_image'] = 0;
        $this->product['has_description'] = 0;

        if (isset($this->product['opis']) && $this->product['opis']) {
            $this->product['has_description'] = 1;
        }

        if ( ! empty($this->product['dokumenti'])) {
            $this->product['has_image'] = 1;
        }

        $has = LuceedProductForRevision::where('uid', $this->product['artikl_uid'])->first();

        Log::store($this->product, 'opis3');

        if ( ! $has) {
            LuceedProductForRevision::insert([
                'uid' => $this->product['artikl_uid'],
                'sku' => $this->product['artikl'],
                'name' => $this->product['naziv'],
                'has_image' => $this->product['has_image'],
                'has_description' => $this->product['has_description'],
                'resolved' => 0,
                'data' => $this->product['data'],
                'date_added' => Carbon::now(),
                'date_modified' => Carbon::now(),
            ]);
        }
    }


    /**
     *
     */
    private function checkHash(): void
    {
        if ($this->luceed_product) {
            $this->hash = $this->luceed_product->hash;
        }
    }


    /**
     * @return mixed
     */
    private function markChecked(string $type = null)
    {
        $imported = 0;

        if ($type) {
            $imported = 1;
        }

        $this->checkHash();

        return Product::where('luceed_uid', $this->product['artikl_uid'])->update([
            'updated' => 1,
            'imported' => $imported,
            'hash'    => $this->hash
        ]);
    }
}