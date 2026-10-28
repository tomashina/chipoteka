<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Models\LuceedProduct;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
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
    public $product_to_delete = null;

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

    private $status_manager;


    /**
     * LOC_ProductSingle constructor.
     */
    public function __construct($product = null)
    {
        if ($product) {
            $this->product = $this->setProduct($product);
        }
    }


    /**
     * @return Collection
     */
    public function resolveLuceedProductData(): Collection
    {
        return collect(unserialize(base64_decode($this->luceed_product->data)));
    }


    /**
     * @param string|null $uid
     *
     * @return bool
     */
    public function hasForUpdate($uid = null): bool
    {
        if ( ! $uid) {
            // An incomplete article remains pending, but has no trusted cache
            // row until a later valid feed. Continue with other pending articles.
            $pending = LuceedProductForUpdate::query()
                ->join('product_luceed AS luceed_snapshot', 'product_luceed_for_update.uid', '=', 'luceed_snapshot.uid')
                ->select('product_luceed_for_update.uid')
                ->first();

            if ( ! $pending) {
                return false;
            }

            $uid = $pending->uid;
        }

        if ($uid) {
            $this->product_to_update = Product::where('luceed_uid', $uid)->first();
            $this->luceed_product = LuceedProduct::query()->where('uid', $uid)->first();

            if ($this->product_to_update && $this->luceed_product) {
                $this->product = $this->resolveLuceedProductData();

                return true;
            }
        }

        return false;
    }


    /**
     * @param \stdClass $product
     */
    public function setForUpdate(\stdClass $product = null)
    {
        $product_array = $this->product;

        if ($product) {
            $product_array = ProductHelper::collectLuceedData($product);
        }

        $this->hash = ProductHelper::hashLuceedData($product_array);
        $this->product_to_update = Product::where('luceed_uid', $product_array['artikl_uid'])->first();

        if ($this->product_to_update) {
            $this->product = collect($product_array);
        } else {
            $this->product_to_insert = collect($product_array);
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

        Log::store($diff, 'diff');

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
     * @return bool
     */
    public function hasForDelete()
    {
        // Absence from a response is not an ERP instruction to delete an article.
        // Explicit enabled/webshop flags control visibility through status sync.
        return false;
    }


    /**
     * @return int
     */
    public function getDeleteProductId(): int
    {
        if ($this->product_to_delete) {
            return $this->product_to_delete['product_id'];
        }

        return 0;
    }


    /**
     * @return int
     */
    public function deleteFromRevision()
    {
        if ($this->product_to_delete) {
            return LuceedProductForRevision::where('sku', $this->product_to_delete['sku'])->delete();
        }

        return 0;
    }


    /**
     * @return array
     */
    public function finishDelete(): array
    {
        return [
            'status'  => 200,
            'message' => 'deleted'
        ];
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

        return [
            'status'  => 300,
            'message' => 'error'
        ];
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

        $product                     = $this->make();
        $product['product_discount'] = $old_product['product_discount'];
        $product['product_special']  = $old_product['product_special'];
        $product['product_download'] = $old_product['product_download'];
        $product['product_filter']   = $old_product['product_filter'];
        $product['product_related']  = $old_product['product_related'];
        $product['product_reward']   = $old_product['product_reward'];

        if (!$this->status_manager) {
            $this->status_manager = new LOC_ProductStatus();
        }

        $product['status'] = $this->status_manager->resolveUpdateStatus(
            (int) $this->product_to_update['product_id'],
            (int) $this->product_to_update['status'],
            (int) $product['status'],
            (int) $this->product_to_update['quantity'],
            ProductHelper::getActivationMinimum(collect($this->product)->toArray())
        );
        $product['_luceed_sync'] = true;

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
        $manufacturer = ProductHelper::getManufacturer($this->product);
        $categories   = ProductHelper::getCategories($this->product);
        $stock_status = $this->product['stanje_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = ProductHelper::isLuceedProductEligible(collect($this->product)->toArray()) ? 1 : 0;

        $description = ProductHelper::getDescription($this->product);

        if ($this->product_to_update) {
            $old_description = ProductDescription::where('product_id', $this->product_to_update->product_id)
                                                 ->where('language_id', agconf('import.default_language'))
                                                 ->first();

            $description = ProductHelper::getDescription($this->product, $old_description);
        }

        $description[agconf('import.default_language')]['tag'] = ProductHelper::getTags($categories, $manufacturer);

        if ( ! $this->product['opis'] || empty($this->product['dokumenti']) || ! $this->product['mpc'] || $this->product['mpc'] == 0) {
            $status = 0;
            $this->pushToRevision();
        }

        if ($this->product['enabled'] == 'N') {
            $status = 0;
        }

        $attributes = ProductHelper::getAttributes($this->product);
        $images = ProductHelper::getImages($this->product);
        $image_path = isset($images[0]['image']) ? $images[0]['image'] : 'image/placeholder.png';
        unset($images[0]);

        if ($image_path === 'not_valid_image' || $image_path === 'image/placeholder.png') {
            $status = 0;
        }

        if (!$this->product_to_update && (float) $this->product['stanje_kol'] < ProductHelper::getActivationMinimum(collect($this->product)->toArray())) {
            $status = 0;
        }

        $prod = [
            'model'               => $this->product['artikl'],
            'sku'                 => $this->product['artikl'],
            'luceed_uid'          => $this->product['artikl_uid'],
            'upc'                 => $this->product['barcode'],
            'ean'                 => ProductHelper::setText($this->product['kataloski_broj']),
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $this->product['jamstvo_naziv'] ?: '',
            'location'            => '',
            'price'               => $this->product['mpc'],
            'price_2'             => $this->product['mpc'],
            'jm'                  => $this->product['jm'],
            'pakiranje'           => $this->product['pakiranje'],
            'vpc'                 => $this->product['vpc'],
            'tax_class_id'        => ProductHelper::getTax($this->product),
            'quantity'            => $this->product['stanje_kol'] ?: 0,
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
            'product_category'    => $categories,
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($this->product)],
        ];

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

        if ($this->product['mpc'] || $this->product['mpc'] == 0) {
            $this->product['data'] = 'Cijena je 0 kn.';
        }

        $has = LuceedProductForRevision::where('uid', $this->product['artikl_uid'])->first();

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


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array|\stdClass
     */
    private function setProduct($product)
    {
        $prods = json_decode($product);

        if (is_array($prods->result[0]->artikli[0])) {
            return $prods->result[0]->artikli[0];
        }

        return collect($prods->result[0]->artikli[0])->toArray();
    }
}
