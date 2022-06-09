<?php

class ControllerExtensionFeedNabavanet extends Controller {

    public function index()
    {

        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<products>';



        $this->load->model('catalog/product');
        $this->load->model('catalog/category');



        $products = $this->model_catalog_product->getProducts();

        foreach ($products as $product) {
            if ($product['quantity'] > 0 && $product['model'] != '') {

                $description = strip_tags(html_entity_decode($product['meta_description']));
                $description = str_replace('&nbsp;', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('&#44', '', $description);
                $description = str_replace("'", '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('.', '', $description);
                $description = str_replace('', '', $description);
                $description = str_replace('>', '', $description);
                $description = str_replace('<', '', $description);


                $description = $this->stripInvalidXml($description);

                $name = strip_tags(html_entity_decode($product['name']));
                $name = str_replace('&nbsp;', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('&#44', '', $name);
                $name = str_replace("'", '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('.', '', $name);
                $name = str_replace('', '', $name);
                $name = str_replace('>', '', $name);
                $name = str_replace('<', '', $name);

                $name = $this->stripInvalidXml($name);

                if ($product['price'] < 500) {
                    $shipping_cost = '39.00';

                } else {
                    $shipping_cost = '0';
                }

                if($product['special'] == ''){
                    $price = $product['price'];
                }
                else{
                    $price = $product['special'];
                }

                $output .= '<product>';
                $output .= '<internal_product_id>' . $product['product_id'] . '</internal_product_id>';
               $output .= '<name>' . $this->wrapInCDATA($name) . '</name>';
                $output .= '<price>' . number_format($price,'2', '.','') . '</price>';
                //$output .= '<regular_price>' . $product['price_2'] . '</regular_price>';
                $output .= '<url>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</url>';

                $output .= '<availability>Raspoloživo</availability>';

                $output .= '<shipping_info>Besplatna dostava za narudžbe iznad 500 kn</shipping_info>';

               // $output .= '<internal_product_id>' . $product['product_id'] . '</internal_product_id>';
                 $output .= '<category>'.$this->wrapInCDATA($this->getCategoriesName($product['product_id'])).'</category>';
                $output .= '<image_url>' . $this->wrapInCDATA('https://cdn.chipoteka.hr/image/' . $product['image']) . '</image_url>';
        $output .= '<description>' . $this->wrapInCDATA($description) . '</description>';
                 $output .= '<shipping_cost>'. $shipping_cost .'</shipping_cost>';

             /*   if($product['upc'] && count_chars($product['upc'], 3) > 1 ){
                     $output .= '<upc>' . $product['upc'] . '</upc>';
                 }*/



                $output .= '<mobile_url>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</mobile_url>';


                $output .= '<brand>' . $this->wrapInCDATA($product['manufacturer']) . '</brand>';
                 $output .= '<brand_product_url>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product['manufacturer_id']) . '</brand_product_url>';
                if($product['mpn']) {
                    $output .= '<warranty>' . $product['mpn'] . '</warranty>';
                }
                $output .= '</product>';
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

}

?>