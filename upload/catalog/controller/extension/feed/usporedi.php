<?php

class ControllerExtensionFeedUsporedi extends Controller {

    // Polyfill za array_column za starije PHP verzije
    public function __construct($registry) {
        parent::__construct($registry);
        if (!function_exists('array_column')) {
            function array_column($input, $column_key, $index_key = null) {
                $arr = array();
                foreach ($input as $row) {
                    if (!is_array($row)) continue;
                    $value = isset($row[$column_key]) ? $row[$column_key] : null;
                    if ($index_key !== null && isset($row[$index_key])) {
                        $arr[$row[$index_key]] = $value;
                    } else {
                        $arr[] = $value;
                    }
                }
                return $arr;
            }
        }
    }

    private $categoryMapByProduct = array();

    public function index()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        if (isset($_GET['pass']) && $_GET['pass'] === 'gNGpx7VIuaIgjohTMQXs') {

            $output = '<?xml version="1.0" encoding="UTF-8"?>';
            $output .= '<products>';

            $this->load->model('catalog/product');
            $this->load->model('catalog/category');

            $products = $this->model_catalog_product->getProducts();
            $productIds = array_column($products, 'product_id');
            $this->preloadCategoryNamesForProducts($productIds);

            foreach ($products as $product) {

                $description = $this->stripInvalidXml($product['description']);
                $kratki = $this->stripInvalidXml($this->elipsis($description));

                $name = strip_tags(html_entity_decode($product['name']));
                $name = str_replace(
                    array('&nbsp;', '', '', '&#44', "'", '', '', '>', '<'),
                    '',
                    $name
                );
                $name = $this->stripInvalidXml($name);

                $price = ($product['special'] == '') ? $product['price'] : $product['special'];

                if ($product['quantity'] > 0) {
                    $output .= '<product>';
                    $output .= '<sku_id>' . $product['model'] . '</sku_id>';
                    $output .= '<name>' . $this->wrapInCDATA($name) . '</name>';
                    $output .= '<description>' . $this->wrapInCDATA($description) . '</description>';
                    $output .= '<manufacturer>' . $this->wrapInCDATA($product['manufacturer']) . '</manufacturer>';
                    $output .= '<category>' . $this->wrapInCDATA($this->getCategoriesNameFast($product['product_id'])) . '</category>';
                    $output .= '<price>' . number_format($price, 2, '.', '') . '</price>';
                    $output .= '<delivery_price>4.99</delivery_price>';
                    $output .= '<product_url>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</product_url>';
                    $output .= '<gallery>';
                    $output .= '<image>';
                    if (!empty($product['image'])) {
                        $output .= '<image_url>' . $this->wrapInCDATA('https://www.chipoteka.hr/image/' . $product['image']) . '</image_url>';
                    }
                    $output .= '</image>';
                    $output .= '</gallery>';

                    $output .= '<availability>';
                    $output .= '<branch>';
                    $output .= '<branch_id>1</branch_id>';
                    $output .= '<status_id>1</status_id>';
                    $output .= '</branch>';
                    $output .= '</availability>';

                    $output .= '</product>';
                }
            }

            $output .= '</products>';

            $output = iconv('UTF-8', 'UTF-8//IGNORE', $output);

            $this->response->addHeader('Content-Type: application/xml');
            $this->response->setOutput($output);
        }
    }

    private function wrapInCDATA($in)
    {
        return "<![CDATA[ " . $in . " ]]>";
    }

    private function stripInvalidXml($value)
    {
        $ret = "";
        if (empty($value)) return $ret;

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value[$i]);
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }

    public function elipsis($text, $words = 30)
    {
        if (str_word_count($text) > $words) {
            preg_match("/(?:[^\s,\.;\?\!]+(?:[\s,\.;\?\!]+|$)){0,$words}/", $text, $matches);
            $text = trim($matches[0]);
            if (substr($text, -1) == ',' || substr($text, -1) == '.') {
                $text = rtrim($text, ',.');
            }
            $text .= '...';
        }
        return $text;
    }

    private function preloadCategoryNamesForProducts($productIds)
    {
        if (empty($productIds)) return;

        // Filtriraj i sanitiziraj ID-eve
        $ids = array();
        foreach ($productIds as $pid) {
            $pid = (int)$pid;
            if ($pid > 0) $ids[] = $pid;
        }
        if (empty($ids)) return;

        $language_id = (int)$this->config->get('config_language_id');

        // Uzmi SVE candidate-pathove po proizvodu, poredaj po dubini i duljini opisa, pa u PHP-u zadrži prvi (najdublji/najduži)
        $sql = "
        SELECT 
            p2c.product_id,
            cat.full_name,
            cat.depth
        FROM " . DB_PREFIX . "product_to_category p2c
        JOIN (
            SELECT 
                cp.category_id,
                GROUP_CONCAT(cd2.name ORDER BY cp.level SEPARATOR ' > ') AS full_name,
                COUNT(*) AS depth
            FROM " . DB_PREFIX . "category_path cp
            JOIN " . DB_PREFIX . "category_description cd2 
                ON cd2.category_id = cp.path_id
               AND cd2.language_id = " . $language_id . "
            GROUP BY cp.category_id
        ) cat ON cat.category_id = p2c.category_id
        WHERE p2c.product_id IN (" . implode(',', $ids) . ")
        ORDER BY p2c.product_id, cat.depth DESC, LENGTH(cat.full_name) DESC
    ";

        $q = $this->db->query($sql);

        // U mapu upiši SAMO prvi red za svaki product_id (jer je već poredano po najdubljem/najdužem)
        foreach ($q->rows as $row) {
            $pid = (int)$row['product_id'];
            if (!isset($this->categoryMapByProduct[$pid])) {
                $this->categoryMapByProduct[$pid] = $row['full_name'];
            }
        }
    }


    private function getCategoriesNameFast($product_id)
    {
        return isset($this->categoryMapByProduct[$product_id]) ? $this->categoryMapByProduct[$product_id] : '';
    }

    public function getNabavnacijena($id)
    {
        $data = $this->getProductDiscounts($id);
        $cijena = '';
        foreach ($data as $item) {
            if ($item['customer_group_id'] == 5) {
                $cijena = $item['price'];
            }
        }
        return $cijena;
    }

    public function getProductDiscounts($product_id)
    {
        $query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "product_discount 
             WHERE product_id = '" . (int)$product_id . "' 
             ORDER BY quantity, priority, price"
        );
        return $query->rows;
    }
}
?>
