<?php

namespace Agmedia\Luceed\Models;

use Agmedia\Models\Product\Product;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class LuceedProductForRevision extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_luceed_revision';

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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'luceed_uid', 'uid');
    }

}
