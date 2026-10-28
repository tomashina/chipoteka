<?php


namespace Agmedia\Luceed\Models;


use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class LuceedProduct extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'product_luceed';
    
    /**
     * @var string
     */
    protected $primaryKey = 'uid';

    // Luceed identifiers contain the company suffix, e.g. 184395-1063.
    // They are strings, not auto-incrementing integer keys.
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;
    
    
    /**
     * @param      $query
     * @param null $products - Should be array or integer.
     *
     * @return mixed
     */
    public function scopeDeactivate($query, $products = null)
    {
        if ( ! $products) {
            return $query->update(['status' => 0]);
        }
        
        if (is_array($products) || is_a($products, Collection::class)) {
            return $query->whereIn('product_id', $products)->update(['status' => 0]);
        }
        
        return $query->where('product_id', $products)->update(['status' => 0]);
    }
    
}
