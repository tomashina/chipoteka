<?php

use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Action;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Manufacturer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;

class ControllerExtensionModuleLuceedSync extends Controller
{

    private $error = array();


    public function install()
    {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_luceed_sync', [
            ['module_luceed_sync_status'] => 1
        ]);*/

        $this->db->query("ALTER TABLE " . DB_PREFIX . "category ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER parent_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "manufacturer ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER manufacturer_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "customer ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER customer_id;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN luceed_uid VARCHAR(255) NULL AFTER order_id;");
    }


    public function uninstall()
    {
        //$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "shipping_collector`");

        $this->db->query("ALTER TABLE " . DB_PREFIX . "category DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "manufacturer DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "customer DROP COLUMN luceed_uid;");
        $this->db->query("ALTER TABLE " . DB_PREFIX . "order DROP COLUMN luceed_uid;");
    }


    public function index()
    {
        $this->load->language('extension/module/luceed_sync');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/shipping_collector', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/luceed_sync_dash', $data));
    }


    /**
     * @return object
     */
    public function importCategories()
    {
        $_loc = new LOC_Category(LuceedGroup::all());

        $imported = $_loc->checkDiff()->import();

        return $this->response($imported, 'categories');
    }


    /**
     * @return mixed
     */
    public function importManufacturers()
    {
        $_loc = new LOC_Manufacturer();

        $imported = $_loc->getFromProducts(LuceedProduct::all())
                         ->checkDiff()
                         ->import();

        return $this->response($imported, 'manufacturers');
    }


    /**
     * @return object
     */
    public function importProducts()
    {
        $_loc  = new LOC_Product(LuceedProduct::all());
        $count = 0;

        $new_products = $_loc->checkDiff()->getProductsToAdd();

        if ($new_products->count()) {
            $this->load->model('catalog/product');

            foreach ($new_products as $product) {
                $this->model_catalog_product->addProduct(
                    $_loc->make($product)
                );
                $count++;
            }
        }

        return $this->response($count, 'products');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importActions()
    {
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $imported = $_loc->collectActive()
                         ->sort()
                         ->import();

        return $this->response($imported, 'products_actions');
    }


    /**
     * @return mixed
     */
    public function checkMinQty()
    {
        $activated   = 0;
        $deactivated = 0;
        $products    = Product::all();

        foreach ($products as $product) {
            if ($product->quantity < $product->active_min && $product->status) {
                $product->update(['status' => 0]);
                $deactivated++;
            }

            if ($product->quantity >= $product->active_min && ! $product->status) {
                $product->update(['status' => 1]);
                $activated++;
            }
        }

        return $this->response([$activated, $deactivated], 'active');
    }


    /**
     * @return mixed
     */
    public function checkMinQtyOfCategories()
    {
        $prod_min = $this->checkMinQty();

        $activated   = 0;
        $deactivated = 0;
        $categories  = Category::all();

        foreach ($categories as $category) {
            $active   = false;
            $pids     = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
            $products = Product::whereIn('product_id', $pids)->get();

            if ($products->count()) {
                foreach ($products as $product) {
                    if ($product->status) {
                        $active = true;
                    }
                }
            }

            if ($active) {
                $category->update(['status' => 1]);
                $activated++;
            } else {
                $category->update(['status' => 0]);
                $deactivated++;
            }
        }

        return $this->response([$activated, $deactivated], 'cat_active');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updatePricesAndQuantities()
    {
        $_loc = new LOC_Product(LuceedProduct::all());

        $updated = $_loc->sortForUpdate()->update();

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updatePrices()
    {
        $_loc = new LOC_Product(LuceedProduct::all());

        $updated = $_loc->sortForUpdate()->update('prices');

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateQuantities()
    {
        $_loc = new LOC_Product(LuceedProduct::all());

        $updated = $_loc->sortForUpdate()->update('quantity');

        return $this->response($updated, 'update');
    }


    /**
     * @return bool
     */
    protected function validateRole()
    {
        if ( ! $this->user->hasPermission('modify', 'extension/module/luceed_sync_dash')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return ! $this->error;
    }


    /**
     * @param int|string $condition
     * @param string     $text
     *
     * @return mixed
     */
    private function response($condition, string $text)
    {
        $this->load->language('extension/module/luceed_sync');

        if ($condition) {
            if (is_string($condition) || is_integer($condition)) {
                return $this->output(['status' => 200, 'message' => sprintf($this->language->get('text_success_' . $text), $condition)]);
            }

            return $this->output(['status' => 200, 'message' => sprintf($this->language->get('text_success_' . $text), $condition[0], $condition[1])]);
        }

        return $this->output(['status' => 300, 'message' => $this->language->get('text_warning_' . $text)]);
    }


    /**
     * @param $data
     *
     * @return mixed
     */
    private function output($data)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(collect($data)->toJson());
    }

}