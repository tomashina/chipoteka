<?php

namespace Agmedia\Luceed\Models;

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
    protected $primaryKey = 'product_id';

    /**
     * @var bool
     */
    public $timestamps = false;

}