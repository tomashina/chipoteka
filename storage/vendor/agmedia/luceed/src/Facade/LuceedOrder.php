<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedOrder extends Facade
{

    /**
     * @param string      $statuses
     * @param string|null $date
     * @param bool        $b2b
     *
     * @return false|mixed
     */
    public static function get(string $statuses, string $date = null, bool $b2b = false)
    {
        $luceed = new Luceed();

        if ($date) {
            $statuses .= '/' . $date;
        }

        return $luceed->getOrders($statuses, $b2b);
    }


    /**
     * @param string $document_uid
     * @param bool   $b2b
     *
     * @return false|mixed
     */
    public static function document(string $document_uid, bool $b2b = false)
    {
        $luceed = new Luceed();

        return $luceed->getOrderDocument($document_uid, $b2b);
    }

}