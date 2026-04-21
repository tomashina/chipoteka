<?php

namespace Agmedia\LuceedOpencartWrapper\Models;
use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Warehouse
{


    /**
     * @var Database
     */
    private $db;


    /**
     * @var array
     */
    private $list = [];

    /**
     * Varijabla da bi odvojili default skladišta od
     * zbrajanja default skldišta na kojima se dopušta kupnja.
     *
     * @var array
     */
    private $strict_default_houses = ['101'];

    /**
     * @var array
     */
    private $warehouses;


    /**
     * LOC_Category constructor.
     *
     * @param null $warehouses
     */
    public function __construct($warehouses = null)
    {
        if ($warehouses) {
            $this->list = $this->setWarehouses($warehouses);
        } else {
            $this->list = $this->load();
        }
    }


    /**
     * @return Collection
     */
    public function getList(): Collection
    {
        return collect($this->list)->where('skladiste', '!=', '')
                                   ->where('naziv', '!=', '');
    }


    /**
     * @return Collection
     */
    public function getWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.included'));
    }


    /**
     * @return Collection
     */
    public function getDefaultWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.default'));
    }


    /**
     * @return Collection
     */
    public function getAvailabilityViewWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.availability_view'));
    }


    /**
     * @param $product
     *
     * @return Collection
     */
    public function getAvailabilityForProduct(string $product): Collection
    {
        $response = collect();
        $houses = $this->getAvailabilityViewWarehouses();
        $units = $this->getUnitsQuery($houses);
        $houses_default = $this->getDefaultWarehouses();
        $units_default = $this->getUnitsQuery($houses_default);
        $houses_stores = $this->getList()->whereIn('skladiste', agconf('import.warehouse.stores'));

        $availables_response = LuceedProduct::stock($units, $product);
        $defaults_response = LuceedProduct::stock($units_default, $product);
        $supplier_response = LuceedProduct::getSuplierStock($product);

        if (
            ! $this->hasValidAvailabilityPayload($availables_response)
            && ! $this->hasValidAvailabilityPayload($defaults_response)
            && ! $this->hasValidSupplierPayload($supplier_response)
        ) {
            throw new \RuntimeException('Luceed availability response is missing or invalid.');
        }

        $availables = collect($this->setAvailables($availables_response));

        $defaults = collect($this->setAvailables($defaults_response));

        $suplier = collect($this->setSuplierStock($supplier_response))->where('main', 'D')->first();

        $dobavljac_stanje = 0;

        if (is_object($suplier) && isset($suplier->dobavljac_stanje)) {
            $dobavljac_stanje = max((int) $suplier->dobavljac_stanje, 0);
        }

        // AVAILABILITY VIEW
        foreach ($houses as $house) {
            $has = $availables->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty = $has->raspolozivo_kol;

                if ($qty < 0) {
                    $qty = 0;
                }

                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => $qty
                ]);

            } else {
                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => 0
                ]);
            }
        }

        $qty_default = 0;
        // DEFAULT WAREHOUSE COUNT
        foreach ($houses_default as $house) {
            if (in_array($house['skladiste'], $this->strict_default_houses)) {
                $has = $defaults->where('skladiste_uid', $house['skladiste_uid'])->first();

                if ($has) {
                    $qty_default += max($has->raspolozivo_kol, 0);
                }
            }
        }

        if ($qty_default < 0) {
            $qty_default = 0;
        }

        $qty_stores = 0;
        // STORES WAREHOUSE COUNT
        // Use the same availability payload that renders the store list so the
        // status banner stays consistent with the visible per-store quantities.
        foreach ($houses_stores as $house) {
            $has = $availables->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has && $has->raspolozivo_kol >= 0) {
                $qty_stores += $has->raspolozivo_kol;
            }
        }

        if ($qty_stores < 0) {
            $qty_stores = 0;
        }

        // provjeri trgovine bez dubrovnika
        //$qty_stores_without_dubrovnik = 0;
        // STORES WAREHOUSE COUNT
        /*foreach ($houses_stores as $house) {
            if ($house['skladiste'] != '012') {
                $has = $stores->where('skladiste_uid', $house['skladiste_uid'])->first();

                if ($has) {
                    $qty_stores_without_dubrovnik += $has->raspolozivo_kol;
                }
            }
        }

        if ($qty_stores_without_dubrovnik < 0) {
            $qty_stores_without_dubrovnik = 0;
        }*/

        if ( ! $dobavljac_stanje && $qty_stores) {
            $dobavljac_stanje = $qty_stores;
        }

        $title = '';
        $btn = '';
        $button = '';
        $date = '';

        if ($qty_default) {
            $title = 'success';
            $date = Carbon::now()->addWeekdays(1);
            $btn = 'DOSTUPNO ODMAH';
            //$date = ($date->diff(Carbon::now())->days < 1) ? 'Šaljemo sutra' : 'Šaljemo do ' . $date->format('d.m.Y');

            $date = 'Rok isporuke 2-3 radna dana';
        }

        if ( ! $qty_default && ! $dobavljac_stanje && $qty_stores) {
            $title = 'secondary';
            $btn = 'NEDOSTUPNO NA WEBU';
            $button = 'Nedostupno online';
            $date = 0;
        }

        if ( ! $qty_default && ($dobavljac_stanje/* || $qty_stores_without_dubrovnik*/)) {
            $title = 'warning';
            $btn = 'DOSTUPNO NA IZDVOJENOM SKLADIŠTU';
            $button = 'Stavi u košaricu';
           // $date = 'Šaljemo do ' . Carbon::now()->addWeekdays(5)->format('d.m.Y');

            $date = 'Rok isporuke 5-7 radnih dana ';
        }

        if ( ! $qty_default && ! $dobavljac_stanje && ! $qty_stores) {
            $title = 'secondary';
            $btn = 'PROIZVOD NEDOSTUPAN';
            $button = 'Nedostupno';
            $date = 0;
        }

        $response->push([
            'title' => 'Web',
            'address' => '',
            'qty'   => $qty_default
        ]);

        $response->push([
            'title' => 'Dobavljač',
            'address' => '',
            'qty'   => $dobavljac_stanje
        ]);



        $response->push([
            'title' => 'Btn',
            'btn' => $title,
            'button' => $button,
            'address' => $btn,
            'qty'   => $date
        ]);

        $this->db = new Database(DB_DATABASE);

       // $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = '".$qty_default."' + '". $dobavljac_stanje."' WHERE sku = '".$product."'  ");

        return $response;
    }


    public function getAvailabilityFallbackResponse(
        string $title = 'Dostupnost po poslovnicama privremeno nije dostupna.',
        string $subtitle = 'Pokusajte ponovno uskoro.'
    ): Collection
    {
        return collect([
            [
                'title'   => 'Btn',
                'btn'     => 'secondary',
                'button'  => '',
                'address' => $title,
                'qty'     => $subtitle
            ]
        ]);
    }


    /**
     * @return int
     */
    public function import(?Collection $list = null)
    {
        $imported = 0;

        if ($list) {
            $imported = file_put_contents(agconf('import.warehouse.json'), $list->toJson());
        }

        return $imported;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        $file = json_decode(file_get_contents(agconf('import.warehouse.json')),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
    }

    /**
     * @param string $sku
     *
     * @return bool
     */
    public function itemHasStock(string $sku): bool
    {
        $warehouses = $this->getAvailabilityForProduct($sku);

        foreach ($warehouses as $warehouse) {
            if ($warehouse['title'] == 'Web' && $warehouse['qty'] > 0) {
                return true;
            }
            if ($warehouse['title'] == 'Dobavljač' && $warehouse['qty'] > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $units
     *
     * @return string
     */
    private function getUnitsQuery($units)
    {
        $string = '[';

        foreach ($units as $unit) {
            $string .= $unit['skladiste'] . ',';
        }

        $string = substr($string, 0, -1);

        $string .= ']';

        return $string;
    }


    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setWarehouses($warehouses): array
    {
        $json = json_decode($warehouses);

        if (isset($json->result[0]->skladista)) {
            return $json->result[0]->skladista;
        }

        return [];
    }

    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setAvailables($items): array
    {
        $json = json_decode($items);

        if (isset($json->result[0]->stanje)) {
            return $json->result[0]->stanje;
        }

        return [];
    }


    private function hasValidAvailabilityPayload($items): bool
    {
        $json = json_decode($items);

        return isset($json->result[0]->stanje) && is_array($json->result[0]->stanje);
    }


    /**
     * @param $stock
     *
     * @return mixed
     */
    private function setSuplierStock($stock)
    {
        $json = json_decode($stock);

        if (isset($json->result[0]->artikli_dobavljaci)) {
            return $json->result[0]->artikli_dobavljaci;
        }

        return [];
    }


    private function hasValidSupplierPayload($stock): bool
    {
        $json = json_decode($stock);

        return isset($json->result[0]->artikli_dobavljaci) && is_array($json->result[0]->artikli_dobavljaci);
    }
}
