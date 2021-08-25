<?php

use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedManufacturer;
use Agmedia\Luceed\Facade\LuceedOrder;
use Agmedia\Luceed\Facade\LuceedPayments;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Facade\LuceedWarehouse;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Action;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Customer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Manufacturer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Payment;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\LuceedOpencartWrapper\Models\LOC_ProductSingle;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Stock;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Order\Order;
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
     * @return object
     */
    public function updateCategories()
    {
        $_loc = new LOC_Category(LuceedGroup::all());

        $updated = $_loc->joinByUid()->update();

        return $this->response($updated, 'categories');
    }


    /**
     * @return mixed
     */
    public function importManufacturers()
    {
        $_loc = new LOC_Manufacturer(LuceedManufacturer::all());

        $imported = $_loc->checkDiff()->import();

        return $this->response($imported, 'manufacturers');
    }


    /**
     * @return mixed
     */
    public function importInitialManufacturers()
    {
        $_loc = new LOC_Manufacturer();

        $imported = $_loc->initialImport();

        return $this->response($imported, 'manufacturers');
    }


    /**
     * @return mixed
     */
    public function importInitialCustomers()
    {
        $_loc = new LOC_Customer();

        $imported = $_loc->initialImport();

        return $this->response($imported, 'customers');
    }


    /**
     * @return mixed
     */
    public function importWarehouses()
    {
        $_loc = new LOC_Warehouse(LuceedWarehouse::all());

        $imported = $_loc->import($_loc->getWarehouses());

        return $this->response($imported, 'warehouses');
    }


    /**
     * @return mixed
     */
    public function importPayments()
    {
        $_loc = new LOC_Payment(LuceedPayments::all());

        $imported = $_loc->import($_loc->getList());

        return $this->response($imported, 'payments');
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
     *
     */
    public function importProduct()
    {
        if ($this->request->get['id']) {
            $_loc = new LOC_ProductSingle(
                LuceedProduct::getById($this->request->get['id'])
            );
        } else {
            $_loc = new LOC_ProductSingle();
        }

        if ($_loc->product) {
            $_loc->make();
        }

        if ($_loc->products) {
            $count = 0;

            foreach ($_loc->products as $product) {
                $_loc->product = $_loc->setProduct(
                    LuceedProduct::getById($product->sku)
                );

                if ($_loc->product) {
                    $_loc->make();

                    $count++;
                }

            }
        }


    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importActions()
    {
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $imported = $_loc->collectActive()
                         ->sortActions()
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
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $updated = $_loc->collectWebPrices()
                        ->update();

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateQuantities()
    {
        $_loc = new LOC_Stock();

        $_loc->setSkladista(
            LuceedProduct::getWarehouseStock(agconf('import.warehouse.default'))
        )->sort();

        $_loc->setDobavljaci(
            LuceedProduct::getSuplierStock()
        )->sort();

        $updated = $_loc->createQuery()->update();

        return $this->response($updated, 'update_stock');
    }


    /**
     * @return mixed
     */
    public function updateOrderStatuses()
    {
        $loc = new LOC_Order();

        $loc->setOrders(
            LuceedOrder::get(
                $loc->collectStatuses(),
                agconf('import.orders.from_date')
            )
        );

        $updated = $loc->sort()->updateStatuses();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $this->response($updated, 'orders');
    }


    /**
     * @return mixed
     */
    public function checkOrderStatusDuration()
    {
        $loc = new LOC_Order();

        $updated = $loc->checkStatusDuration();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $this->response($updated, 'orders');
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
     * @param array $order
     *
     * @throws Exception
     */
    private function sendMail(array $order = null)
    {
        if ($order && isset($order['order_id']) && isset($order['mail'])) {
            $email = $this->loadEmails($order['mail']);
            $data = Order::where('order_id', $order['order_id'])->with('products', 'totals')->first()->toArray();
            $data['mail_text'] = sprintf($email['text'], $order['order_id']);

            for ($i = 0; $i < count($data['products']); $i++) {
                $data['products'][$i]['image'] = Product::where('product_id', data['products'][$i]['product_id'])->pluck('image');
            }
            $data['mail_logo'] = DIR_IMAGE.'chipoteka-hd.png';
            $data['mail_title'] = sprintf($email['subject'], $order['order_id']);

            \Agmedia\Helpers\Log::store($data);


            $html = $this->load->view('mail/mail', $data);

            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($order['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(sprintf($email['subject'], $order['order_id']));
            $mail->setHtml($html);
            $mail->send();
        }
    }


    /**
     * @param null $key
     *
     * @return array|\Illuminate\Support\Collection|mixed
     */
    private function loadEmails($key = null)
    {
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/emails.json'),TRUE);

        if ($file) {
            if ($key) {
                return collect($file[$key]);
            }

            return collect($file);
        }

        return [];
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