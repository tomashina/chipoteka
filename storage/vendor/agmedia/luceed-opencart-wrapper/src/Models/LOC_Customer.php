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
     * @return string
     */
    public function getUid(): string
    {
        return $this->customer['uid'] ?: '';
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
        // If uid exist, customer exist.
        // Customer uid is set on class construct
        // if it exists in oc_db.
        if ($this->customer['uid']) {
            return true;
        }

        $this->service = new Luceed();
        // Set the response from Luceed service.
        $exist = $this->setResponseData(
            $this->service->getCustomerByEmail($this->customer['e_mail'])
        );

        Log::store('Customer exist();', 'proccess_order');
        Log::store($exist, 'proccess_order');

        if ( ! empty($exist)) {
            if ( ! $this->customer['uid']) {
                Customer::where('customer_id', $this->customer['id'])->update([
                    'luceed_uid' => $exist[0]->partner_uid
                ]);
            }
            // If customer exist set uid data.
            $this->customer['uid'] = $exist[0]->partner_uid;

            if ($this->should_update) {
                $this->customer['partner_uid'] = $exist[0]->partner_uid;

                $response = json_decode(
                    $this->service->updateCustomer(['partner' => [$this->customer]])
                );

                Log::store('Customer update();', 'proccess_order');
                Log::store($response, 'proccess_order');

            }

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

        Log::store('Customer store();', 'proccess_order');
        Log::store($response, 'proccess_order');

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
        $this->should_update = $customer['should_update'];

        return [
            'id'             => $customer['customer_id'],
            'uid'            => $this->setUid($customer['customer_id'], true),
            'naziv'          => $customer['fname'] . ' ' . $customer['lname'],
            'ime'            => $customer['fname'],
            'prezime'        => $customer['lname'],
            'enabled'        => 'D',
            'tip_komitenta'  => 'F',
            'adresa'         => $customer['address'],
            'telefon'        => ($customer['phone'] != '') ? $customer['phone'] : '000',
            'e_mail'         => $customer['email'],
            'postanski_broj' => $customer['zip']
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
    public function setUid($uid, $from_ocdb = false): void
    {
        if ($uid && $from_ocdb) {
            $customer = Customer::where('customer_id', $uid)->first();

            if ($customer) {
                $this->customer['uid'] = $customer->luceed_uid;
            }
        } else {
            $this->customer['uid'] = $uid ?: null;
        }
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
        Log::store('Customer setResponseData($response);', 'proccess_order');
        Log::store($response, 'proccess_order');

        $data = json_decode($response);

        if (isset($data->result[0])) {
            return $data->result[0]->partner;
        }

        return false;
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