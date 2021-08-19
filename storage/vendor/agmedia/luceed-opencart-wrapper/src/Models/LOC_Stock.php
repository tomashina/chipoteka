<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Product\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Stock
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var
     */
    public $dobavljaci = null;

    /**
     * @var
     */
    public $skladista = null;

    /**
     * @var null
     */
    public $stock = null;

    /**
     * @var null
     */
    private $skladista_sorted = null;

    private $dobavljaci_sorted = null;

    private $query = '';

    /**
     * LOC_Product constructor.
     */
    public function __construct()
    {
        $this->db = new Database(DB_DATABASE);
    }


    /**
     * @return $this
     */
    public function sort()
    {
        if ( ! $this->stock) {
            $this->stock = collect();
        }

        if ($this->skladista && ! $this->skladista_sorted) {
            foreach ($this->skladista->groupBy('artikl_uid') as $key => $item) {
                $this->stock->push([
                    'artikl_uid' => $key,
                    'stanje_kol' => $item->sum('stanje_kol')
                ]);
            }

            $this->skladista_sorted = true;
        }

        if ($this->dobavljaci && ! $this->dobavljaci_sorted) {
            foreach ($this->dobavljaci->where('main', 'D')->groupBy('sifra_artikla') as $key => $item) {
                $this->stock->push([
                    'artikl_uid' => $key,
                    'stanje_kol' => $item->sum('dobavljac_stanje')
                ]);
            }

            $this->dobavljaci_sorted = true;
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function createQuery()
    {
        if ($this->skladista_sorted && $this->dobavljaci_sorted && $this->stock) {
            foreach ($this->stock as $item) {
                $this->query .= '("' . $item['artikl_uid'] . '", ' . $item['stanje_kol'] . ', 0),';
            }
        }

        return $this;
    }


    /**
     * @return int
     */
    public function update(): int
    {
        if ($this->query != '') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->query, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity");

            return 1;
        }

        return 0;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setDobavljaci($dobavljaci)
    {
        $json = json_decode($dobavljaci);

        $this->dobavljaci = collect($json->result[0]->artikli_dobavljaci);

        return $this;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setSkladista($skladista)
    {
        $json = json_decode($skladista);

        $this->skladista = collect($json->result[0]->stanje);

        return $this;
    }

}