<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Luceed;
use Agmedia\Models\Address;
use Agmedia\Models\Customer\Customer;
use Agmedia\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        $list = $this->loadList();
        $data = $this->loadData();
        $count = 0;

        //Log::store($data->where('subject_type', null)->all());

        if ( ! empty($list)) {
            foreach ($list as $item) {
                if ( ! $item['blocked'] && $item['email'] != '') {
                    $new_user = $data->where('email', $item['email'])->first();
                    $customer = Customer::where('email', $item['email'])->first();

                    if ( ! $customer && $new_user) {
                        $user = User::where('email', $item['email'])->first();

                        if ( ! $user) {
                            User::insertGetId([
                                'user_group_id' => 1,
                                'username' => $item['username'] ? substr($item['username'], 0, 20) : '0',
                                'password' => $item['password'] ? substr($item['password'], 0, 40) : '0',
                                'salt' => '0',
                                'firstname' => $new_user['firstname'] ?: '0',
                                'lastname' => $new_user['lastname'] ?: '0',
                                'email' => $item['email'] ?: '0',
                                'image' => 'catalog/favikon-chipoteka.png',
                                'code' => '',
                                'ip' => '',
                                'status' => 1,
                                'date_added' => Carbon::now(),
                            ]);
                        }

                        $customer_id = Customer::insertGetId([
                            'luceed_uid' => '',
                            'customer_group_id' => 1,
                            'store_id' => agconf('import.default_store_id'),
                            'language_id' => agconf('import.default_language'),
                            'firstname' => $new_user['firstname'] ?: '0',
                            'lastname' => $new_user['lastname'] ?: '0',
                            'email' => $item['email'] ?: '0',
                            'telephone' => (isset($new_user['phone']) && $new_user['phone']) ? substr($new_user['phone'], 0, 32) : '0',
                            'fax' => '0',
                            'password' => $item['password'] ? substr($item['password'], 0, 40) : '0',
                            'salt' => '0',
                            'cart' => null,
                            'wishlist' => null,
                            'address_id' => 0,
                            'custom_field' => '{"2":"' . $new_user['company'] . '","1":"' . $new_user['oib'] . '"}',
                            'ip' => '',
                            'status' => 1,
                            'safe' => '0',
                            'token' => '',
                            'code' => '',
                            'date_added' => Carbon::now(),
                        ]);

                        if ($customer_id) {
                            $address_id = Address::insertGetId([
                                'customer_id' => $customer_id,
                                'firstname' => $new_user['firstname'] ?: '0',
                                'lastname' => $new_user['lastname'] ?: '0',
                                'company' => $new_user['company'] ? substr($item['password'], 0, 40) : '0',
                                'address_1' => $new_user['address'] ?: '0',
                                'address_2' => '',
                                'city' => $new_user['city'] ?: '0',
                                'postcode' => (isset($new_user['zip']) && $new_user['zip']) ? substr($item['zip'], 0, 10) : '0',
                                'country_id' => 53,
                                'zone_id' => 0,
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
            }
        }
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
            $this->customer['uid'] = $uid ? $uid : null;
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
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/users.json'),TRUE);

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
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/user_data.json'),TRUE);
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