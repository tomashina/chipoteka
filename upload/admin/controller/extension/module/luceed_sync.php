<?php

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedManufacturer;
use Agmedia\Luceed\Facade\LuceedOrder;
use Agmedia\Luceed\Facade\LuceedPayments;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Models\LuceedProduct as LuceedProductRecord;
use Agmedia\Luceed\Facade\LuceedWarehouse;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForRevisionData;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Action;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Customer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Manufacturer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Payment;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Price;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\LuceedOpencartWrapper\Models\LOC_ProductSingle;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Related;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Stock;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Order\Order;
use Agmedia\Models\Order\OrderStatus;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Carbon\Carbon;

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

        $data['revision_products']   = LuceedProductForRevision::with('product')->get();
        $data['price_zero_products'] = Product::where('price', 0)->get();
        $data['rev_ids']             = $data['revision_products']->pluck('sku')->take(200)->flatten();
        $last_rev                    = LuceedProductForRevisionData::orderBy('last_revision_date', 'desc')->first();

        $data['last_rev'] = 'Nepoznato';
        if ($last_rev) {
            $data['last_rev'] = Carbon::make($last_rev->last_revision_date)->diffForHumans();
        }

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
     * @return object
     */
    public function updateCategoriesUids()
    {
        $_loc = new LOC_Category(LuceedGroup::all());

        $updated = $_loc->updateUids();

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
    public function updateManufacturersUids()
    {
        $_loc = new LOC_Manufacturer(LuceedManufacturer::all());

        $updated = $_loc->updateByUids();

        return $this->response($updated, 'manufacturers');
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
    public function importEprelModel()
    {



        $api_key = "EJIHIAziLs86nUV0rs7V08u5XTg1m8LF16uXJ9J4";

        $target = $this->request->get['target'];



        if($target =='electronicdisplays'){
             $category_id= ['320', '396'];
        }

        elseif ($target =='smartphonestablets20231669'){

            $category_id = ['378', '380', '389'];
        }

        elseif ($target =='lightsources'){

            $category_id= ['538'];
        }



        $ids = ProductCategory::whereIn('category_id', $category_id)->pluck('product_id')->unique();

        foreach ($ids as $id) {

           sleep(1);

            $product = Product::query()->where('product_id', $id)->where('ean', '!=', '')->first();

            Log::info($id, 'eprel');

            if($product  and $product['jan'] == ''){
                $url = "https://eprel.ec.europa.eu/api/products/".$target."?modelIdentifier=".$product['ean']."";

                Log::info($url, 'eprel');


                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERAGENT, "curl/7.88.1");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "x-api-key: $api_key"
                ));
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // -L equivalent

                header('Content-Type: application/json');

                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    Log::info(curl_error($ch), 'eprel');
                } else {

                    $result = json_decode($response);

                    foreach ($result->hits as $hit) {

                        if($product['ean'] = $hit->modelIdentifier ){



                            $product->update(['jan' => '{"0":"'.$hit->energyClassImage.'","1":"'.$hit->eprelRegistrationNumber.'","2":"'.$hit->productGroup.'"}']);


                        }

                    }


                    Log::info($response, 'eprel');
                }
                curl_close($ch);



            }






           // Log::info($product['ean'], 'eprel');
        }


        return $this->response(200, 'success');
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
     * @return mixed
     * @throws Exception
     */
    public function updateProducts()
    {
        $this->importLuceedProducts();

        $count      = 0;
        $_loc_ps    = new LOC_ProductSingle();
        $for_update = LuceedProductForUpdate::all();

        if ($for_update->count()) {
            foreach ($for_update as $item) {
                if ($_loc_ps->hasForUpdate()) {
                    if ( ! isset($_loc_ps->product['naziv'])) {
                        return $this->output($_loc_ps->finishUpdateError());
                    }

                    $product = $this->resolveOldProductData($_loc_ps->product_to_update);
                    // first check known errors
                    $product_for_update                    = $_loc_ps->makeForUpdate($product);
                    $product_for_update['quantity']        = $_loc_ps->product_to_update['quantity'];
                    $product_for_update['stock_status_id'] = $_loc_ps->product_to_update['stock_status_id'];

                    Log::info($product_for_update['sku'], 'log2025');

                    if ($product_for_update['sku'] == '6129256300') {
                        return $this->output($_loc_ps->finishUpdate());
                    }

                    $this->model_catalog_product->editProduct(
                        $_loc_ps->product_to_update['product_id'],
                        $product_for_update
                    );

                    $_loc_ps->finishUpdate();

                    LuceedProductForUpdate::where('uid', $_loc_ps->product_to_update['luceed_uid'])->delete();

                    $count++;
                }
            }
        }

        //$this->updateQuantities();
        $this->finishUpdateProduct();

        return $this->response($count, 'products');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importLuceedProducts()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $_loc = new LOC_Product(LuceedProduct::all());

        return $this->output($_loc->populateLuceedData());
    }


    /**
     * @return mixed
     */
    public function updateProduct()
    {
        $_loc_ps = new LOC_ProductSingle();
        $this->load->model('catalog/product');

        // Ako smo mu dali uid preko revision liste.
        if (isset($this->request->get['products'])) {
            $_loc_p     = new LOC_Product(LuceedProduct::all());
            $list       = substr($this->request->get['products'], 1, -1);
            $for_update = $_loc_p->sortForUpdate($list)
                                 ->getProductsToAdd();

            $_loc_p->cleanRevisionTable($for_update->pluck('artikl_uid'));

            foreach ($for_update as $product) {
                $_loc_ps->setForUpdate($product);

                if ($_loc_ps->product) {
                    $product                               = $this->resolveOldProductData($_loc_ps->product_to_update);
                    $product_for_update                    = $_loc_ps->makeForUpdate($product);
                    $product_for_update['quantity']        = $_loc_ps->product_to_update['quantity'];
                    $product_for_update['stock_status_id'] = $_loc_ps->product_to_update['stock_status_id'];

                    $this->model_catalog_product->editProduct(
                        $_loc_ps->product_to_update['product_id'],
                        $product_for_update
                    );
                } else {
                    if ($_loc_ps->product_to_insert) {
                        $this->model_catalog_product->addProduct(
                            $_loc_ps->makeForInsert()
                        );
                    }
                }
            }

            return $this->response($for_update->count(), 'products');
        }





        // Ako ima proizvoda za UPDATE
        if ($_loc_ps->hasForUpdate()) {
            if ( ! isset($_loc_ps->product['naziv'])) {
                return $this->output($_loc_ps->finishUpdateError());
            }

            $product = $this->resolveOldProductData($_loc_ps->product_to_update);
            // first check known errors
            $product_for_update                    = $_loc_ps->makeForUpdate($product);
            $product_for_update['quantity']        = $_loc_ps->product_to_update['quantity'];
            $product_for_update['stock_status_id'] = $_loc_ps->product_to_update['stock_status_id'];

            if ($product_for_update['sku'] == '6129256300') {
                return $this->output($_loc_ps->finishUpdate());
            }

            $this->model_catalog_product->editProduct(
                $_loc_ps->product_to_update['product_id'],
                $product_for_update
            );

            LuceedProductForUpdate::where('uid', $_loc_ps->product_to_update['luceed_uid'])->delete();

            return $this->output($_loc_ps->finishUpdate());

        } else {
            // Ako ima proizvoda za INSERT
            if ($_loc_ps->hasForInsert()) {
                // first check known errors
                $product_for_insert = $_loc_ps->makeForInsert();

                $this->model_catalog_product->addProduct(
                    $product_for_insert
                );

                return $this->output($_loc_ps->finishInsert());
            }

            if ($_loc_ps->hasForDelete()) {
                $this->model_catalog_product->deleteProduct(
                    $_loc_ps->getDeleteProductId()
                );

                $_loc_ps->deleteFromRevision();

                return $this->output($_loc_ps->finishDelete());
            }
        }

        return $this->output($_loc_ps->finish());
    }


    /**
     * @return mixed
     */
    public function updateProductAttributes()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $cursor = isset($this->request->get['cursor']) ? max((int) $this->request->get['cursor'], 0) : 0;
        $limit  = isset($this->request->get['limit']) ? max((int) $this->request->get['limit'], 1) : 100;
        $total  = LuceedProductRecord::query()->count();

        if ( ! $total) {
            return $this->output([
                'status' => 300,
                'message' => 'Luceed cache proizvoda je prazan. Prvo osvježi proizvode iz Luceed-a pa pokreni update atributa.'
            ]);
        }

        $batch = LuceedProductRecord::query()
            ->select('uid', 'data')
            ->orderBy('uid')
            ->skip($cursor)
            ->take($limit)
            ->get();

        if ($batch->isEmpty()) {
            return $this->output([
                'status' => 200,
                'processed' => 0,
                'definitions' => 0,
                'products' => 0,
                'attributes' => 0,
                'next_cursor' => $cursor,
                'total' => $total,
                'done' => true,
            ]);
        }

        $definitions    = 0;
        $updatedProducts = 0;
        $updatedAttrs    = 0;

        foreach ($batch as $luceedProduct) {
            $localProduct = Product::query()
                ->where('luceed_uid', $luceedProduct->uid)
                ->first(['product_id']);

            if ( ! $localProduct) {
                continue;
            }

            $data = @unserialize(base64_decode($luceedProduct->data));

            if ( ! is_array($data)) {
                continue;
            }

            $updatedAttrs += ProductHelper::syncProductAttributes(
                (int) $localProduct->product_id,
                collect($data),
                true
            );

            $updatedProducts++;
        }

        $nextCursor = $cursor + $batch->count();

        return $this->output([
            'status' => 200,
            'processed' => $batch->count(),
            'products' => $updatedProducts,
            'attributes' => $updatedAttrs,
            'next_cursor' => $nextCursor,
            'total' => $total,
            'done' => $nextCursor >= $total,
        ]);
    }


    /**
     * @return mixed
     */
    public function updateAttributeSortOrders()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $cursor = isset($this->request->get['cursor']) ? max((int) $this->request->get['cursor'], 0) : 0;
        $limit  = isset($this->request->get['limit']) ? max((int) $this->request->get['limit'], 1) : 25;
        $total  = Product::query()
            ->whereNotNull('luceed_uid')
            ->where('luceed_uid', '!=', '')
            ->count();

        if ( ! $total) {
            return $this->output([
                'status' => 300,
                'message' => 'Nema lokalnih proizvoda povezanih s Luceed-om za update redoslijeda atributa.'
            ]);
        }

        $batch = Product::query()
            ->select('product_id', 'sku', 'luceed_uid')
            ->whereNotNull('luceed_uid')
            ->where('luceed_uid', '!=', '')
            ->orderBy('product_id')
            ->skip($cursor)
            ->take($limit)
            ->get();

        if ($batch->isEmpty()) {
            return $this->output([
                'status' => 200,
                'processed' => 0,
                'definitions' => 0,
                'products' => 0,
                'attributes' => 0,
                'next_cursor' => $cursor,
                'total' => $total,
                'done' => true,
            ]);
        }

        $definitions    = 0;
        $updatedProducts = 0;
        $updatedAttrs    = 0;

        $luceedProducts = $this->fetchLuceedProductsBatch($batch);

        foreach ($batch as $localProduct) {
            if ( ! isset($luceedProducts[(int) $localProduct->product_id])) {
                continue;
            }

            $data = $luceedProducts[(int) $localProduct->product_id];

            $definitions += ProductHelper::syncAttributeDefinitions(
                $data,
                true
            );

            $updatedAttrs += ProductHelper::syncProductAttributeSortOrders(
                (int) $localProduct->product_id,
                $data,
                true
            );

            $updatedProducts++;
        }

        $nextCursor = $cursor + $batch->count();

        return $this->output([
            'status' => 200,
            'processed' => $batch->count(),
            'definitions' => $definitions,
            'products' => $updatedProducts,
            'attributes' => $updatedAttrs,
            'next_cursor' => $nextCursor,
            'total' => $total,
            'done' => $nextCursor >= $total,
        ]);
    }


    /**
     * @param \Illuminate\Support\Collection $products
     *
     * @return array<int, \Illuminate\Support\Collection>
     */
    private function fetchLuceedProductsBatch($products): array
    {
        $results = [];

        if ($products->isEmpty()) {
            return $results;
        }

        if (agconf('env') == 'local' || ! function_exists('curl_multi_init')) {
            foreach ($products as $localProduct) {
                $data = $this->fetchSingleLuceedProductData((string) $localProduct->sku);

                if ($data) {
                    $results[(int) $localProduct->product_id] = $data;
                }
            }

            return $results;
        }

        $chunks = array_chunk($products->all(), 5);

        foreach ($chunks as $chunk) {
            $multiHandle = curl_multi_init();
            $handles = [];

            foreach ($chunk as $localProduct) {
                if (empty($localProduct->sku)) {
                    continue;
                }

                $ch = curl_init(agconf('service.base_url') . \Agmedia\Luceed\LuceedEndPoints::get(agconf('env'))['product'] . $localProduct->sku);
                curl_setopt($ch, CURLOPT_USERPWD, agconf('service.username') . ":" . agconf('service.password'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);

                curl_multi_add_handle($multiHandle, $ch);

                $handles[] = [
                    'handle' => $ch,
                    'product_id' => (int) $localProduct->product_id,
                ];
            }

            $running = null;

            do {
                curl_multi_exec($multiHandle, $running);

                if ($running) {
                    curl_multi_select($multiHandle, 1.0);
                }
            } while ($running);

            foreach ($handles as $item) {
                $response = curl_multi_getcontent($item['handle']);
                $data = $this->decodeLuceedProductResponse($response);

                if ($data) {
                    $results[$item['product_id']] = $data;
                }

                curl_multi_remove_handle($multiHandle, $item['handle']);
                curl_close($item['handle']);
            }

            curl_multi_close($multiHandle);
        }

        return $results;
    }


    /**
     * @param string $sku
     *
     * @return \Illuminate\Support\Collection|null
     */
    private function fetchSingleLuceedProductData(string $sku)
    {
        $response = LuceedProduct::getById($sku);

        return $this->decodeLuceedProductResponse($response);
    }


    /**
     * @param mixed $response
     *
     * @return \Illuminate\Support\Collection|null
     */
    private function decodeLuceedProductResponse($response)
    {
        if ( ! $response) {
            return null;
        }

        $decoded = json_decode($response);

        if (
            ! $decoded
            || ! isset($decoded->result[0]->artikli[0])
        ) {
            return null;
        }

        $luceedProduct = $decoded->result[0]->artikli[0];

        if (is_array($luceedProduct)) {
            $luceedProduct = (object) $luceedProduct;
        }

        if ( ! is_object($luceedProduct)) {
            return null;
        }

        return collect(ProductHelper::collectLuceedData($luceedProduct));
    }


    /**
     * @return mixed
     */
    public function checkRevision()
    {
        $loc_p = new LOC_Product();

        return $this->response($loc_p->checkRevisionTable(), 'products');
    }


    /**
     *
     */
    public function finishUpdateProduct()
    {

        Log::info($this->request->post, 'log2025');
        if (isset($this->request->post['data'])) {
            $inserted = LuceedProductForRevisionData::insert([
                'last_revision_date' => Carbon::now(),
                'data'               => serialize($this->request->post['data'])
            ]);
        } else {
            $inserted = LuceedProductForRevisionData::insert([
                'last_revision_date' => Carbon::now(),
                'data'               => serialize(['updated' => 'No data!', 'inserted' => 'No data!', 'deleted' => 'No data!'])
            ]);
        }

        $this->sendRevisionMail();

        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET updated = 0 WHERE 1");

        //$this->updateQuantities();

        return $this->output($inserted);
    }


    /**
     * @return mixed
     */
    public function updateAdditionalProductData()
    {
        $_loc = new LOC_Product(LuceedProduct::all());

        $products = $_loc->getProducts()
                         ->where('artikl', '!=', '')
                         ->where('naziv', '!=', '')
                         ->where('enabled', '!=', 'N')
                         ->where('webshop', '!=', 'N');

        foreach ($products as $product) {
            Product::where('sku', $product->artikl)->update([
                'jm'        => $product->jm,
                'pakiranje' => $product->pakiranje ?: 1
            ]);
        }

        return $this->response('1', 'products');
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
     * @throws Exception
     */
    public function importRelatedProducts()
    {
        $_loc = new LOC_Related(LuceedProduct::getRelated());

        $imported = $_loc->collectActive()
                         ->sort()
                         ->import();

        return $this->response($imported, 'products');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importActionPricesLast30Days()
    {
        $_loc = new LOC_Action(LuceedProduct::getActionsPricesLast30(), true);

        $imported = $_loc->update('last_30');

        return $this->response($imported, 'actions_last30');
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
   /* public function updatePricesAndQuantities()
    {
        $_loc = new LOC_Product(LuceedProduct::all());

        $updated = $_loc->sortForUpdate()->update();

        return $this->response($updated, 'update');
    }*/


    /**
     * @return mixed
     * @throws Exception
     */
    public function updatePrices()
    {
        $_loc = new LOC_Action(LuceedProduct::getActions());

        $updated = $_loc->collectProductsRegularPrices()
                        ->collectWebPrices()
                        ->update();

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateB2BPrices()
    {
        $updated = 0;
        $lp      = new LOC_Price();

        $lp->deleteProductDiscountDB();

        foreach ($lp->getGroups() as $group) {
            $_loc = new LOC_Price(
                LuceedProduct::getB2BPrices($group['url']),
                $group['id']
            );

            $updated += $_loc->collectB2B()->update('b2b');
        }

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     */
    public function updateVpcPrices()
    {
        $_loc = new LOC_Product(LuceedProduct::all());
        $lp   = new LOC_Price();

        $updated = $lp->collectAndStore($_loc->getProducts(), 'vpc');

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
     * @throws Exception
     */
    public function updateQuantitiesWithoutSupliers()
    {
        $_loc = new LOC_Stock();

        $_loc->setSkladista(
            LuceedProduct::getWarehouseStock(agconf('import.warehouse.default'))
        )->sort();


        $updated = $_loc->createQuery()->updateWithoutSupliers();

        return $this->response($updated, 'update_stock');
    }


    /**
     * @return mixed
     */
    public function updateOrderStatuses()
    {
        try {
            $legacy                 = isset($this->request->get['legacy']) && (int) $this->request->get['legacy'] === 1;
            $cutoff                 = Carbon::now()->subDays(60);
            $excludedLuceedStatuses = $legacy ? $this->getExcludedLuceedStatuses() : [];

            if ($legacy) {
                return $this->output($this->syncLegacyOrderStatusBatch($cutoff, $excludedLuceedStatuses));
            }

            if ($this->shouldRunFullOrderStatusCronSync($legacy)) {
                return $this->output($this->syncAllOrderStatusBatches($cutoff, $excludedLuceedStatuses));
            }

            return $this->output($this->syncOrderStatusBatch($cutoff, $legacy, $excludedLuceedStatuses));
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_order_status_sync_error');
            Log::store($e->getTraceAsString(), 'luceed_order_status_sync_error');

            return $this->output([
                'status'  => 300,
                'message' => 'Greška kod sinkronizacije statusa narudžbi: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * @param Carbon $cutoff
     * @param bool   $legacy
     * @param array  $excludedLuceedStatuses
     *
     * @return array
     */
    private function syncOrderStatusBatch(Carbon $cutoff, bool $legacy = false, array $excludedLuceedStatuses = [], ?int $cursor = null): array
    {
        $this->prepareLongRunningOrderStatusSync();

        if ($cursor === null) {
            $cursor = isset($this->request->get['cursor']) ? (int) $this->request->get['cursor'] : 0;
        }

        $batchSize = $this->getOrderStatusBatchSize();
        $batch     = $this->getOrderStatusBatch($cutoff, $legacy, $cursor, $batchSize, $excludedLuceedStatuses);

        if ($batch->isEmpty()) {
            return [
                'status'     => 200,
                'processed'  => 0,
                'updated'    => 0,
                'done'       => true,
                'empty'      => ($cursor === 0),
                'next_cursor'=> 0
            ];
        }

        $loc               = new LOC_Order();
        $statuses          = $this->getSyncOrderStatusesCollection($excludedLuceedStatuses);
        $statusesById      = $statuses->keyBy('order_status_id');
        $statusesByLuceed  = $statuses->keyBy('luceed_status_id');
        $statusList        = $loc->collectStatuses($statuses->all());
        $startDate         = Carbon::make($batch->min('date_added'))->format('d.m.Y');
        $updates           = [];

        foreach ([false, true] as $b2b) {
            $updates = $this->collectOrderStatusUpdates(
                $updates,
                $batch,
                $statusesById,
                $statusesByLuceed,
                $statusList,
                $startDate,
                $b2b,
                ! $legacy
            );
        }

        $updated = $this->applyOrderStatusUpdates(array_values($updates), ! $legacy);

        if ( ! $legacy) {
            foreach ($updates as $update) {
                $this->sendMail($update);
            }
        }

        return [
            'status'      => 200,
            'processed'   => $batch->count(),
            'updated'     => $updated,
            'done'        => ($batch->count() < $batchSize),
            'empty'       => false,
            'next_cursor' => ($batch->count() < $batchSize) ? 0 : (int) $batch->last()->order_id
        ];
    }


    /**
     * @param bool $legacy
     *
     * @return bool
     */
    private function shouldRunFullOrderStatusCronSync(bool $legacy): bool
    {
        if ($legacy) {
            return false;
        }

        if (isset($this->request->get['cron']) && (int) $this->request->get['cron'] === 1) {
            return true;
        }

        if (isset($this->request->get['user_token']) && $this->request->get['user_token']) {
            return false;
        }

        return ! isset($this->request->get['cursor']) || (int) $this->request->get['cursor'] <= 0;
    }


    /**
     * @param Carbon $cutoff
     * @param array  $excludedLuceedStatuses
     *
     * @return array
     */
    private function syncAllOrderStatusBatches(Carbon $cutoff, array $excludedLuceedStatuses = []): array
    {
        $processed = 0;
        $updated   = 0;
        $cursor    = 0;
        $empty     = true;

        do {
            $result = $this->syncOrderStatusBatch($cutoff, false, $excludedLuceedStatuses, $cursor);

            if ((int) $result['status'] !== 200) {
                return $result;
            }

            $processed += isset($result['processed']) ? (int) $result['processed'] : 0;
            $updated   += isset($result['updated']) ? (int) $result['updated'] : 0;
            $empty      = $empty && ! empty($result['empty']);

            if ( ! empty($result['done'])) {
                return [
                    'status'      => 200,
                    'processed'   => $processed,
                    'updated'     => $updated,
                    'done'        => true,
                    'empty'       => $empty,
                    'next_cursor' => 0
                ];
            }

            $nextCursor = isset($result['next_cursor']) ? (int) $result['next_cursor'] : 0;

            if ($nextCursor <= 0 || $nextCursor === $cursor) {
                return [
                    'status'      => 300,
                    'processed'   => $processed,
                    'updated'     => $updated,
                    'done'        => false,
                    'empty'       => $empty,
                    'next_cursor' => $nextCursor,
                    'message'     => 'Cron sinkronizacija statusa narudžbi je prekinuta zbog neispravnog kursora.'
                ];
            }

            $cursor = $nextCursor;
        } while (true);
    }


    /**
     * @param Carbon $cutoff
     * @param array  $excludedLuceedStatuses
     *
     * @return array
     */
    private function syncLegacyOrderStatusBatch(Carbon $cutoff, array $excludedLuceedStatuses = []): array
    {
        $this->prepareLongRunningOrderStatusSync();

        $oldestDate = $this->getLegacyOldestOrderDate($cutoff, $excludedLuceedStatuses);

        if ( ! $oldestDate) {
            return [
                'status'            => 200,
                'processed'         => 0,
                'updated'           => 0,
                'done'              => true,
                'empty'             => true,
                'next_legacy_start' => ''
            ];
        }

        $windowEnd = isset($this->request->get['legacy_end']) && $this->request->get['legacy_end']
            ? Carbon::parse($this->request->get['legacy_end'])
            : $cutoff->copy();

        if ($windowEnd->greaterThan($cutoff)) {
            $windowEnd = $cutoff->copy();
        }

        $legacyCursor = isset($this->request->get['legacy_cursor']) ? (int) $this->request->get['legacy_cursor'] : 0;
        $windowDays  = 365;
        $batchSize  = $this->getOrderStatusBatchSize();
        $batch      = collect();
        $windowStart = null;

        while ($windowEnd->greaterThan($oldestDate) && $batch->isEmpty()) {
            $windowStart = $windowEnd->copy()->subDays($windowDays);

            if ($windowStart->lessThan($oldestDate)) {
                $windowStart = $oldestDate->copy();
            }

            $batch = $this->getLegacyOrderStatusWindow($windowStart, $windowEnd, $excludedLuceedStatuses, $legacyCursor, $batchSize);

            if ($batch->isEmpty()) {
                $windowEnd = $windowStart->copy();
                $legacyCursor = 0;
            }
        }

        if ($batch->isEmpty() || ! $windowStart) {
            return [
                'status'          => 200,
                'processed'       => 0,
                'updated'         => 0,
                'done'            => true,
                'empty'           => true,
                'next_legacy_start' => '',
                'next_legacy_end'   => ''
            ];
        }

        $loc              = new LOC_Order();
        $statuses         = $this->getSyncOrderStatusesCollection($excludedLuceedStatuses);
        $statusesById     = $statuses->keyBy('order_status_id');
        $statusesByLuceed = $statuses->keyBy('luceed_status_id');
        $statusList       = $loc->collectStatuses($statuses->all());
        $updates          = [];

        foreach ([false, true] as $b2b) {
            $updates = $this->collectOrderStatusUpdates(
                $updates,
                $batch,
                $statusesById,
                $statusesByLuceed,
                $statusList,
                $windowStart->format('d.m.Y'),
                $b2b,
                false
            );
        }

        $updated = $this->applyOrderStatusUpdates(array_values($updates), false);
        $windowFinished = $batch->count() < $batchSize;
        $done           = $windowFinished && $windowStart->lessThanOrEqualTo($oldestDate);
        $nextLegacyEnd  = $done ? '' : ($windowFinished ? $windowStart->toDateTimeString() : $windowEnd->toDateTimeString());
        $nextLegacyCursor = $done ? 0 : ($windowFinished ? 0 : (int) $batch->last()->order_id);

        return [
            'status'             => 200,
            'processed'          => $batch->count(),
            'updated'            => $updated,
            'done'               => $done,
            'empty'              => false,
            'next_legacy_start'  => $nextLegacyEnd,
            'next_legacy_end'    => $nextLegacyEnd,
            'next_legacy_cursor' => $nextLegacyCursor
        ];
    }


    /**
     * @return void
     */
    private function prepareLongRunningOrderStatusSync(): void
    {
        ignore_user_abort(true);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
    }


    /**
     * @return array
     */
    private function getExcludedLuceedStatuses(): array
    {
        if ( ! isset($this->request->get['skip_luceed_statuses'])) {
            return [];
        }

        return array_values(array_filter(array_unique(array_map('trim', explode(',', (string) $this->request->get['skip_luceed_statuses'])))));
    }


    /**
     * @return int
     */
    private function getOrderStatusBatchSize(): int
    {
        return 200;
    }


    /**
     * @param array $luceedStatuses
     *
     * @return array
     */
    private function getOrderStatusIdsByLuceedStatuses(array $luceedStatuses): array
    {
        if (empty($luceedStatuses)) {
            return [];
        }

        return OrderStatus::query()
                          ->whereIn('luceed_status_id', $luceedStatuses)
                          ->pluck('order_status_id')
                          ->all();
    }


    /**
     * @param array $excludedLuceedStatuses
     *
     * @return \Illuminate\Support\Collection
     */
    private function getSyncOrderStatusesCollection(array $excludedLuceedStatuses = [])
    {
        $query = OrderStatus::query()->where('luceed_status_id', '!=', '');

        if ($excludedLuceedStatuses) {
            $query->whereNotIn('luceed_status_id', $excludedLuceedStatuses);
        }

        return $query->get();
    }


    /**
     * @param Carbon $cutoff
     * @param bool   $legacy
     * @param int    $cursor
     * @param int    $limit
     * @param array  $excludedLuceedStatuses
     *
     * @return \Illuminate\Support\Collection
     */
    private function getOrderStatusBatch(Carbon $cutoff, bool $legacy, int $cursor, int $limit, array $excludedLuceedStatuses = [])
    {
        $query = Order::query()
                      ->select('order_id', 'luceed_uid', 'email', 'payment_code', 'order_status_id', 'date_added')
                      ->where('order_status_id', '!=', 0)
                      ->whereNotNull('luceed_uid')
                      ->where('luceed_uid', '!=', '');

        if ($legacy) {
            $query->where('date_added', '<', $cutoff->toDateTimeString());

            if ($excludedLuceedStatuses) {
                $excludedOrderStatusIds = $this->getOrderStatusIdsByLuceedStatuses($excludedLuceedStatuses);

                if ($excludedOrderStatusIds) {
                    $query->whereNotIn('order_status_id', $excludedOrderStatusIds);
                }
            }
        } else {
            $query->where('date_added', '>=', $cutoff->toDateTimeString());
        }

        if ($cursor > 0) {
            $query->where('order_id', '<', $cursor);
        }

        return $query->orderByDesc('order_id')
                     ->limit($limit)
                     ->get();
    }


    /**
     * @param Carbon $cutoff
     * @param array  $excludedLuceedStatuses
     *
     * @return Carbon|null
     */
    private function getLegacyOldestOrderDate(Carbon $cutoff, array $excludedLuceedStatuses = [])
    {
        $query = Order::query()
                      ->where('order_status_id', '!=', 0)
                      ->whereNotNull('luceed_uid')
                      ->where('luceed_uid', '!=', '')
                      ->where('date_added', '<', $cutoff->toDateTimeString());

        if ($excludedLuceedStatuses) {
            $excludedOrderStatusIds = $this->getOrderStatusIdsByLuceedStatuses($excludedLuceedStatuses);

            if ($excludedOrderStatusIds) {
                $query->whereNotIn('order_status_id', $excludedOrderStatusIds);
            }
        }

        $oldestDate = $query->min('date_added');

        return $oldestDate ? Carbon::make($oldestDate) : null;
    }


    /**
     * @param Carbon $windowStart
     * @param Carbon $windowEnd
     * @param array  $excludedLuceedStatuses
     * @param int    $cursor
     * @param int    $limit
     *
     * @return \Illuminate\Support\Collection
     */
    private function getLegacyOrderStatusWindow(Carbon $windowStart, Carbon $windowEnd, array $excludedLuceedStatuses = [], int $cursor = 0, int $limit = 200)
    {
        $query = Order::query()
                      ->select('order_id', 'luceed_uid', 'email', 'payment_code', 'order_status_id', 'date_added')
                      ->where('order_status_id', '!=', 0)
                      ->whereNotNull('luceed_uid')
                      ->where('luceed_uid', '!=', '')
                      ->where('date_added', '>=', $windowStart->toDateTimeString())
                      ->where('date_added', '<', $windowEnd->toDateTimeString())
                      ->orderByDesc('order_id');

        if ($excludedLuceedStatuses) {
            $excludedOrderStatusIds = $this->getOrderStatusIdsByLuceedStatuses($excludedLuceedStatuses);

            if ($excludedOrderStatusIds) {
                $query->whereNotIn('order_status_id', $excludedOrderStatusIds);
            }
        }

        if ($cursor > 0) {
            $query->where('order_id', '<', $cursor);
        }

        return $query->limit($limit)->get();
    }


    /**
     * @param array                         $updates
     * @param \Illuminate\Support\Collection $batch
     * @param \Illuminate\Support\Collection $statusesById
     * @param \Illuminate\Support\Collection $statusesByLuceed
     * @param string                        $statusList
     * @param string                        $startDate
     * @param bool                          $b2b
     * @param bool                          $withMail
     *
     * @return array
     */
    private function collectOrderStatusUpdates(
        array $updates,
        $batch,
        $statusesById,
        $statusesByLuceed,
        string $statusList,
        string $startDate,
        bool $b2b = false,
        bool $withMail = true
    ): array
    {
        $luceedOrders = $this->extractLuceedOrders(
            LuceedOrder::get($statusList, $startDate, $b2b)
        );

        if ($luceedOrders->isEmpty()) {
            return $updates;
        }

        $luceedOrdersByUid = $luceedOrders->keyBy('nalog_prodaje_uid');

        foreach ($batch as $order) {
            $luceedOrder = $luceedOrdersByUid->get($order->luceed_uid);

            if ( ! $luceedOrder || ! isset($luceedOrder->status)) {
                continue;
            }

            $oldStatus = $statusesById->get($order->order_status_id);
            $newStatus = $statusesByLuceed->get((string) $luceedOrder->status);

            if ( ! $oldStatus || ! $newStatus) {
                continue;
            }

            if ((string) $luceedOrder->status === (string) $oldStatus->luceed_status_id) {
                continue;
            }

            $update = [
                'order_id'     => (int) $order->order_id,
                'oc_status_to' => (int) $newStatus->order_status_id,
                'email'        => $order->email
            ];

            if ($withMail) {
                $update['payment']     = $order->payment_code;
                $update['status_from'] = (string) $oldStatus->luceed_status_id;
                $update['status_to']   = (string) $luceedOrder->status;
                $mailKey               = $this->resolveOrderStatusMail($update);

                if ($mailKey) {
                    $update['mail'] = $mailKey;
                }
            }

            $updates[$order->order_id] = $update;
        }

        return $updates;
    }


    /**
     * @param mixed $response
     *
     * @return \Illuminate\Support\Collection
     */
    private function extractLuceedOrders($response)
    {
        $json = json_decode($response);

        if ( ! isset($json->result[0]->nalozi_prodaje)) {
            return collect();
        }

        return collect($json->result[0]->nalozi_prodaje)->filter(function($order) {
            return isset($order->nalog_prodaje_uid);
        });
    }


    /**
     * @param array $update
     *
     * @return mixed|null
     */
    private function resolveOrderStatusMail(array $update)
    {
        $items = agconf('mail.' . $update['payment']);

        if ( ! is_array($items)) {
            return null;
        }

        foreach ($items as $key => $item) {
            if ( ! $key || ! isset($item['from'], $item['to'])) {
                continue;
            }

            if ($update['status_from'] == $item['from'] && $update['status_to'] == $item['to']) {
                return $key;
            }
        }

        return null;
    }


    /**
     * @param array $updates
     * @param bool  $notify
     *
     * @return int
     */
    private function applyOrderStatusUpdates(array $updates, bool $notify): int
    {
        if (empty($updates)) {
            return 0;
        }

        $updated = 0;
        $now     = Carbon::now()->toDateTimeString();

        foreach (array_chunk($updates, $this->getOrderStatusBatchSize()) as $chunk) {
            $cases         = [];
            $historyValues = [];
            $orderIds      = [];

            foreach ($chunk as $item) {
                $orderId  = (int) $item['order_id'];
                $statusId = (int) $item['oc_status_to'];

                $orderIds[]      = $orderId;
                $cases[]         = 'WHEN ' . $orderId . ' THEN ' . $statusId;
                $historyValues[] = '(' . $orderId . ', ' . $statusId . ', ' . (int) $notify . ", '', '" . $this->db->escape($now) . "')";
            }

            $this->db->query(
                "UPDATE `" . DB_PREFIX . "order`
                 SET order_status_id = CASE order_id " . implode(' ', $cases) . " END,
                     order_status_changed = '" . $this->db->escape($now) . "',
                     date_modified = '" . $this->db->escape($now) . "'
                 WHERE order_id IN (" . implode(',', $orderIds) . ')'
            );

            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "order_history` (order_id, order_status_id, notify, comment, date_added)
                 VALUES " . implode(',', $historyValues)
            );

            $updated += count($chunk);
        }

        return $updated;
    }


    /**
     * @return mixed
     */
    public function updateB2BOrderStatuses()
    {
        $loc = new LOC_Order();

        $loc->setOrders(
            LuceedOrder::get(
                $loc->collectStatuses(),
                Carbon::now()->subWeek(2)->format('d.m.Y'), //agconf('import.orders.from_date')
                true
            )
        );

        $updated = $loc->sort()->updateStatuses();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $updated;
    }


    /**
     * @return mixed
     */
    public function checkOrderStatusDuration()
    {
        try {
            $this->prepareLongRunningOrderStatusSync();

            $cursor    = isset($this->request->get['cursor']) ? (int) $this->request->get['cursor'] : 0;
            $batchSize = $this->getOrderStatusBatchSize();
            $batch     = $this->getDurationStatusBatch($cursor, $batchSize);

            if ($batch->isEmpty()) {
                return $this->output([
                    'status'      => 200,
                    'processed'   => 0,
                    'updated'     => 0,
                    'done'        => true,
                    'empty'       => ($cursor === 0),
                    'next_cursor' => 0
                ]);
            }

            $notifications = $this->collectDurationStatusNotifications($batch);

            foreach ($notifications as $order) {
                $this->sendMail($order);
            }

            return $this->output([
                'status'      => 200,
                'processed'   => $batch->count(),
                'updated'     => count($notifications),
                'done'        => ($batch->count() < $batchSize),
                'empty'       => false,
                'next_cursor' => ($batch->count() < $batchSize) ? 0 : (int) $batch->last()->order_id
            ]);
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_order_duration_sync_error');
            Log::store($e->getTraceAsString(), 'luceed_order_duration_sync_error');

            return $this->output([
                'status'  => 300,
                'message' => 'Greška kod provjere trajanja statusa: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * @param int $cursor
     * @param int $limit
     *
     * @return \Illuminate\Support\Collection
     */
    private function getDurationStatusBatch(int $cursor, int $limit)
    {
        $query = Order::query()
                      ->select('order_id', 'email', 'payment_code', 'order_status_id', 'order_status_changed')
                      ->where('order_status_id', '!=', 0)
                      ->whereNotNull('order_status_changed');

        if ($cursor > 0) {
            $query->where('order_id', '<', $cursor);
        }

        return $query->orderByDesc('order_id')
                     ->limit($limit)
                     ->get();
    }


    /**
     * @param \Illuminate\Support\Collection $batch
     *
     * @return array
     */
    private function collectDurationStatusNotifications($batch): array
    {
        $notifications = [];
        $statuses      = OrderStatus::query()->where('luceed_status_id', '!=', '')->get()->keyBy('order_status_id');
        $now           = Carbon::now();

        foreach ($batch as $order) {
            $status = $statuses->get($order->order_status_id);

            if ( ! $status || ! $order->order_status_changed) {
                continue;
            }

            $changedAt  = Carbon::make($order->order_status_changed);
            $longerThen = null;

            if ($changedAt < $now->copy()->subHour(168) && $changedAt > $now->copy()->subHour(72) && $status->luceed_status_id == '11') {
                $longerThen = 168;
            } elseif ($changedAt < $now->copy()->subHour(72) && $changedAt > $now->copy()->subHour(48) && in_array($status->luceed_status_id, ['02', '05'])) {
                $longerThen = 72;
            } elseif ($changedAt < $now->copy()->subHour(48) && $changedAt > $now->copy()->subHour(24) && in_array($status->luceed_status_id, ['12', '01'])) {
                $longerThen = 48;
            } elseif ($changedAt < $now->copy()->subHour(24) && $changedAt > $now && in_array($status->luceed_status_id, ['02', '05', '12', '01'])) {
                $longerThen = 24;
            }

            if ( ! $longerThen) {
                continue;
            }

            $mailKey = $this->resolveDurationStatusMail($order->payment_code, (string) $status->luceed_status_id, $longerThen);

            if ( ! $mailKey) {
                continue;
            }

            $notifications[] = [
                'order_id' => (int) $order->order_id,
                'email'    => $order->email,
                'mail'     => $mailKey
            ];
        }

        return $notifications;
    }


    /**
     * @param string $payment
     * @param string $status
     * @param int    $longerThen
     *
     * @return int|string|null
     */
    private function resolveDurationStatusMail(string $payment, string $status, int $longerThen)
    {
        $items = agconf('mail.' . $payment);

        if ( ! is_array($items) || ! isset($items[0]) || ! is_array($items[0])) {
            return null;
        }

        foreach ($items[0] as $key => $hours) {
            if ($status != $key || ! is_array($hours)) {
                continue;
            }

            foreach ($hours as $hour => $mail) {
                if ((int) $hour === $longerThen) {
                    return $mail;
                }
            }
        }

        return null;
    }
    
    
    /**
     * @return void
     */
    public function checkRevisionTable()
    {
        $prods = LuceedProductForRevision::query()->get();
        foreach ($prods as $prod) {
            $act = Product::query()->where('sku', $prod->sku)->first();
            
            if ($act) {
                if ($act->status == 0) {
                    $prod->delete();
                }
            }
        }

        return $this->response('200', 'success');
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
            $email             = $this->loadEmails($order['mail']);
            $data              = Order::where('order_id', $order['order_id'])->with('products', 'totals')->first()->toArray();
            $data['mail_text'] = sprintf($email['text'], $order['order_id']);

            for ($i = 0; $i < count($data['products']); $i++) {
                $data['products'][$i]['image'] = HTTPS_CATALOG . 'image/' . Product::where('product_id', $data['products'][$i]['product_id'])->pluck('image')->first();
            }

            $data['mail_logo']          = HTTPS_CATALOG . 'image/chipoteka-hd.png';
            $data['mail_title']         = sprintf($email['subject'], $order['order_id']);
            $data['mail_data']          = $email['data'];
            $nhs_no                     = $order['order_id'] . date("ym");
            $data['mail_poziv_na_broj'] = $nhs_no . $this->mod11INI($nhs_no);

            $data['oib']    = isset($order['custom_field'][1]) ? $order['custom_field'][1] : null;
            $data['tvrtka'] = isset($order['custom_field'][2]) ? $order['custom_field'][2] : null;

            $data['b2b'] = $order['mail'];

            $lc     = new \Agmedia\LuceedOpencartWrapper\Models\LOC_Document();
            $is_b2b = ($data['oib'] != '' && $data['customer_group_id'] > 2) ? true : false;

            $data['is_b2b']       = $is_b2b;
            $data['b2b_products'] = [];

            if (isset($data['luceed_uid']) && $data['luceed_uid']) {
                $data['b2b_products'] = $lc->setDocument($data['luceed_uid'], $is_b2b)->sortProducts($data['products']);
            }

            $mail                = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter     = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
            $mail->setTo($order['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(sprintf($email['subject'], $order['order_id']));
            $mail->setHtml($this->load->view('mail/mail', $data));
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
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/emails.json'), true);

        if ($file) {
            if ($key) {
                return collect($file[$key]);
            }

            return collect($file);
        }

        return [];
    }


    /**
     * @throws Exception
     */
    private function sendRevisionMail()
    {
        $data             = [];
        $data['products'] = LuceedProductForRevision::query()->select('sku', 'name', 'data', 'has_image', 'has_description')->get()->toArray();

        $mail                = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter     = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo('pmovi@chipoteka.hr');
        $mail->addBCC('z-el.tomic@chipoteka.hr');
        $mail->addBCC('z-el.radas@chipoteka.hr');
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject('Proizvodi za reviziju... ' . Carbon::now()->format('d.m.Y'));
        $mail->setHtml($this->load->view('mail/updatemail', $data));
        $mail->send();

        //dodatni mail za reviziju
        $mail                = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter     = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
        $mail->setTo('z-el.tomic@chipoteka.hr');
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject('Proizvodi za reviziju... ' . Carbon::now()->format('d.m.Y'));
        $mail->setHtml($this->load->view('mail/updatemail', $data));
        $mail->send();
    }


    /**
     * @param $product
     *
     * @return array
     */
    private function resolveOldProductData($product): array
    {
        $this->load->model('catalog/product');

        $data                     = [];
        $data['product_discount'] = $this->model_catalog_product->getProductDiscounts($product['product_id']);
        $data['product_special']  = $this->model_catalog_product->getProductSpecials($product['product_id']);
        $data['product_download'] = $this->model_catalog_product->getProductDownloads($product['product_id']);
        $data['product_filter']   = $this->model_catalog_product->getProductFilters($product['product_id']);
        $data['product_related']  = $this->model_catalog_product->getProductRelated($product['product_id']);
        $data['product_reward']   = $this->model_catalog_product->getProductRewards($product['product_id']);

        return $data;
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


    /**
     * @param string $nb
     *
     * @return float|int|mixed
     */
    public function mod11INI(string $nb)
    {
        $i = 0;
        $v = 0;
        $p = 2;
        $c = ' ';

        for ($i = strlen($nb); $i >= 1; $i--) {
            $c = substr($nb, $i - 1, 1);

            if ('0' <= $c && $c <= '9' && $v >= 0) {
                $v = $v + $p * $c;
                $p = $p + 1;
            } else {
                $v = -1;
            }
        }

        if ($v >= 0) {
            $v = 11 - ($v % 11);

            if ($v > 9) {
                $v = 0;
            }
        }

        return $v;
    }

}
