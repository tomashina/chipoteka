<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\Models\ShippingCollector;
use Agmedia\Luceed\Luceed;
use Agmedia\LuceedOpencartWrapper\Helpers\OrderHelper;
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
     * @var int
     */
    private $discount;

    private $coupon;

    /**
     * @var string
     */
    private $pickup = '';
    private $pickup_warehouse = [];
    private $availability = false;
    private $regular_products = [];

    /**
     * @var bool|array
     */
    private $items_available = false;

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
        // Check if it's a pickup
        if ($this->pickup != '') {
            // Set up availability.
            $this->availability = $this->resolveAvailability();

            if ($this->items_available['has_all_in_pickup_store']) {
                $this->createPickup($this->items_available['nalozi'][$this->pickup_warehouse['skladiste_uid']]);

                return $this->sendOrder(true);

            } else {
                foreach ($this->items_available['nalozi'] as $nalog) {
                    $this->createPickup($nalog);

                    $this->sendOrder(($nalog['broj_naloga'] == '') ? true : false);
                }

                return 1;
            }
        }

        $this->create();

        return $this->sendOrder(true);
    }


    /**
     * @param bool $save
     *
     * @return false|mixed
     * @throws \Exception
     */
    private function sendOrder(bool $save = false)
    {
        // Send order to luceed service.
        $this->response = json_decode(
            $this->service->createOrder(['nalozi_prodaje' => [$this->order]], $this->hasOIB())
        );

        /*$this->log('Store order response: $this->response - LOC_Order #98.', $this->response);
        $this->log($this->oc_order['order_id'] . ' Order sent...');

        return 1;*/

        // If response ok.
        // Update order uid.
        if (isset($this->response->result[0]) && $save) {

            $this->log($this->oc_order['order_id']);
            $this->log($this->response->result[0]);

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

        //$this->items_available = $this->getItemsAvailability();

        $this->order = [
            'nalog_prodaje_b2b'         => $this->oc_order['order_id']. '-' . Carbon::now()->year.'-101',
            'narudzba'                  => $this->oc_order['order_id'] . '-' . Carbon::now()->year,
            'vezani_poziv_na_broj'      => $this->oc_order['poziv_na_broj'],
            'datum'                     => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'skladiste'                 => '099',// 001 -> 099
            'sa__skladiste'             => '101',// 001 -> 101
            //'pj_id'                     => '00',
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

        if ( ! $this->hasOIB()) {
            $this->order['na__skladiste'] = '099'; // agconf('luceed.default_warehouse_uid') -> 099
            $this->order['skl_dokument']  = 'MSM'; // MS -> MSM
            $this->order['raspored'] = Carbon::make($this->oc_order['date_added'])->format('d.m.Y H:i:s');
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
        /*if ($this->pickup != '' && $this->items_available['has_all_in_pickup_store']) {
            $this->order['sa__skladiste'] = $this->pickup;
            $this->order['na__skladiste'] = $this->pickup;
            $this->order['skl_dokument']  = 'DP';
            $this->order['vrsta_isporuke']  = '03';
            $this->order['napomena']  = 'Osobno preuzimanje: ' . $this->oc_order['comment'];

            if ($this->oc_order['payment_code'] == 'cod') {
                $this->order['placanja'] = '';
            }
        }*/

        $this->log('Order create method: $this->>order - LOC_Order #262', $this->order);
    }


    /**
     * Create luceed order data.
     */
    public function createPickup(array $nalog): void
    {
        $this->log('createPickup() ---> $nalog ::::: ', $nalog);

        $this->order = [
            'nalog_prodaje_b2b'         => $this->oc_order['order_id']. '-' . Carbon::now()->year.'-101' . $nalog['broj_naloga'],
            'narudzba'                  => $this->oc_order['order_id'] . '-' . Carbon::now()->year . $nalog['broj_naloga'],
            'datum'                     => Carbon::make($this->oc_order['date_added'])->format(agconf('luceed.date')),
            'raspored'                  => Carbon::make($this->oc_order['date_added'])->format('d.m.Y H:i:s'),
            'skladiste'                 => $nalog['na__skladiste'],
            'sa__skladiste'             => $nalog['sa__skladiste'],
            'na__skladiste'             => $nalog['na__skladiste'],
            //'pj_id'                     => $nalog['pj'],
            'status'                    => $this->getStatus(),
            'napomena'                  => $nalog['napomena'],
            'poruka_dolje'              => $nalog['napomena'],
            'komercijalist__radnik_uid' => '206-1063',
            'placa_porez'               => 'D',
            'cijene_s_porezom'          => agconf('luceed.with_tax'),
            'partner_uid'               => $this->customer['main'],
            'korisnik__partner_uid'     => $this->customer['alter'] ?: $this->customer['main'],
            'iznos'                     => (float) $nalog['iznos'],
            'vrsta_isporuke'            => $nalog['vrsta_isporuke'],
            'skl_dokument'              => $nalog['skl_dokument'],
            'rezervacija_do_datuma'     => $this->getReservation(),
            'placanja'                  => [
                [
                    'vrsta_placanja_uid' => $this->getPaymentType(),
                    'iznos'              => (float) $nalog['iznos'],
                ]
            ],
            'stavke'                    => $nalog['stavke'],
        ];

        if ($nalog['broj_naloga'] == '') {
            $this->order['vezani_poziv_na_broj'] = $this->oc_order['poziv_na_broj'];
        }

        if ($this->oc_order['payment_code'] == 'cod') {
            $this->order['placanja'] = '';
        }

        $this->log('createPickup() ::::: ', $this->order);
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    private function resolveAvailability()
    {
        $this->regular_products = $this->getRegularProducts();
        $this->items_available  = $this->resolveAvailabilityForCart();
        $warehouse_list         = (new LOC_Warehouse())->getList();
        $this->pickup_warehouse = $warehouse_list->where('skladiste', '=', $this->pickup)->first();

        $this->items_available['has_all_in_pickup_store'] = 0;
        $this->items_available['products_required']       = [];

        $products_available = false;
        $nalog_count        = 1;
        $has_any_items_in_pickup_store = false;

        $this->log_items('resolveAvailability()::$this->pickup_warehouse', $this->pickup_warehouse);
        $this->log_items('resolveAvailability()::$this->regular_products', $this->regular_products);
        $this->log_items('resolveAvailability()::$items_available', $this->items_available);

        // Check if all items are available in pickup store
        foreach ($this->items_available['items'] as $store_uid => $items) {
            Log::store($store_uid . ' - store_uid', 'items');
            Log::store($this->pickup_warehouse['skladiste_uid'], 'items');
            if ($store_uid == $this->pickup_warehouse['skladiste_uid']) {
                $has_any_items_in_pickup_store = true;
                // Ako ima sve u pickup poslovnici
                if (count($this->regular_products) == count($items)) {
                    $has_all_in_pickup_store = true;

                    foreach ($items as $item) {
                        if ($item['qty'] < $item['req']) {
                            $has_all_in_pickup_store = false;
                        }
                    }

                    if ($has_all_in_pickup_store) {
                        $this->items_available['has_all_in_pickup_store'] = 1;

                        $this->items_available['nalozi'][$store_uid] = [
                            'broj_naloga'    => '',
                            'iznos'          => $this->getIznos(),
                            'sa__skladiste'  => $this->pickup_warehouse['skladiste'],
                            'na__skladiste'  => $this->pickup_warehouse['skladiste'],
                            'pj'             => $this->pickup_warehouse['pj'],
                            'skl_dokument'   => 'DP',
                            'vrsta_isporuke' => '03',
                            'napomena'       => 'Osobno preuzimanje: ' . $this->oc_order['comment'],
                            'stavke'         => $this->getItems()
                        ];

                        return;
                    }
                }

                // Ako ima dio u pickup poslovnici.
                if ( ! $this->items_available['has_all_in_pickup_store']) {
                    foreach ($items as $item) {
                        // Provjeri koji artikli fale i koliko.
                        if ($item['qty'] < $item['req']) {
                            // Uzmi koliko ima.
                            if ($item['qty']) {
                                $products_available[$item['uid']] = [
                                    'qty' => $item['qty']
                                ];
                            }
                        }
                        // Uzmi artikle kojih ima u pickup poslovnici.
                        if ($item['qty'] >= $item['req']) {
                            $products_available[$item['uid']] = [
                                'qty' => $item['req']
                            ];
                        }
                    }

                    //
                    if ($products_available) {
                        //$stavke = OrderHelper::getOrderItems($products_available, $this->oc_order['order_id']);

                        $this->items_available['nalozi'][$store_uid] = [
                            'broj_naloga'    => '',
                            'iznos'          => $this->getIznos(),
                            'sa__skladiste'  => $this->pickup_warehouse['skladiste'],
                            'na__skladiste'  => $this->pickup_warehouse['skladiste'],
                            'pj'             => $this->pickup_warehouse['pj'],
                            'skl_dokument'   => 'DP',
                            'vrsta_isporuke' => '03',
                            'napomena'       => 'Osobno preuzimanje: ' . $this->oc_order['comment'],
                            'stavke'         => $this->getItems()
                        ];
                    }
                }

                unset($this->items_available['items'][$store_uid]);
            }
            //

            $resolved_items = OrderHelper::resolveRequiredProducts(
                $this->regular_products, $products_available ?: []
            );

            $this->regular_products = $resolved_items['regular'];
            $this->items_available['products_required'] = $resolved_items['required'];
        }

        if ( ! $has_any_items_in_pickup_store) {
            $this->items_available['nalozi'][$this->pickup_warehouse['skladiste_uid']] = [
                'broj_naloga'    => '',
                'iznos'          => $this->getIznos(),
                'sa__skladiste'  => $this->pickup_warehouse['skladiste'],
                'na__skladiste'  => $this->pickup_warehouse['skladiste'],
                'pj'             => $this->pickup_warehouse['pj'],
                'skl_dokument'   => 'DP',
                'vrsta_isporuke' => '03',
                'napomena'       => 'Osobno preuzimanje: ' . $this->oc_order['comment'],
                'stavke'         => $this->getItems()
            ];
        }

        $this->log_items('resolveAvailability()::$items_available_2', $this->items_available);
        //
        // Ako nema svih u pickup poslovnici i ako postoje koji fale.
        if (isset($this->items_available['products_required']) && ! empty($this->items_available['products_required'])) {
            $required_products = $this->items_available['products_required'];
            $loop = 1;

            $this->log_items('Start 2...');

            // Provjeri ostale trgovine za artikle koje fale.
            // Ali u ovoj iteraciji samo ako ih ima svih u jednoj poslovnici.
            foreach ($this->items_available['items'] as $store_uid => $items) {
                if ($loop) {
                    $this->log_items($loop);
                    $this->log_items($store_uid);
                    $loop++;

                    $store_sifra = OrderHelper::getStoreSifra($store_uid, $warehouse_list);
                    $has_all_in_one_store = OrderHelper::hasAllInOneStore($required_products, $items);

                    if ($has_all_in_one_store) {
                        $this->log_items('$has_all_in_one_store');
                        $this->items_available['nalozi'][$store_uid] = OrderHelper::getNalog(
                            $required_products,
                            $this->oc_order['order_id'],
                            $nalog_count,
                            $store_sifra,
                            $this->pickup_warehouse
                        );

                        $this->log_items('resolveAvailability()::$items_available_3', $this->items_available);

                        return;
                    }

                    $resolved_products = OrderHelper::getAvailableItemsFromStore($required_products, $items);

                    if ( ! empty($resolved_products['available'])) {
                        $this->items_available['nalozi'][$store_uid] = OrderHelper::getNalog(
                            $resolved_products['available'],
                            $this->oc_order['order_id'],
                            $nalog_count,
                            $store_sifra,
                            $this->pickup_warehouse
                        );
                    }

                    $required_products = $resolved_products['required'];

                    $this->log_items($resolved_products);

                    if (empty($required_products)) {
                        $loop = false;
                    }

                    $nalog_count++;
                }
            }

            $this->log_items('End 2...');

            $this->log_items('resolveAvailability()::$items_available_3', $this->items_available);
        }

    }


    /**
     * @return array
     */
    private function resolveAvailabilityForCart()
    {
        $availables = [];
        $warehouse_query = OrderHelper::getAvailabilityPickupQuery(agconf('import.warehouse.pickup'));

        // Collect availability for all items
        foreach ($this->regular_products as $product) {
            $service   = $this->service->getStock($warehouse_query, $product['artikl']);
            $json      = json_decode($service)->result[0]->stanje;
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





    private function getItemsAvailability()
    {
        $availables = [];

        $products        = $this->getRegularProducts();
        $warehouse_query = OrderHelper::getAvailabilityPickupQuery(agconf('import.warehouse.pickup'));
        $warehouse_list  = (new LOC_Warehouse())->getList();

        $this->log_items('getItemsAvailability() -> $products...', $products);
        $this->log_items('count $products', count($products));

        // Collect availability for all items
        foreach ($products as $product) {
            $service   = $this->service->getStock($warehouse_query, $product['artikl']);
            $json      = json_decode($service)->result[0]->stanje;
            $available = collect($json)->where('raspolozivo_kol', '>', 0);

            //$this->log_items('$available()..................', $available);
            //$this->log('$available()->count()', $available->count());

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

        $this->log_items('getItemsAvailability() -> $availables...', $availables);

        $available_warehouses = [];

        foreach ($availables['items'] as $store_uid => $items) {
            // Check if all items are available in pickup store
            if (count($products) == count($items)) {

                $has_all_in_pickup_store = $warehouse_list->where('skladiste_uid', '=', $store_uid . '-1063')
                                                          ->where('skladiste', '=', $this->pickup)
                                                          ->first();

                $this->log_items('getItemsAvailability() -> $has_all_in_pickup_store...', $has_all_in_pickup_store);

                if ($has_all_in_pickup_store) {
                    $availables['has_all_in_pickup_store'] = $has_all_in_pickup_store;
                }
                /* else {
                    $has_all_in_one_store = $warehouse_list->where('skladiste_uid', '=', $store_uid)
                                                           ->first();
                    if ($has_all_in_one_store) {
                        $availables['has_all_in_one_store'] = $has_all_in_one_store;
                    }
                }*/
            }
        }

        // If all items are NOT available in pickup store
        if ( ! isset($availables['has_all_in_pickup_store'])) {
            // Collect items form pickup store
            $pickup_store = $warehouse_list->where('skladiste', '=', $this->pickup)->first();

            $this->log_items('getItemsAvailability() -> $pickup_store...', $pickup_store);

            foreach ($availables['items'] as $store_uid => $items) {
                if ($store_uid == $pickup_store['skladiste_uid']) {
                    $availables['pickup_store_items'] = $items;

                    foreach ($items as $item) {
                        if ($item['req'] > $item['qty']) {
                            for ($i = 0; $i < count($products); $i++) {
                                if ($products[$i]['artikl'] == $item['uid']) {
                                    $products[$i]['kolicina'] = $item['qty'] - $item['req'];
                                }
                            }
                        }
                    }
                }
            }

        }
        // Collect from available stores

        $this->log_items('getItemsAvailability() -> $products...', $products);
        $this->log_items('getItemsAvailability() -> $availables_#2...', $availables);
    }


    /**
     * @return $this
     */
    private function resolvePickup(string $code = null)
    {
        $shipping_code = substr($this->oc_order['shipping_code'], 0, -1);

        if ($code) {
            $shipping_code = substr($code, 0, -1);
        }

        if ($shipping_code == 'xshippingpro.xshippingpro1_') {
            $xid = substr($this->oc_order['shipping_code'], strpos($this->oc_order['shipping_code'], '_') + 1);

            foreach (agconf('luceed.pickup') as $key => $item) {
                if ($key == $xid) {
                    $this->pickup = $item;
                }
            }
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
        return Order::query()->where('order_id', $this->oc_order['order_id'])->update([
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
        $orders   = Order::select('order_id', 'luceed_uid', 'email', 'payment_code', 'shipping_code', 'order_status_id', 'order_status_changed')
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
                        'shipping'     => $order->shipping_code,
                        'email'        => $order->email
                    ];
                }
            }
        }

        if ( ! empty($this->collection)) {
            // Get the apropriate mail.
            for ($i = 0; $i < count($this->collection); $i++) {
                $this->resolvePickup($this->collection[$i]['shipping']);

                if ($this->pickup != '') {
                    foreach (agconf('mail_pickup.' . $this->collection[$i]['payment']) as $p_key => $p_item) {
                        if ($p_key) {
                            if ($this->collection[$i]['status_from'] == $p_item['from'] && $this->collection[$i]['status_to'] == $p_item['to']) {
                                $this->collection[$i]['mail'] = $p_key;
                            }
                        }
                    }
                } else {
                    foreach (agconf('mail.' . $this->collection[$i]['payment']) as $key => $item) {
                        if ($key) {
                            if ($this->collection[$i]['status_from'] == $item['from'] && $this->collection[$i]['status_to'] == $item['to']) {
                                $this->collection[$i]['mail'] = $key;
                            }
                        }
                    }
                }
            }

            Log::info($this->collection);

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
        $orders   = Order::query()->select('order_id', 'email', 'payment_code', 'order_status_id', 'order_status_changed')
                         ->where('order_status_id', '!=', 0)
                         ->where('date_added', '>=', Carbon::now()->subMonth()->toDateTimeString())
                         ->get();

        foreach ($orders as $order) {
            $status = $statuses->where('order_status_id', $order->order_status_id)->first();

            if ($order->order_id == 35647) {
                Log::info($order->toArray());
                Log::info($status->luceed_status_id);
                Log::info('168  - ' . Carbon::now()->subHour(168)->toDateTimeString());
                Log::info('72  - ' . Carbon::now()->subHour(72)->toDateTimeString());
                Log::info('48  - ' . Carbon::now()->subHour(48)->toDateTimeString());
                Log::info('24  - ' . Carbon::now()->subHour(24)->toDateTimeString());

                Log::info($order->order_status_changed > Carbon::now()->subHour(168));
                Log::info($order->order_status_changed < Carbon::now()->subHour(72));

                Log::info($order->order_status_changed > Carbon::now()->subHour(72));
                Log::info($order->order_status_changed < Carbon::now()->subHour(48));

                Log::info($order->order_status_changed > Carbon::now()->subHour(48));
                Log::info($order->order_status_changed < Carbon::now()->subHour(24));

                Log::info($order->order_status_changed > Carbon::now()->subHour(24));
                Log::info($order->order_status_changed < Carbon::now());
            }

            if ($order->order_status_changed > Carbon::now()->subHour(168)->toDateTimeString() && $order->order_status_changed < Carbon::now()->subHour(72)->toDateTimeString() && in_array($status->luceed_status_id, ['11', '09'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 72,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed > Carbon::now()->subHour(72)->toDateTimeString() && $order->order_status_changed < Carbon::now()->subHour(48)->toDateTimeString() && in_array($status->luceed_status_id, ['02', '05', '09'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 48,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed > Carbon::now()->subHour(48)->toDateTimeString() && $order->order_status_changed < Carbon::now()->subHour(24)->toDateTimeString() && in_array($status->luceed_status_id, ['12', '01', '09'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 24,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

            if ($order->order_status_changed > Carbon::now()->subHour(24)->toDateTimeString() && $order->order_status_changed < Carbon::now()->toDateTimeString() && in_array($status->luceed_status_id, ['02', '05', '12', '01'])) {
                $this->collection[] = [
                    'order_id'    => $order->order_id,
                    'longer_then' => 0,
                    'status'      => $status->luceed_status_id,
                    'payment'     => $order->payment_code,
                    'email'       => $order->email
                ];
            }

        }

        Log::info($this->collection);

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

            if ($this->pickup != '') {
                foreach (agconf('mail_pickup.' . $this->collection[$i]['payment'])[0] as $key => $items) {
                    if ($this->collection[$i]['status'] == $key) {
                        foreach ($items as $hour => $mail) {
                            if ($this->collection[$i]['longer_then'] == $hour) {
                                $this->collection[$i]['mail'] = $mail;
                            }
                        }
                    }
                }
            }
        }

        Log::info($this->collection);

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
        $response = array_values($this->getRegularProducts());
        // Apply shipping dummy product.
        if ($this->pickup == '') {
            $response[] = $this->getShippingProduct();
        }

        return $response;
    }


    /**
     * @return array
     */
    private function getRegularProducts(): array
    {
        $response       = [];
        $order_products = OrderProduct::where('order_id', $this->oc_order['order_id'])->get();

        if ($order_products->count()) {
            foreach ($order_products as $order_product) {
                $price = $order_product->price;

                if ($this->installments > 12) {
                    $price = $order_product->price * 1.07;
                }

                $response[$order_product->model] = [
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
     * @return int|string
     */
    private function getIznos()
    {
        if ($this->hasOIB()) {
            return $this->getSubTotal();
        }

        return number_format($this->oc_order['total'], 2, '.', '');
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

        return $json->result[0]->nalozi_prodaje ?: [];
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


    /**
     * @param $string
     * @param $data
     *
     * @return int|mixed
     */
    private function log_items($string, $data = null)
    {
        if (is_string($data) || is_int($data) || is_float($data)) {
            return Log::store($string . ' => ' . $data, 'proccess_order_' . $this->oc_order['order_id'] . '_items');
        }

        Log::store($string, 'proccess_order_' . $this->oc_order['order_id'] . '_items');

        if ($data) {
            Log::store($data, 'proccess_order_' . $this->oc_order['order_id'] . '_items');
        }

        return 1;
    }

}