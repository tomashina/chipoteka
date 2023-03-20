<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\Models\ShippingCollector;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Coupon;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Order\OrderProduct;
use Agmedia\Models\Order\OrderStatus;
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
     * @var array
     */
    public $collection = [];

    /**
     * @var Database
     */
    private $db;

    /**
     * @var Luceed
     */
    private $service;

    /**
     * @var
     */
    private $orders;

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
     * @var array|null
     */
    private $customer = null;

    /**
     * @var array
     */
    private $items_available;

    /**
     * @var int
     */
    private $discount;

    private $coupon;

    /**
     * @var string
     */
    private $pickup = '';

    /**
     * @var int
     */
    private $installments = 0;

    /**
     * @var string
     */
    private $query_update_status = '';

    /**
     * @var string
     */
    private $query_update_history = '';


    /**
     * LOC_Order constructor.
     *
     * @param array|null $order
     */
    public function __construct(array $order = null)
    {
        $this->oc_order = $order;
        $this->service  = new Luceed();

        $this->checkInstallments();
        $this->resolveCouponDiscount();
        $this->resolvePickup();
    }


    /**
     * @param $orders
     */
    public function setOrders($orders)
    {
        $this->orders = collect($this->setLuceedOrders($orders));
    }


    /**
     * @param array $customer_data
     *
     * @return $this
     */
    public function setCustomerUid(array $customer_data)
    {
        $this->customer = $customer_data;

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
            $this->service->createOrder(['nalozi_prodaje' => [$this->order]], $this->hasOIB())
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

            /*$updated = Order::where('order_id', $this->oc_order['order_id'])->update([
                'luceed_uid' => $this->response->result[0]
            ]);*/

            $this->db = new Database(DB_DATABASE);
            $updated = $this->db->query("UPDATE " . DB_PREFIX . "order o SET o.luceed_uid = '" . $this->db->escape($this->response->result[0]) . "' WHERE o.order_id = " . $this->oc_order['order_id'] . ";");

            if ($updated) {
                $this->log('Luceed UID updated in DB...');
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

        if ($this->hasOIB()) {
            $iznos = $this->getSubTotal();
        }

        $this->items_available = false;//$this->setAvailability();

        $this->order = [
            'nalog_prodaje_b2b'         => $this->oc_order['order_id']. '-' . Carbon::now()->year.'-101',
            'narudzba'                   => $this->oc_order['order_id'] . '-' . Carbon::now()->year,
            'datum'                     => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'skladiste'                 => '099',// 001 -> 099
            'sa__skladiste'             => '101',// 001 -> 101
            'status'                    => $this->getStatus(),
            'napomena'                  => $this->oc_order['comment'],
            'poruka_dolje'              => $this->oc_order['comment'],
            //'raspored'          => $this->getDeliveryTime(),
            'komercijalist__radnik_uid' => '206-1063',
            'placa_porez'               => 'D',
            'cijene_s_porezom'          => agconf('luceed.with_tax'),
            'partner_uid'               => $this->customer['main'],
            'korisnik__partner_uid'     => $this->customer['alter'] ?: $this->customer['main'],
            'iznos'                     => (float) $iznos,
            'vrsta_isporuke'            => '10',
            'rezervacija_do_datuma'     => $this->getReservation(),
            'placanja'                  => [
                [
                    'vrsta_placanja_uid' => $this->getPaymentType(),
                    'iznos'              => (float) $iznos,
                ]
            ],
            'stavke'                    => $this->getItems(),
        ];

        if ($this->oc_order) {
            $this->order['vezani_poziv_na_broj'] = $this->oc_order['poziv_na_broj'];
        }

        if ( ! $this->hasOIB()) {
            $this->order['na__skladiste'] = '099'; // agconf('luceed.default_warehouse_uid') -> 099
            $this->order['skl_dokument']  = 'MSM'; // MS -> MSM
            $this->order['raspored'] = Carbon::make($this->oc_order['date_added'])->format('d.m.Y H:i:s');
        }

        if ($this->items_available) {
            $this->order['sa__skladiste'] = agconf('luceed.stock_warehouse_uid');
            /*$this->order['na__skladiste'] = '099'; // agconf('luceed.default_warehouse_uid') -> 099
            $this->order['skl_dokument']  = 'MSM'; // MS -> MSM*/
        }

        if ($this->hasOIB()) {
            $this->order['nalog_prodaje_b2b'] = $this->oc_order['order_id']. '-' . Carbon::now()->year.'-b2b';
            $this->order['skladiste'] = '101';
            $this->order['sa__skladiste'] = '101';
            $this->order['skl_dokument']  = 'OT';
            $this->order['vrsta_isporuke']  = '07';
            $this->order['cijene_s_porezom']  = 'N';
            $this->order['vrsta_placanja']  = '96-1063';
            $this->order['komercijalist__radnik_uid'] = '';
        }

        //
        if ($this->pickup != '') {
            $this->order['sa__skladiste'] = $this->pickup;
            $this->order['na__skladiste'] = $this->pickup;
            $this->order['skl_dokument']  = 'DP';
            $this->order['vrsta_isporuke']  = '03';
            $this->order['komercijalist__radnik_uid'] = 'WEBRadnik';
            $this->order['napomena']  = 'Osobno preuzimanje: ' . $this->oc_order['comment'];

            if ($this->oc_order['payment_code'] == 'cod') {
                $this->order['placanja'] = '';
            }
        }

        $this->log('Order create method: $this->>order - LOC_Order #262', $this->order);
    }


    /**
     * @return $this
     */
    private function resolvePickup()
    {
        $shipping_code = substr($this->oc_order['shipping_code'], 0, -1);

        $this->log('resolvePickup() - LOC_Order #269', $shipping_code);

        if ($shipping_code == 'xshippingpro.xshippingpro1_') {
            $xid = (int) substr($this->oc_order['shipping_code'], strpos($this->oc_order['shipping_code'], '_'));

            $this->log($xid);

            foreach (agconf('luceed.pickup') as $key => $item) {
                if ($key == $xid) {
                    $this->pickup = $item;
                }
            }

            $this->log($this->pickup);
        }

        return $this;
    }


    /**
     * @return string
     */
    private function getReservation()
    {
        $date = Carbon::now();

        if ($this->hasOIB()) {
            $date = $date->addDay(7);
        }

        if (in_array($this->oc_order['payment_code'], ['cod', 'wspay'])) {
            $date = $date->addDay(10);
        }

        if ($this->oc_order['payment_code'] == 'bank_transfer') {
            $date = $date->addDay(4);
        }

        return $date->format(agconf('luceed.date'));
    }


    /**
     * @return bool
     */
    private function hasOIB(): bool
    {
        return ($this->oc_order['oib'] != '' && $this->oc_order['customer_group_id'] > 2) ? true : false;
    }


    /**
     * @return string
     */
    private function getStatus()
    {
        if ($this->hasOIB()) {
            return '12';
        }

        if ($this->oc_order['payment_code'] == 'cod') {
            return '02';
        }

        if ($this->oc_order['payment_code'] == 'bank_transfer') {
            return '12';
        }

        if ($this->oc_order['payment_code'] == 'bank_transfer' && $this->pickup != '') {
            return '01';
        }

        if ($this->oc_order['payment_code'] == 'wspay') {
            return '02';
        }

        return '12';
    }


    /**
     * @return array
     */
    public function getCustomerData(): array
    {
        $update = $this->checkAddress();

        return [
            'customer_id'       => $this->oc_order['customer_id'],
            'customer_group_id' => $this->oc_order['customer_group_id'],
            'email'             => $this->oc_order['email'],
            'phone'             => $this->oc_order['telephone'],
            'fname'             => $this->oc_order['payment_firstname'],
            'lname'             => $this->oc_order['payment_lastname'],
            'company'           => $this->oc_order['payment_company'],
            'address'           => $this->oc_order['payment_address_1'],
            'zip'               => $this->oc_order['payment_postcode'],
            'city'              => $this->oc_order['payment_city'],
            'country'           => $this->oc_order['payment_country'],
            'shipping_fname'    => $this->oc_order['shipping_firstname'],
            'shipping_lname'    => $this->oc_order['shipping_lastname'],
            'shipping_company'  => $this->oc_order['shipping_company'],
            'shipping_address'  => $this->oc_order['shipping_address_1'],
            'shipping_zip'      => $this->oc_order['shipping_postcode'],
            'shipping_city'     => $this->oc_order['shipping_city'],
            'shipping_country'  => $this->oc_order['shipping_country'],
            'should_update'     => $update,
            'company'           => isset($this->oc_order['custom_field'][2]) ? $this->oc_order['custom_field'][2] : '',
            'oib'               => $this->oc_order['oib'],
            'has_oib'           => $this->hasOIB()
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
     * @param array|null $statuses
     *
     * @return string
     */
    public function collectStatuses(array $statuses = null): string
    {
        $string = '[';

        if ( ! $statuses) {
            $statuses = OrderStatus::whereNotNull('luceed_status_id')->get();
        }

        foreach ($statuses as $status) {
            $string .= $status->luceed_status_id . ',';
        }

        return substr($string, 0, -1) . ']';
    }


    /**
     * @return $this
     */
    public function sort()
    {
        $statuses = OrderStatus::where('luceed_status_id', '!=', '')->get();
        $orders   = Order::select('order_id', 'luceed_uid', 'email', 'payment_code', 'order_status_id', 'order_status_changed')
                         ->where('order_status_id', '!=', 0)
                         ->get();

        // Check if status have changed.
        foreach ($orders as $order) {
            $l_order = $this->orders->where('nalog_prodaje_uid', $order->luceed_uid)->first();

            if ($l_order) {
                $old_status = $statuses->where('order_status_id', $order->order_status_id)->first();
                $new_status = $statuses->where('luceed_status_id', $l_order->status)->first();

                if ($l_order->status != $old_status->luceed_status_id) {
                    $this->collection[] = [
                        'order_id'     => $order->order_id,
                        'status_from'  => $old_status->luceed_status_id,
                        'status_to'    => $l_order->status,
                        'oc_status_to' => $new_status->order_status_id,
                        'payment'      => $order->payment_code,
                        'email'        => $order->email
                    ];
                }
            }
        }

        if ( ! empty($this->collection)) {
            // Get the apropriate mail.
            for ($i = 0; $i < count($this->collection); $i++) {
                foreach (agconf('mail.' . $this->collection[$i]['payment']) as $key => $item) {
                    if ($key) {
                        if ($this->collection[$i]['status_from'] == $item['from'] && $this->collection[$i]['status_to'] == $item['to']) {
                            $this->collection[$i]['mail'] = $key;
                        }
                    }
                }
            }

            // Collect update status query.
            foreach ($this->collection as $item) {
                $this->query_update_status .= '(' . $item['order_id'] . ', ' . $item['oc_status_to'] . ', NULL, NULL),';
                $this->query_update_history = '(' . $item['order_id'] . ', ' . $item['oc_status_to'] . ', 1, "", "' . Carbon::now() . '"),';
            }
        }

        return $this;
    }


    /**
     * @return int
     * @throws \Exception
     */
    public function updateStatuses(): int
    {
        if ($this->query_update_status != '') {
            $this->db = new Database(DB_DATABASE);

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_temp (id, status, data_1, data_2) VALUES " . substr($this->query_update_status, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "order o INNER JOIN " . DB_PREFIX . "order_temp ot ON o.order_id = ot.id SET o.order_status_id = ot.status, o.order_status_changed = NOW();");
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history (order_id, order_status_id, notify, comment, date_added) VALUES " . substr($this->query_update_history, 0, -1) . ";");

            $this->deleteOrderTempDB();
        }

        return count($this->collection);
    }


    /**
     * @return int
     */
    public function checkStatusDuration()
    {
        $this->collection = [];

        $statuses = OrderStatus::where('luceed_status_id', '!=', '')->get();
        $orders   = Order::select('order_id', 'email', 'payment_code', 'order_status_id', 'order_status_changed')
                         ->where('order_status_id', '!=', 0)
                         ->get();

        foreach ($orders as $order) {
            $status = $statuses->where('order_status_id', $order->order_status_id)->first();

            if ($order->order_status_changed < Carbon::now()->subHour(168) && $order->order_status_changed > Carbon::now()->subHour(72) && $status->luceed_status_id == '11') {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 168,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed < Carbon::now()->subHour(72) && $order->order_status_changed > Carbon::now()->subHour(48) && in_array($status->luceed_status_id, ['02', '05'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 72,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed < Carbon::now()->subHour(48) && $order->order_status_changed > Carbon::now()->subHour(24) && in_array($status->luceed_status_id, ['12', '01'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 48,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed < Carbon::now()->subHour(24) && $order->order_status_changed > Carbon::now() && in_array($status->luceed_status_id, ['02', '05', '12', '01'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 24,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

        }

        // Get the apropriate mail.
        for ($i = 0; $i < count($this->collection); $i++) {
            foreach (agconf('mail.' . $this->collection[$i]['payment'])[0] as $key => $items) {
                if ($this->collection[$i]['status'] == $key) {
                    foreach ($items as $hour => $mail) {
                        if ($this->collection[$i]['longer_then'] == $hour) {
                            $this->collection[$i]['mail'] = $mail;
                        }
                    }
                }
            }
        }

        return count($this->collection);
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     *
     */
    private function checkInstallments(): void
    {
        if ($this->oc_order && $this->oc_order['installment'] != '0000') {
            $this->installments = (int) substr($this->oc_order['installment'], 0, 2);
        }
    }


    /**
     * Get order payment type UID.
     *
     * @return mixed|string
     */
    private function getPaymentType()
    {
        if ($this->hasOIB()) {
            return '96-1063';
        }

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
        $this->log('$this->installments', $this->installments);

        if ($order_products->count()) {
            foreach ($order_products as $order_product) {
                /*$price = $this->getItemPrices($order_product->product_id, $order_product->price);

                if ( ! $price['rabat']) {
                    $price['rabat'] = $this->applyCouponDiscount();
                }*/

                $price = $order_product->price;

                if ($this->installments > 12) {
                    $price = $order_product->price * 1.07;
                }

                $response[] = [
                    'artikl'   => $order_product->model,
                    'kolicina' => (int) $order_product->quantity,
                    'cijena'   => (float) number_format($price, 2, '.', ''),
                    'rabat'    => 0 //(float) number_format($price['rabat'], 2),
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

        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

        foreach ($order_total as $item) {
            if ($item->code == 'shipping') {
                if ($this->hasOIB()) {
                    $shipping_amount = $item->value / 1.25;
                } else {
                    $shipping_amount = $item->value;
                }
            }
        }

        return [
            'artikl'   => agconf('luceed.shipping_article_uid'),
            'kolicina' => (int) 1,
            'cijena'   => (float) $shipping_amount,
            'rabat'    => (int) 0,
        ];
    }


    /**
     * @return int|string
     */
    private function getSubTotal()
    {
        $order_total = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();
        $total = 0;

        foreach ($order_total as $item) {
            if ($item->code == 'total') {
                $total = $item->value;
            }
        }

        return number_format($total, 2, '.', '');
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
            $cijena       = number_format($product->price, 2, '.', '');
            $return_rabat = number_format((($price / $product->price) * 100 - 100), 4);

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
        if ($this->oc_order) {
            $this->discount = 0;
            $order_total    = OrderTotal::where('order_id', $this->oc_order['order_id'])->get();

            $this->log('$order_total', $order_total->toArray());

            foreach ($order_total as $item) {
                if ($item->code == 'coupon') {
                    preg_match('#\((.*?)\)#', $item->title, $code);

                    $this->coupon = Coupon::where('code', $code[1])->first();

                    if ($this->coupon) {
                        $this->log('$coupon', $this->coupon->toArray());

                        $this->discount = $this->coupon->discount;

                        $this->log('$this->discount', $this->discount);
                    }
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
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setLuceedOrders($orders): array
    {
        $json = json_decode($orders);

        return $json->result[0]->nalozi_prodaje;
    }


    /**
     * @throws \Exception
     */
    private function deleteOrderTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "order_temp`");
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