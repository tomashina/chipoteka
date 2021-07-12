<?php


namespace Agmedia\LuceedOpencartWrapper\Models;


use Agmedia\Helpers\Log;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Manufacturer\ManufacturerToStore;
use Illuminate\Support\Collection;

/**
 * Class LOC_Manufacturer
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Manufacturer
{

    /**
     * @var array|null
     */
    private $manufacturers;

    /**
     * @var null
     */
    private $manufacturers_to_add = null;
    
    
    /**
     * LOC_Manufacturer constructor.
     *
     * @param null $manufacturers
     */
    public function __construct($manufacturers = null)
    {
        $this->manufacturers = $manufacturers ? $this->setManufacturers($manufacturers) : null;
    }
    
    
    /**
     * @return Collection|null
     */
    public function getManufacturers(): ?Collection
    {
        return $this->manufacturers ? collect($this->manufacturers) : [];
    }
    
    
    /**
     * @return Collection|null
     */
    public function getManufacturersToAdd(): ?Collection
    {
        return $this->manufacturers_to_add ? collect($this->manufacturers_to_add) : [];
    }
    
    
    /**
     * @param $products
     *
     * @return $this
     */
    public function getFromProducts($products)
    {
        $list = json_decode($products)->result[0]->artikli;
        
        $this->manufacturers = collect($list)->unique('robna_marka')->toArray();
        
        return $this;
    }
    
    
    /**
     * @return $this
     */
    public function checkDiff()
    {
        if ($this->manufacturers) {
            $existing = Manufacturer::pluck('luceed_uid');
            $list_diff = $this->getManufacturers()
                ->where('robna_marka', '!=', '')
                ->where('naziv', '!=', '')
                ->pluck('robna_marka')
                ->diff($existing)
                ->flatten();
    
            $this->manufacturers_to_add = $this->getManufacturers()->whereIn('robna_marka', $list_diff);
        }
        
        return $this;
    }
    
    
    /**
     * @return int
     */
    public function import()
    {
        $count = 0;
        
        foreach ($this->getManufacturersToAdd() as $manufacturer) {
            $this->save($manufacturer);
            $count++;
        }
        
        return $count;
    }
    
    
    /**
     * @param $manufacturer
     *
     * @return mixed
     */
    private function save($manufacturer)
    {
        $id = Manufacturer::insertGetId([
            'luceed_uid' => $manufacturer->robna_marka,
            'name' => $manufacturer->naziv,
            'image' => '',
            'sort_order' => 0
        ]);
        
        ManufacturerToStore::insert([
            'manufacturer_id' => $id,
            'store_id' => 0
        ]);
        
        return $id;
    }
    
    
    /**
     * @param $categories
     *
     * @return array
     */
    public function setManufacturers($manufacturers): array
    {
        $man = json_decode($manufacturers);
        
        return $man->result[0]->robne_marke;
    }
}