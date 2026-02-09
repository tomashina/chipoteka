<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Luceed;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class Availability
{

    /**
     * @var Luceed
     */
    protected Luceed $service;

    /**
     * @var array
     */
    protected array $order;

    /**
     * @var array
     */
    protected array $products; // aka. regular_products

    /**
     * @var array
     */
    protected array $shipping_product;

    /**
     * @var string
     */
    protected string $pickup = '';

    /**
     * @var array
     */
    protected array $items_available = [];

    /**
     * @var array
     */
    protected array $items_resolved = [];

    /**
     * @var Collection
     */
    protected Collection $warehouse_list;

    /**
     * @var array
     */
    public array $items_required = [];

    /**
     * @var mixed
     */
    public $pickup_warehouse;

    /**
     * @var bool
     */
    public bool $has_any_in_pickup_store = false;

    /**
     * @var bool
     */
    public bool $has_all_in_pickup_store = false;

    /**
     * @var array
     */
    public array $nalozi = [];

    /**
     * @var int
     */
    public int $nalog_count = 1;

    /**
     * @var int
     */
    private int $loop = 1;


    /**
     * @param array  $products
     * @param array  $shipping_product
     * @param array  $order
     * @param string $pickup
     */
    public function __construct(array $products, array $shipping_product, array $order, string $pickup)
    {
        $this->service          = new Luceed();
        $this->products         = $products;
        $this->shipping_product = $shipping_product;
        $this->order            = $order;
        $this->pickup           = $pickup;
        $this->items_available  = $this->resolveAvailabilityForCart();
        $this->warehouse_list   = (new LOC_Warehouse())->getList();
        $this->pickup_warehouse = $this->warehouse_list->where('skladiste', '=', $this->pickup)->first();

        //
        $this->log('resolveAvailability()::$this->products', $this->products);
        $this->log('resolveAvailability()::$this->order', $this->order);
        $this->log('resolveAvailability()::$this->pickup', $this->pickup);
        $this->log('resolveAvailability()::$this->items_available', $this->items_available);
        $this->log('resolveAvailability()::$this->warehouse_list', $this->warehouse_list);
        $this->log('resolveAvailability()::$this->pickup_warehouse', $this->pickup_warehouse);
    }


    /**
     * @return array
     */
    public function getNalozi(): array
    {
        return $this->nalozi;
    }


    /**
     * @return void
     */
    public function checkPickupStore(): void
    {
        if ( ! isset($this->pickup_warehouse['skladiste_uid'], $this->pickup_warehouse['skladiste'])) {
            $this->log_items('checkPickupStore(): pickup warehouse missing.', $this->pickup_warehouse);
            $this->items_required = $this->getItems(true);

            return;
        }

        $items_in_pickup = [];
        $resolved_items = [
            'regular' => $this->products,
            'required' => []
        ];

        // Check if all items are available in pickup store
        foreach ($this->items_available['items'] ?? [] as $store_uid => $items) {
            //
            if ($store_uid == $this->pickup_warehouse['skladiste_uid']) {
                $this->has_any_in_pickup_store = true;
                $items_in_pickup = $items;

                // Ako ima sve u pickup poslovnici
                if (count($this->products) == count($items)) {
                    $this->has_all_in_pickup_store = true;

                    foreach ($items as $item) {
                        if ($item['qty'] < $item['req']) {
                            $this->has_all_in_pickup_store = false;
                        }
                    }

                    if ($this->has_all_in_pickup_store) {
                        $this->nalozi[$store_uid] = OrderHelper::getNalog(
                            $this->getItems(true),
                            $this->order['order_id'],
                            '',
                            $this->pickup_warehouse['skladiste'],
                            $this->pickup_warehouse,
                            'Osobno preuzimanje: ' . $this->order['comment']
                        );

                        $this->log_items('resolveAvailability()::$this->nalozi from return', $this->nalozi);

                        return;
                    }
                }

                // Ako ima dio u pickup poslovnici.
                if ( ! $this->has_all_in_pickup_store) {
                    //
                    $this->items_resolved = OrderHelper::getAvailableItemsFromStore($this->getItems(true), $items);
                    $this->items_required = $this->items_resolved['required'];
                    // Napravi nalog za pickup poslovnicu sa onim šta ima.
                    if ( ! empty($this->items_resolved['available'])) {
                        $this->nalozi[$store_uid] = OrderHelper::getNalog(
                            $this->getItems(true),
                            $this->order['order_id'],
                            '',
                            $this->pickup_warehouse['skladiste'],
                            $this->pickup_warehouse,
                            'Osobno preuzimanje: ' . $this->order['comment']
                        );

                        $this->log_items('resolveAvailability()::$this->nalozi from  ! $this->has_all_in_pickup_store');
                    }
                }

                $this->unsetStore($store_uid);
            }

            //
            $resolved_items = OrderHelper::resolveRequiredProducts(
                $this->products, $this->items_resolved['available'] ?: []
            );

            $this->products = $resolved_items['regular'];
            //$this->items_required = $resolved_items['required'];
        }

        Log::store($resolved_items, 'test_order_loop');

        if ( ! $this->has_any_in_pickup_store) {
            //
            $this->items_resolved = OrderHelper::getAvailableItemsFromStore($this->getItems(true), $items_in_pickup);
            $this->items_required = $this->items_resolved['required'];
            //
            $this->nalozi[$this->pickup_warehouse['skladiste_uid']] = OrderHelper::getNalog(
                $this->getItems(true),
                $this->order['order_id'],
                '',
                $this->pickup_warehouse['skladiste'],
                $this->pickup_warehouse,
                'Osobno preuzimanje: ' . $this->order['comment']
            );

            $this->log_items('resolveAvailability()::$this->nalozi from  ! $this->has_any_in_pickup_store');
        }

        $this->log_items('resolveAvailability()::$this->nalozi', $this->nalozi);
    }


    /**
     * @param string $warehouse
     *
     * @return void
     */
    public function checkWarehouse(string $warehouse): void
    {
        $house = $this->warehouse_list->where('skladiste', '=', $warehouse)->first();

        if ( ! $house || empty($this->items_available['items'])) {
            return;
        }

        Log::store($this->loop, 'test_order_loop');
        Log::store($this->items_required, 'test_order_loop');

        foreach ($this->items_available['items'] as $store_uid => $items) {
            if ($this->loop && $store_uid == $house['skladiste_uid']) {

                $has_all_in_one_store = OrderHelper::hasAllInOneStore($this->items_required, $items);
                $this->items_resolved = OrderHelper::getAvailableItemsFromStore($this->items_required, $items);

                if ($has_all_in_one_store) {
                    $this->nalozi[$store_uid] = OrderHelper::getNalog(
                        $this->items_required,
                        $this->order['order_id'],
                        $this->nalog_count,
                        $warehouse,
                        $this->pickup_warehouse
                    );

                    $this->items_required = $this->items_resolved['required'];

                    $this->unsetStore($store_uid);
                    $this->nalog_count++;
                    $this->loop = 0;

                    return;
                }

                $this->log_items('checkWarehouse()::$this->items_resolved', $this->items_resolved);

                if ( ! empty($this->items_resolved['available'])) {
                    $this->nalozi[$store_uid] = OrderHelper::getNalog(
                        $this->items_resolved['available'],
                        $this->order['order_id'],
                        $this->nalog_count,
                        $warehouse,
                        $this->pickup_warehouse
                    );

                    $this->loop++;
                    $this->nalog_count++;
                }

                $this->items_required = $this->items_resolved['required'];

                $this->unsetStore($store_uid);
            }

            if (empty($this->items_required)) {
                $this->loop = 0;
            }
        }
    }


    /**
     * @param string $type
     *
     * @return void
     */
    public function collectBy(string $type)
    {
        if ($type == 'sum') {
            $this->collectBySum();
        }
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    private function collectBySum()
    {
        if (empty($this->items_available['items']) || empty($this->items_required)) {
            return;
        }

        $sum       = 0;
        $max_store = '';

        $this->log_items('$this->items_available', $this->items_available);

        foreach ($this->items_available['items'] as $store_uid => $items) {
            $loop_sum = 0;
            foreach ($items as $item) {
                $loop_sum += $item['qty'];
            }

            if ($loop_sum > $sum) {
                $sum       = $loop_sum;
                $max_store = $store_uid;
            }
        }

        $warehouse = $this->warehouse_list->where('skladiste_uid', '=', $max_store)->first();

        $this->log_items('$warehouse', $warehouse);

        if ($warehouse && isset($warehouse['skladiste'])) {
            $this->checkWarehouse($warehouse['skladiste']);
        }

        if ($this->loop && $this->loop < 10) {
            $this->collectBy('sum');
        }

        $this->log_items('$this->nalozi', $this->nalozi);
    }


    /**
     * @param string $store_uid
     *
     * @return void
     */
    private function unsetStore(string $store_uid): void
    {
        if (isset($this->items_available['items'][$store_uid])) {
            unset($this->items_available['items'][$store_uid]);
        }
    }


    /**
     * @return array
     */
    private function resolveAvailabilityForCart(): array
    {
        $availables      = [];
        $warehouse_query = OrderHelper::getAvailabilityPickupQuery(agconf('import.warehouse.pickup'));

        // Collect availability for all items
        foreach ($this->products as $product) {
            $service = $this->service->getStock($warehouse_query, $product['artikl']);
            $json    = [];

            if (isset(json_decode($service)->result[0]->stanje)) {
                $json = json_decode($service)->result[0]->stanje;
            }

            $available = collect($json)->where('raspolozivo_kol', '>', 0);

            if ($available->count()) {
                foreach ($available as $warehouse) {
                    $availables['items'][$warehouse->skladiste_uid][] = [
                        'uid' => $product['artikl'],
                        'qty' => $warehouse->raspolozivo_kol,
                        'req' => $product['kolicina']
                    ];
                }
            }
        }

        return $availables;
    }


    /**
     * Get the array data for cart items.
     * Also apply shipping dummy product.
     *
     * @param bool $as_required
     *
     * @return array
     */
    private function getItems(bool $as_required = false): array
    {
        // Get the regular products from cart.
        $response = array_values($this->products);

        if ($as_required) {
            $required_return = [];

            foreach ($response as $item) {
                $required_return[$item['artikl']] = ['qty' => $item['kolicina']];
            }

            return $required_return;
        }

        // Apply shipping dummy product.
        if ($this->pickup == '') {
            $response[] = $this->shipping_product;
        }

        return $response;
    }


    /**
     * @param $string
     * @param $data
     *
     * @return void
     */
    private function log_items($string, $data = null): void
    {
        if (is_string($data) || is_int($data) || is_float($data)) {
            Log::store($string . ' => ' . $data, 'test_order_' . $this->order['order_id'] . '_items');

            return;
        }

        Log::store($string, 'test_order_' . $this->order['order_id'] . '_items');

        if ($data) {
            Log::store($data, 'test_order_' . $this->order['order_id'] . '_items');
        }
    }


    /**
     * @param string $string
     * @param        $data
     *
     * @return void
     */
    private function log(string $string, $data = null): void
    {
        if (is_string($data) || is_int($data) || is_float($data)) {
            Log::store($string . ' => ' . $data, 'test_order_' . $this->order['order_id']);

            return;
        }

        Log::store($string, 'test_order_' . $this->order['order_id']);

        if ($data) {
            Log::store($data, 'test_order_' . $this->order['order_id']);
        }
    }

}
