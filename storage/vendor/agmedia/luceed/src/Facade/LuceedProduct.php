<?php


namespace Agmedia\Luceed\Facade;


use Agmedia\Luceed\Luceed;

class LuceedProduct extends Facade
{
    
    /**
     * @return mixed
     */
    public static function all()
    {
        $luceed = new Luceed();
        
        return $luceed->getProductsList();
    }
    
    
    /**
     * @param array $chunks
     *
     * @return mixed
     */
    public static function chunk(array $chunks)
    {
        $luceed = new Luceed();
        
        return $luceed->getProductsList($chunks);
    }
    
    
    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getById(string $id)
    {
        $luceed = new Luceed();
        
        return $luceed->getProduct($id, 'sifra');
    }
    
    
    /**
     * @param string $barcode
     *
     * @return mixed
     */
    public static function getByBarcode(string $barcode)
    {
        $luceed = new Luceed();
        
        return $luceed->getProduct($barcode, 'barcode');
    }
    
    
    /**
     * @param $group_id
     *
     * @return mixed
     */
    public static function getByGroup($group_id)
    {
        $luceed = new Luceed();
        
        return $luceed->getGroupProducts($group_id);
    }
    
    
    /**
     * @param $manufacturer_id
     *
     * @return mixed
     */
    public static function getByManufacturer($manufacturer_id)
    {
        $luceed = new Luceed();
        
        return $luceed->getManufacturerProducts($manufacturer_id);
    }
    
    
    /**
     * @return mixed
     */
    public static function getActions()
    {
        $luceed = new Luceed();
        
        return $luceed->getProductsActions();
    }
    
    
    /**
     * @param null|string|array $units
     *
     * @return mixed
     */
    public static function getAllStock($units = null)
    {
        $luceed = new Luceed();
        
        return $luceed->getStock($units);
    }
    
    
    /**
     * @param string $id
     * @param null|string|array $units
     *
     * @return mixed
     */
    public static function getStockById(string $id, $units = null)
    {
        $luceed = new Luceed();
        
        return $luceed->getProductStock($id, $units);
    }
    
    
    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getImage(string $uid)
    {
        $luceed = new Luceed();
        
        return $luceed->getProductImage($uid);
    }
    
    
    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/
    // HELPERS
    
    /**
     * @param $group_id
     *
     * @return mixed
     */
    public static function getByCategory($group_id)
    {
        return self::getByGroup($group_id);
    }
}