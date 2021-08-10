<?php


namespace Agmedia\Models\Manufacturer;


use Agmedia\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'manufacturer';

    /**
     * @var string
     */
    protected $primaryKey = 'manufacturer_id';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'manufacturer_id', 'manufacturer_id');
    }
}