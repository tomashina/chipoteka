<?php

namespace Agmedia\Luceed;

use Agmedia\Luceed\Connection\LuceedService;

/**
 * Class Luceed
 * @package Agmedia\Luceed
 */
class Luceed
{

    /**
     * @var LuceedService
     */
    private $service;

    /**
     * @var string
     */
    private $env = 'local';

    /**
     * @var array
     */
    private $end_points = [];


    /**
     * Luceed constructor.
     */
    public function __construct()
    {
        $this->service    = new LuceedService();
        $this->env        = agconf('env');
        $this->end_points = LuceedEndPoints::get($this->env);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // GROUPS / CATEGORIES

    /**
     * @return mixed
     */
    public function getGroupList()
    {
        return $this->service->get($this->end_points['group_list']);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // MANUFACTURERS

    /**
     * @return mixed
     */
    public function getManufacturer(string $manufacturer_uid)
    {
        return $this->service->get($this->end_points['manufacturer_uid'] . $manufacturer_uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // PRODUCTS

    /**
     * @param array|null $query
     *
     * @return mixed
     */
    public function getProductsList(array $query = null)
    {
        return $this->service->get($this->end_points['product_list'] . ($query ? $query : ''));
    }


    /**
     * @param array|null $query
     *
     * @return mixed
     */
    public function getProductsActions(array $query = null)
    {
        return $this->service->get($this->end_points['product_actions'] . ($query ? $query : ''));
    }


    /**
     * @param string $uid
     *
     * @return mixed
     */
    public function getProductImage($uid)
    {
        return $this->service->get($this->end_points['product_image'], $uid);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // STOCK

    /**
     * @param string|array $warehouse_uid
     *
     * @return false|mixed
     */
    public function getStock($warehouse_uid)
    {
        return $this->service->get($this->end_points['stock_get'], $warehouse_uid);
    }


    /**
     * @param $warehouse_uid
     * @param $article_uid
     *
     * @return false|mixed
     */
    public function getIndividualStock($article_uid, $warehouse_uid)
    {
        $option = $article_uid . '/' . $warehouse_uid;

        return $this->service->get($this->end_points['ind_stock_get'], $option);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // CUSTOMERS

    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function createCustomer(array $customer)
    {
        return $this->service->post($this->end_points['customer_create'], $customer);
    }


    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function updateCustomer(array $customer)
    {
        return $this->service->put($this->end_points['customer_create'], $customer);
    }


    /**
     * @param array $customer
     *
     * @return mixed
     */
    public function getCustomerByEmail(string $email)
    {
        return $this->service->get($this->end_points['customer_email'], $email);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // ORDERS

    /**
     * @param array $order
     *
     * @return mixed
     */
    public function createOrder(array $order)
    {
        return $this->service->post($this->end_points['order_create'], $order);
    }


    /**
     * @param string $uid
     *
     * @return false|mixed
     */
    public function orderWrit(string $uid)
    {
        return $this->service->get($this->end_points['raspis'], $uid);
    }

}