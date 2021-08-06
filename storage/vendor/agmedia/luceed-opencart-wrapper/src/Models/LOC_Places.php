<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Places
{

    /**
     * @var array
     */
    public $places;

    /**
     * @var array
     */
    private $list = [];


    /**
     * LOC_Places constructor.
     *
     * @param null $places
     */
    public function __construct($places)
    {
        $this->list = $this->setPlaces($places);
    }


    /**
     * @param string $state
     *
     * @return mixed
     */
    public function getList(string $state = 'HR')
    {
        $this->places = collect($this->list)->where('drzava', '==', $state);

        return $this;
    }


    /**
     * @param string $request
     * @param string $target = naziv | mjesto(zip)
     *
     * @return Collection
     */
    public function find(string $request = '', string $target = 'naziv')
    {
        if ($request != '') {
            $this->places = $this->places->filter(function ($item) use ($request, $target) {
                return stripos($item->{$target}, $request) !== false;
            });
        }

        return $this;
    }


    /**
     * @param int $count
     *
     * @return $this
     */
    public function limit(int $count = 0)
    {
        if ($count) {
            $this->places = $this->places->take($count);
        }

        return $this;
    }


    /**
     * @param $places
     *
     * @return array
     */
    private function setPlaces($places): array
    {
        $cats = json_decode($places);

        return $cats->result[0]->mjesta;
    }
}