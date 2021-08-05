<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedPlaces
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getPlaces();
    }
    
    
    /**
     * @param string $uid
     *
     * @return mixed
     */
    public static function getById(string $id)
    {
        $luceed = new Luceed();
    
        return $luceed->getPlace($id);
    }
}