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
    public $product;

    /**
     * @var Collection
     */
    public $product_to_update;

    /**
     * @var Collection
     */
    public $product_to_insert;

    /**
     * @var Collection
     */
    private $luceed_product;

    /**
     * @var Collection
     */
    private $products_for_revision;


    /**
     * LOC_ProductSingle constructor.
     */
    public function __construct()
    {
        $this->products_for_revision = collect();
    }


    /**
     * @return Collection
     */
    public function resolveLuceedProductData(): Collection
    {
        return collect(json_decode(
            htmlspecialchars_decode($this->luceed_product->data),
            true
        ));
    }


    /**
     * @return bool
     */
    public function hasForUpdate(): bool
    {
        $this->product_to_update = Product::where('updated', 0)->first();

        if ($this->product_to_update) {
            $this->luceed_product = LuceedProduct::where('uid', $this->product_to_update->luceed_uid)
                                                 ->where('hash', '!=', $this->product_to_update->imported)
                                                 ->first();

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
        if ($this->product_to_update && $this->luceed_product) {
            Product::where('product_id', $this->product_to_update->product_id)->update([
                'updated'  => 1,
                'hash' => $this->luceed_product->hash
            ]);

            return [
                'status'  => 200,
                'message' => 'updated'
            ];
        }
    }


    /**
     * @return array
     */
    public function finish(): array
    {
        if ($this->products_for_revision->count()) {
            $db = new Database(DB_DATABASE);
            $existing = LuceedProductForRevision::pluck('uid');
            $diff = $this->products_for_revision->whereNotIn('artikl_uid', $existing)->all();

            $count = 0;
            $query_str = '';

            foreach ($diff as $product) {
                $data = collect($product)->toJson();

                $query_str .= '("' . $product->artikl_uid . '", "' . $product->artikl . '", "' . $product->naziv . '", ' . $product->has_image?1:0 . ', ' . $product->has_description?1:0 . ', 0, "", ' . Carbon::now() . ', ' . Carbon::now() . '),';

                $count++;
            }

            $db->query("INSERT INTO " . DB_PREFIX . "product_luceed_revision (uid, sifra, `hash`, has_image, has_description, resolved, `data`, date_added, date_modified) VALUES " . substr($query_str, 0, -1) . ";");
        }

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
        $stock_status = $this->product['stanje_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = 1;

        $description = ProductHelper::getDescription($this->product);

        if ($this->product_to_update) {
            $old_description = ProductDescription::where('product_id', $this->product_to_update->product_id)
                                                 ->where('language_id', agconf('import.default_language'))
                                                 ->first();

            $description = ProductHelper::getDescription($this->product, $old_description);
        }

        if (empty($this->product['opis']) && empty($this->product['dokumenti'])) {
            $status = 0;
            $this->pushToRevision();
        }

        if ($this->product['enabled'] == 'N') {
            $status = 0;
        }

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
            'image'               => ! empty($this->product['dokumenti']) ? ProductHelper::getImagePath($this->product) : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_attribute'   => ProductHelper::getAttributes($this->product),
            'product_description' => $description,
            'product_image'       => ProductHelper::getImages($this->product),
            'product_layout'      => [0 => ''],
            'product_category'    => ProductHelper::getCategories($this->product),
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($this->product)],
        ];

        return $prod;
    }


    /**
     *
     */
    private function pushToRevision(): void
    {
        if ($this->product_to_update) {
            $this->product['product_id'] = $this->product_to_update->product_id;
        }

        $this->product['has_image'] = true;
        $this->product['has_description'] = true;

        if (empty($this->product['opis'])) {
            $this->product['has_description'] = false;
        }

        if (empty($this->product['dokumenti'])) {
            $this->product['has_image'] = false;
        }

        $this->products_for_revision->push($this->product);
    }
}