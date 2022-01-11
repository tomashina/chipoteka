<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedOrder;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Document
{

    /**
     * @var
     */
    private $document;


    /**
     * @param array $products
     *
     * @return array
     */
    public function sortProducts(array $products): array
    {
        for ($i = 0; $i < count($products); $i++) {
            foreach ($this->document->first()->stavke as $document_item) {
                if ($document_item->artikl == $products[$i]['model']) {
                    $products[$i]['kolicina'] = $document_item->kolicina;
                    $products[$i]['dostupno'] = 1;
                }
            }
        }

        for ($i = 0; $i < count($products); $i++) {
            if ( ! isset($products[$i]['dostupno'])) {
                $products[$i]['kolicina'] = 0;
                $products[$i]['dostupno'] = 0;
            }
        }

        return $products;
    }


    /**
     * @param string $document
     * @param bool   $b2b
     *
     * @return $this
     */
    public function setDocument(string $document, bool $b2b = false): LOC_Document
    {
        if ($document) {
            $json = json_decode(
                LuceedOrder::document($document, $b2b)
            );

            $this->document = collect($json->result[0]->dokumenti)->where('dokument', 'OT');
        }

        return $this;
    }

}