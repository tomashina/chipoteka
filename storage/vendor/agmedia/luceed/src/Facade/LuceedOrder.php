<?php


namespace Agmedia\Luceed\Facade;


class LuceedOrder extends Facade
{
    
    /**
     * @param array $order
     *
     * @return mixed
     */
    public static function create(array $order)
    {
        return self::$luceed->createOrder($order);
    }
    
    
    /**
     * @param string $order_uid
     *
     * @return mixed
     */
    public static function getByUid(string $order_uid)
    {
        return self::$luceed->getOrder($order_uid);
    }
    
    
    /**
     * @param array $status
     *
     * @return mixed
     */
    public static function getStatus(array $status)
    {
        return self::$luceed->getOrdersByStatus($status);
    }
}