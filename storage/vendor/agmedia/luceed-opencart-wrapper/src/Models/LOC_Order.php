<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\Models\ShippingCollector;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Coupon;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Order\OrderProduct;
use Agmedia\Models\Order\OrderTotal;
use Agmedia\Models\Product\Product;
use Illuminate\Support\Carbon;

/**
 * Class LOC_Order
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Order
{

    /**
     * @var Luceed
     */
    private $service;

    /**
     * @var array|null
     */
    private $order;

    /**
     * @var array|null
     */
    private $oc_order;

    /**
     * @var array|null
     */
    private $response;

    /**
     * @var string|null
     */
    private $customer_uid = null;

    /**
     * @var array
     */
    private $items_available;

    /**
     * @var int
     */
    private $discount;


    /**
     * LOC_Order constructor.
     *
     * @param array|null $order
     */
    public function __construct(array $order = null)
    {
        $this->oc_order = $order;
        $this->service = new Luceed();

        $this->resolveCouponDiscount();
    }


    /**
     * @param string $customer_uid
     *
     * @return $this
     */
    public function setCustomerUid(string $customer_uid)
    {
        $this->customer_uid = $customer_uid;

        return $this;
    }


    /**
     * @return false
     */
    public function store()
    {
        // Create luceed order data.
        $this->create();

        // Send order to luceed service.
        $this->response = json_decode(
            $this->service->createOrder(['nalozi_prodaje' => [$this->order]])
        );

        $this->log('Store order response: $this->response - LOC_Order #98.', $this->response);

        // If response ok.
        // Update order uid.
        if (isset($this->response->result[0])) {
            /*if ( ! $this->items_available) {
                $raspis = json_decode(
                    $this->service->orderWrit($this->response->result[0])
                );

                $this->log('Raspis response: $raspis - LOC_Order #110.', $raspis);
            }*/

            $this->log($this->oc_order['order_id']);
            $this->log($this->response->result[0]);

            $updated = Order::where('order_id', $this->oc_order['order_id'])->update([
                'luceed_uid' => $this->response->result[0]
            ]);

            if ($updated) {
                return $this->response->result[0];
            }
        }

        return false;
    }


    /**
     * Create luceed order data.
     */
    public function create(): void
    {
        $iznos = number_format($this->oc_order['total'], 2, '.', '');

        $this->items_available = false;//$this->setAvailability();

        $this->order = [
            'nalog_prodaje_b2b' => $this->oc_order['order_id'],
            'datum'             => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'skladiste'         => '001',
            'sa__skladiste'     => '001',
            'status'            => $this->getStatus(),
            'napomena'          => $this->oc_order['comment'],
            //'raspored'          => $this->getDeliveryTime(),
            'komercijalist__radnik_uid' => '206-1063',
            'placa_porez' => 'D',
            'cijene_s_porezom'  => agconf('luceed.with_tax'),
            'partner_uid'       => $this->customer_uid,
            'iznos'             => (float) $iznos,
            'placanja'          => [
                [
                    'vrsta_placanja_uid' => $this->getPaymentType(),
                    'iznos'              => (float) $iznos,
                ]
            ],
            'stavke'            => $this->getItems(),
        ];

        if ($this->items_available) {
            $this->order['sa__skladiste'] = agconf('luceed.stock_warehouse_uid');
            $this->order['na__skladiste'] = agconf('luceed.default_warehouse_uid');
            $this->order['skl_dokument'] = 'MS';
        }

        $this->log('Order create method: $this->>order - LOC_Order #156', $this->order);
    }


    public function getStatus()
    {
        $this->log('getStatus()', 1);

        if ($this->oc_order['payment_code'] == 'wspay') {
            return '02';
        }

        return '01';
    }


    /**
     * @return array
     */
    public function getCustomerData(): array
    {
        $update = $this->checkAddress();

        return [
            'customer_id'   => $this->oc_order['customer_id'],
            'fname'         => $update ? $this->oc_order['shipping_firstname'] : $this->oc_order['payment_firstname'],
            'lname'         => $update ? $this->oc_order['shipping_lastname'] : $this->oc_order['payment_lastname'],
            'email'         => $this->oc_order['email'],
            'phone'         => $this->oc_order['telephone'],
            'company'       => $update ? $this->oc_order['shipping_company'] : $this->oc_order['payment_company'],
            'address'       => $update ? $this->oc_order['shipping_address_1'] : $this->oc_order['payment_address_1'],
            'zip'           => $update ? $this->oc_order['shipping_postcode'] : $this->oc_order['payment_postcode'],
            'city'          => $update ? $this->oc_order['shipping_city'] : $this->oc_order['payment_city'],
            'country'       => $update ? $this->oc_order['shipping_country'] : $this->oc_order['payment_country'],
            'should_update' => $update
        ];
    }


    /**
     * @return mixed
     */
    public function recordError()
    {
        return Order::where('order_id', $this->oc_order['order_id'])->update([
            'luceed_uid' => $this->response->error
        ]);
    }


    /**
     * Get order payment type UID.
     *
     * @return mixed|string
     */
    private function getPaymentType()
    {
        if (in_array($this->oc_order['payment_code'], ['cod', 'bank_transfer'])) {
            $loc_p = (new LOC_Payment())->getList(agconf('luceed.payment.' . $this->oc_order['payment_code']))->first();
        }

        if ($this->oc_order['payment_code'] == 'wspay') {
            $loc_p = (new LOC_Payment())->getList($this->oc_order['payment_card'])->first();
        }

        if (isset($loc_p['vrsta_placanja_uid'])) {
            return $loc_p['vrsta_placanja_uid'];
        }

        return 'false';
    }


    /**
     * @return string
     */
    private function getDeliveryTime(): string
    {
        $time = '01.01.2021 00:00:00';

        if ($this->oc_order['shipping_code'] == 'collector.collector') {
            $collector = ShippingCollector::find($this->oc_order['shipping_collector_id']);

            if ($collector) {
                foreach (agconf('shipping_collector_defaults') as $item) {
                    if ($item['time'] == $collector->collect_time) {
                        $time_str = substr($collector->collect_date, 0, 11) . substr($collector->collect_time, 0, 2) . ':00:00';
                        $time     = Carbon::make($time_str)->format(agconf('luceed.datetime'));
                    }
                }
            }
        }

        return $time;
    }


    /**
     * Get the array data for cart items.
     * Also apply shipping dummy product.
     *
     * @return array
     */
    private function getItems(): array
    {
        // Get the regular products from cart.
        $response = $this->getRegularProducts();
        // Apply shipping dummy product.
        $response[] = $this->getShippingProduct();

        return $response;
    }


    /**
     * @return array
     */
    private function getRegularProducts(): array
    {
        $response       = [];
        $order_products = OrderProduct::where('order_id', $this->oc_order['order_id'])->get();

        $this->log('$order_products', $order_products->toArray());

        if ($order_products->count()) {
            foreach ($order_products as $order_product) {
                $price = $this->getItemPrices($order_product->product_id, $order_product->price);

                if ( ! $price['rabat']) {
                    $price['rabat'] = $this->applyCouponDiscount();
                }

                $response[] = [
                    'artikl' => $order_product->model,
                    'kolicina'   => isset($price['quantity']) ? $price['quantity'] : (int) $order_product->quantity,
                    'cijena'     => (float) $price['cijena'],
                    'rabat'      => (float) number_format($price['rabat'], 2),
                ];
            }
        }

        return $response;
    }


    /**
     * Apply shipping dummy product.
     *
     * @return array
     */
    private function getShippingProduct()
    {
        $shipping_amount = agconf('default_shipping_price');

        /*if ($this->oc_order['total'] > agconf('free_shipping_amount')) {
            $shipping_amount = 0;
        }*/

        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        foreach ($order_total as $item) {
            if ($item->code == 'shipping') {
                $shipping_amount = $item->value;
            }
        }

        return [
            'artikl' => agconf('luceed.shipping_article_uid'),
            'kolicina'   => (int) 1,
            'cijena'     => (float) $shipping_amount,
            'rabat'      => (int) 0,
        ];
    }


    /**
     * @param int   $product_id
     * @param float $price
     *
     * @return array
     */
    private function getItemPrices(int $product_id, float $price): array
    {
        $product = Product::find($product_id);

        if ($price < $product->price) {
            $cijena = number_format($product->price, 2, '.', '');
            $rabat = (($price / $product->price) * 100) - 100;
            $return_rabat = number_format((($price / $product->price) * 100 - 100), 2);

            $B = [50, 75, 90];

            if ($product->scale == 'B' && in_array($rabat, $B)) {
                return [
                    'cijena' => $cijena,
                    'rabat'  => 0,
                    'quantity' => 1 - ($rabat / 100),
                ];
            }

            if ($product->scale == 'C' && $rabat = 50) {
                return [
                    'cijena' => $cijena,
                    'rabat'  => 0,
                    'quantity' => 1 - ($rabat / 100),
                ];
            }

            return [
                'cijena' => $cijena,
                'rabat'  => abs($return_rabat)
            ];
        }

        return [
            'cijena' => number_format($price, 2, '.', ''),
            'rabat'  => 0
        ];
    }


    /**
     * Calculate discount between two prices
     *
     * @param $regular_price
     * @param $action_price
     *
     * @return float
     */
    public static function calculateDiscount($regular_price, $action_price)
    {
        $value = (($regular_price - $action_price) / $regular_price) * 100;

        return $value;
    }


    /**
     * Resolve if an order has coupon discount.
     */
    private function resolveCouponDiscount(): void
    {
        $this->discount = 0;
        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        $this->log('$order_total', $order_total->toArray());

        foreach ($order_total as $item) {
            if ($item->code == 'coupon') {
                preg_match('#\((.*?)\)#', $item->title, $code);

                $coupon = Coupon::where('code', $code[1])->first();

                if ($coupon) {
                    $this->log('$coupon', $coupon->toArray());

                    $this->discount = $coupon->discount;

                    $this->log('$this->discount', $this->discount);
                }
            }
        }
    }


    /**
     * Apply the coupon discount on a price.
     * If discount is not 0.
     *
     * @return int
     */
    private function applyCouponDiscount()
    {
        if ($this->discount) {
            return abs($this->discount);
        }

        return 0;
    }


    /**
     * @return bool
     */
    private function checkAddress()
    {
        if ($this->oc_order['payment_address_1'] == $this->oc_order['shipping_address_1']) {
            return false;
        }

        return true;
    }


    /**
     * @return array
     */
    private function setAvailability()
    {
        $has      = true;
        $iterator = $this->getRegularProducts();

        $this->log('private function setAvailability()');

        foreach ($iterator as $item) {
            $available = json_decode($this->service->getIndividualStock($item['artikl_uid'], agconf('luceed.stock_warehouse_uid')))->result[0];

            $this->log('$available', $available);

            if (isset($available->stanje) && ! empty($available->stanje)) {
                $check = collect($available->stanje)->where('stanje_kol', '>=', $item['kolicina'])->first();

                $this->log('$check', $check);

                if ( ! isset($check->artikl_uid)) {
                    $has = false;
                }
            } else {
                $has = false;
            }
        }

        $this->log('$has', $has);

        return $has;

    }


    /**
     * @param string|null $string
     * @param             $data
     */
    private function log(string $string = null, $data = null): void
    {
        if ($string) {
            Log::store($string, 'proccess_order_' . $this->oc_order['order_id']);
        }

        if ($data) {
            Log::store($data, 'proccess_order_' . $this->oc_order['order_id']);
        }
    }

}