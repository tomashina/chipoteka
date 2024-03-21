<?php

class ControllerExtensionFeedRonis extends Controller {

    public function index()
    {

        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<products>';



        $this->load->model('catalog/product');
        $this->load->model('catalog/category');



        $products = $this->model_catalog_product->getProducts();

        foreach ($products as $product) {

            $nabavna_cijena = $this->getNabavnacijena($product['product_id']);



            if ($nabavna_cijena != '') {


                /*  $url ='http://sechip.dyndns.org:8888/datasnap/rest/StanjeZalihe/Skladiste/[101,001,002,003,004,005,007,011,012]/'.$product['model'];


                  $username = 'webshop';
                  $password = 'bJ8tn63Q';
                  $ch       = curl_init($url);
                  curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($ch);


                  header('Content-Type: application/json');

                  $response =    json_decode($response, true);

                 $kol=0;

                 foreach ($response['result'][0]['stanje'] as $item){

                     $kol += $item['raspolozivo_kol'];

                 }

                 $ukupna_kolicina = $kol;*/

                $description = $product['description'];


                $kratki = $this->elipsis($description);






                $description = $this->stripInvalidXml($description);

                $name = strip_tags(html_entity_decode($product['name']));
                $name = str_replace('&nbsp;', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('&#44', '', $name);
                $name = str_replace("'", '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('>', '', $name);
                $name = str_replace('<', '', $name);

                $name = $this->stripInvalidXml($name);

                if ($product['special'] == '') {
                    $price = $product['price'];
                } else {
                    $price = $product['special'];
                }

                $vpc = $product['vpc'];

                if($product['quantity'] > 0){
                    $output .= '<product>';
                    $output .= '<sifra>' . $product['model'] . '</sifra>';
                    $output .= '<ean>' . $product['upc'] . '</ean>';
                    $output .= '<kolicina>' .  $product['quantity'] . '</kolicina>';
                    $output .= '<name>' . $this->wrapInCDATA($name) . '</name>';
                    $output .= '<brand>' . $this->wrapInCDATA($product['manufacturer']) . '</brand>';
                    $output .= '<kratki_opis>' . $this->wrapInCDATA($kratki) . '</kratki_opis>';
                    $output .= '<mpc>' . number_format($price, '2', '.', '') . '</mpc>';
                    $output .= '<vpc>' . number_format($vpc, '2', '.', '') . '</vpc>';
                    $output .= '<nabavna_cijena>' . number_format($nabavna_cijena, '2', '.', '') . '</nabavna_cijena>';
                    $output .= '<url>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</url>';
                    $output .= '<category>' . $this->wrapInCDATA($this->getCategoriesName($product['product_id'])) . '</category>';
                    $output .= '<image_url>' . $this->wrapInCDATA('https://www.chipoteka.hr/image/' . $product['image']) . '</image_url>';
                    $output .= '<description>' . $this->wrapInCDATA($description) . '</description>';
                    $output .= '</product>';

                }
            }

        }
        $output .= '</products>';


        $output = iconv('UTF-8', 'UTF-8//IGNORE', $output);

        $this->response->addHeader('Content-Type: application/xml');
        $this->response->setOutput($output);




    }


    private function wrapInCDATA($in)
    {
        return "<![CDATA[ " . $in . " ]]>";
        //return $in;
    }


    private function stripInvalidXml($value)
    {
        $ret = "";
        $current;
        if (empty($value))
        {
            return $ret;
        }

        $length = strlen($value);
        for ($i=0; $i < $length; $i++)
        {
            $current = ord($value[$i]);
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||

                (($current >= 0x28) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $ret .= chr($current);
            }
            else
            {
                $ret .= " ";
            }
        }
        return $ret;
    }


    private function removeChar($string, $char)
    {
        return str_replace($char, '', $string);
    }

     public function elipsis ($text, $words = 30) {
        // Check if string has more than X words
        if (str_word_count($text) > $words) {

            // Extract first X words from string
            preg_match("/(?:[^\s,\.;\?\!]+(?:[\s,\.;\?\!]+|$)){0,$words}/", $text, $matches);
            $text = trim($matches[0]);

            // Let's check if it ends in a comma or a dot.
            if (substr($text, -1) == ',') {
                // If it's a comma, let's remove it and add a ellipsis
                $text = rtrim($text, ',');
                $text .= '...';
            } else if (substr($text, -1) == '.') {
                // If it's a dot, let's remove it and add a ellipsis (optional)
                $text = rtrim($text, '.');
                $text .= '...';
            } else {
                // Doesn't end in dot or comma, just adding ellipsis here
                $text .= '...';
            }
        }
        // Returns "ellipsed" text, or just the string, if it's less than X words wide.
        return $text;
    }


    protected function getPath($parent_id, $current_path = '') {
        $category_info = $this->model_catalog_category->getCategory($parent_id);

        if ($category_info) {
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            } else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }

            $path = $this->getPath($category_info['parent_id'], $new_path);

            if ($path) {
                return $path;
            } else {
                return $new_path;
            }
        }
    }


    /**
     * Construct category and parent name
     * and return it
     *
     * @param $id
     *
     * @return string
     */
    public function getCategoriesName($id)
    {
        $this->load->model('catalog/category');
        $data = $this->model_catalog_product->getCategories($id);
        $ime = '';
        $category = array();

        foreach ($data as $item) {

            $category = $this->model_catalog_category->getCategory($item['category_id']);

            if (isset($category['name'])){
                $ime = $category['name'];
            }


            if (isset($category['parent_id']) && $category['parent_id'] != 0) {
                $parent = $this->model_catalog_category->getCategory($category['parent_id']);
                $ime   = $parent['name'] . ' > ' . $category['name'];
            }



        }

        return $ime;
    }


    public function getNabavnacijena($id)
    {
        $this->load->model('catalog/product');


        $data = $this->getProductDiscounts($id);




        $cijena ='';
        foreach ($data as $item) {

            if ($item['customer_group_id'] == 4 ){
                $cijena = $item['price'];
            }

        }

        return $cijena;
    }

    public function getProductDiscounts($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' ORDER BY quantity, priority, price");

        return $query->rows;
    }

}

?>