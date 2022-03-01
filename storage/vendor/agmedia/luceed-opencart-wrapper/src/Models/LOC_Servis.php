<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Servis
{

    /**
     * @var string
     */
    private $sid = '';

    /**
     * @var array
     */
    private $response;


    /**
     * LOC_Category constructor.
     *
     * @param null $warehouses
     */
    public function __construct($response = null)
    {
        if ($response) {
            $this->response = $this->setResponse($response);
        }
    }


    /**
     * @return Collection
     */
    public function getResponse(): Collection
    {
        return collect($this->response)->first();
    }


    /**
     * @param $response
     *
     * @return array
     */
    private function setResponse($response): array
    {
        $json = json_decode($response);

        return $json->result[0]->radni_nalozi;
    }
}