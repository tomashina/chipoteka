<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Address;
use Agmedia\Models\Customer\Customer;
use Agmedia\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Customer
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Customer
{

    /**
     * @var array|null
     */
    public $customer;

    /**
     * @var array
     */
    private $order_customer;

    /**
     * @var array|null
     */
    private $alter_customer = null;

    /**
     * @var Luceed
     */
    private $service;

    /**
     * @var bool
     */
    private $should_update;


    /**
     * LOC_Customer constructor.
     *
     * @param array|null $customer
     */
    public function __construct(array $customer = null)
    {
        if ($customer) {
            $this->customer = $this->create($customer);
            $this->order_customer = $customer;
        }
    }


    /**
     * @return int
     */
    public function initialImport()
    {
        $count       = 0;
        $store_id    = agconf('import.default_store_id');
        $language_id = agconf('import.default_language');

        $customers = Customer::pluck('email');

        $list = $this->loadData()
                     ->where('subject_type', 'F')
                     ->where('firstname', '!=', '')
            /*->where('firstname', '!=', 0)*/
                     ->where('lastname', '!=', '')
                     ->where('email', '!=', '')
                     ->whereNotIn('email', $customers)
                     ->unique('email');

        if ( ! empty($list)) {
            foreach ($list as $item) {
                $customer_id = Customer::insertGetId([
                    'luceed_uid'        => '',
                    'customer_group_id' => 1,
                    'store_id'          => $store_id,
                    'language_id'       => $language_id,
                    'firstname'         => substr($item['firstname'], 0, 32),
                    'lastname'          => substr($item['lastname'], 0, 32),
                    'email'             => $item['email'] ?: '0',
                    'telephone'         => (isset($item['phone']) && $item['phone']) ? substr(str_replace(' ', '', $item['phone']), 0, 32) : '0',
                    'fax'               => '0',
                    'password'          => strtoupper(Str::random(40)),
                    'salt'              => '0',
                    'cart'              => null,
                    'wishlist'          => null,
                    'address_id'        => 0,
                    'custom_field'      => '{"2":"' . $item['company'] . '","1":"' . $item['oib'] . '"}',
                    'ip'                => '',
                    'status'            => 1,
                    'safe'              => '0',
                    'token'             => '',
                    'code'              => '',
                    'date_added'        => Carbon::now(),
                ]);

                if ($customer_id) {
                    $address_id = Address::insertGetId([
                        'customer_id'  => $customer_id,
                        'firstname'    => substr($item['firstname'], 0, 32),
                        'lastname'     => substr($item['lastname'], 0, 32),
                        'company'      => $item['company'] ? substr($item['company'], 0, 40) : '0',
                        'address_1'    => $item['address'] ?: '0',
                        'address_2'    => '',
                        'city'         => $item['city'] ?: '0',
                        'postcode'     => (isset($item['zip']) && $item['zip']) ? substr($item['zip'], 0, 5) : '0',
                        'country_id'   => 53,
                        'zone_id'      => 0,
                        'custom_field' => '',
                    ]);

                    if ($address_id) {
                        Customer::where('customer_id', $customer_id)->update([
                            'address_id' => $address_id
                        ]);
                    }

                    $count++;
                }
            }
        }

        return $count;
    }


    /**
     * @return array
     */
    public function getUid(): array
    {
        return [
            'main' => $this->customer['uid'] ?: '',
            'alter' => $this->alter_customer['uid'] ?: '',
        ];
    }


    /**
     * Check by Email if the customer exist
     * in the Luceed database.
     * Set uid data if exist.
     *
     * @return bool
     */
    public function exist(): bool
    {
        Log::store('$exist_before');

        $this->service = new Luceed();

        Log::store('$exist_before');

        // Set the response from Luceed service.
        $exist = $this->setResponseData(
            $this->service->getCustomerByEmail($this->customer['e_mail'])
        );

        Log::store('$exist_after');
        Log::store($this->order_customer);

        if ( ! empty($exist)) {
            foreach ($exist as $l_customer) {
                Log::store($this->order_customer['shipping_address']);
                Log::store($l_customer->adresa);
                Log::store($l_customer->grupacija);
                // Kupac
                if ( ! $this->diffAddress() && $this->order_customer['shipping_address'] == $l_customer->adresa && ! $l_customer->grupacija) {
                    Customer::where('customer_id', $this->customer['id'])->update([
                        'luceed_uid' => $l_customer->partner_uid
                    ]);

                    $this->customer['uid'] = $l_customer->partner_uid;
                }

                // Korisnik
                if ($this->diffAddress() && $l_customer->adresa == $this->order_customer['shipping_address']) {
                    $l_customer->grupacija = $this->customer['uid'];

                    $this->alter_customer = $this->populateCustomerForLuceed(collect($l_customer), true);

                    if ( ! $l_customer->grupacija) {
                        // updejtaj alter partnera i grupaciju.
                        json_decode(
                            $this->service->updateCustomer(['partner' => [$this->alter_customer]])
                        );
                    }
                }

                // Neregani kupac
                if ( ! $this->customer['uid']) {
                    $this->customer['uid'] = $l_customer->partner_uid;
                }
            }

            Log::store('First ::::: customer i alter...');
            Log::store($this->customer);
            Log::store($this->alter_customer);

            // Ima kupca, ali nema korisnika
            if ( ! empty($this->customer['uid']) && ! $this->alter_customer) {
                // snimi korisnika u luceed
                $this->alter_customer = [
                    'id'                  => 0,
                    'uid'                 => null,
                    'parent__partner_uid' => $this->customer['uid'],
                    'naziv'               => $this->order_customer['shipping_fname'] . ' ' . $this->order_customer['shipping_lname'],
                    'ime'                 => $this->order_customer['shipping_fname'],
                    'prezime'             => $this->order_customer['shipping_lname'],
                    'enabled'             => 'D',
                    'tip_komitenta'       => 'F',
                    'adresa'              => $this->order_customer['shipping_address'],
                    'telefon'             => $this->customer['telefon'],
                    'e_mail'              => $this->customer['e_mail'],
                    'postanski_broj'      => $this->order_customer['shipping_postcode']
                ];

                $response = json_decode(
                    $this->service->createCustomer(['partner' => [$this->alter_customer]])
                );

                // složi podatke za order
                if (isset($response->result[0])) {
                    $this->alter_customer['uid'] = $response->result[0];
                }
            }

            Log::store('Second ::::: customer i alter...');
            Log::store($this->customer);
            Log::store($this->alter_customer);

            return true;
        }

        return false;
    }


    /**
     * Store the customer in Luceed database.
     * Set uid data if response ok.
     *
     * @return $this
     */
    public function store()
    {
        $response = json_decode(
            $this->service->createCustomer(['partner' => [$this->customer]])
        );

        if (isset($response->result[0])) {
            Customer::where('customer_id', $this->customer['id'])->update([
                'luceed_uid' => $response->result[0]
            ]);

            $this->customer['uid'] = $response->result[0];
        }

        return $this;
    }


    /**
     * @param array|null $customer
     *
     * @return array
     */
    private function create(array $customer = null): array
    {
        return $this->populateCustomerForLuceed(collect($customer));
    }


    /**
     * @param Collection $collection
     * @param false      $luceed_data
     *
     * @return array
     */
    private function populateCustomerForLuceed(Collection $collection, $luceed_data = false): array
    {
        if ( ! isset($collection->uid)) {
            $collection->put('uid', null);
        }

        if ( ! isset($collection->grupacija)) {
            $collection->put('grupacija', null);
        }

        if ($luceed_data) {
            return [
                'id'                  => 0,
                'uid'                 => $collection->partner_uid,
                'parent__partner_uid' => $collection->grupacija,
                'naziv'               => $collection->ime . ' ' . $collection->prezime,
                'ime'                 => $collection->ime,
                'prezime'             => $collection->prezime,
                'enabled'             => 'D',
                'tip_komitenta'       => 'F',
                'adresa'              => $collection->adresa,
                'telefon'             => $collection->telefon,
                'e_mail'              => $this->customer['e_mail'],
                'postanski_broj'      => $collection->postanski_broj
            ];
        }

        return [
            'id'                  => $collection['customer_id'],
            'uid'                 => $this->setUid($collection['customer_id'], true),
            'parent__partner_uid' => $collection['grupacija'],
            'naziv'               => $collection['fname'] . ' ' . $collection['lname'],
            'ime'                 => $collection['fname'],
            'prezime'             => $collection['lname'],
            'enabled'             => 'D',
            'tip_komitenta'       => 'F',
            'adresa'              => $collection['address'],
            'telefon'             => ($collection['phone'] != '') ? $collection['phone'] : '000',
            'e_mail'              => $collection['email'],
            'postanski_broj'      => $collection['zip']
        ];
    }


    /**
     * Set customer uid data.
     * Param can be luceed_uid or oc_customer_id.
     * If it's customer_id 2 param should be true.
     *
     * @param       $uid
     * @param false $from_ocdb
     */
    private function setUid($uid, $from_ocdb = false): void
    {
        if ($uid && $from_ocdb) {
            $customer = Customer::where('customer_id', $uid)->first();

            if ($customer && ! empty($customer->luceed_uid)) {
                $this->customer['uid'] = $customer->luceed_uid;
            } else {
                $this->customer['uid'] = null;
            }
        } else {
            $this->customer['uid'] = $uid ?: null;
        }
    }


    /**
     * @return bool
     */
    private function diffAddress(): bool
    {
        if ($this->order_customer['address'] == $this->order_customer['shipping_address']) {
            return false;
        }

        return true;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $response
     *
     * @return array
     */
    private function setResponseData($response): array
    {
        $data = json_decode($response);

        if (isset($data->result[0])) {
            return $data->result[0]->partner;
        }

        return [];
    }


    /**
     * @return array|Collection
     */
    private function loadList()
    {
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/users.json'), true);

        if ($file) {
            $file = $file['users'];

            return collect($file);
        }

        return [];
    }


    /**
     * @return array|Collection
     */
    private function loadData()
    {
        $file     = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/user_data.json'), true);
        $response = [];

        if ($file) {
            $file = $file['user_data'];

            foreach ($file as $item) {
                $response[] = json_decode($item['data'], true);
            }

            return collect($response);
        }

        return [];
    }

}