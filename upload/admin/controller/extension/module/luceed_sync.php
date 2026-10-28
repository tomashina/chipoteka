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
use Agmedia\LuceedOpencartWrapper\Models\LOC_ProductStatus;
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
    private const KORISNIK_PARTNER_SETTING_KEY = 'luceed_sync_korisnik_partner_ids';
    private const KORISNIK_PARTNER_UID_SUFFIX = '1063';
    private const KORISNIK_PARTNER_SEED = [
        '001' => '20761',
        '002' => '15319',
        '003' => '12271',
        '004' => '24939',
        '005' => '34976',
        '006' => '20847',
        '007' => '25041',
        '011' => '82655',
        '012' => '123062',
        '013' => '329822',
    ];

    const DURATION_MAIL_ACTIVATED_AT = '2026-07-17 13:35:10';
    const ORDER_STATUS_REQUEST_TIMEOUT_SECONDS = 60;
    const LUCEED_PRODUCT_SYNC_LOCK = 'chipoteka_luceed_product_sync';
    // Product and stock jobs both mutate product quantities, so they must never overlap.
    const LUCEED_STOCK_SYNC_LOCK = 'chipoteka_luceed_product_sync';

    private $error = array();
    private $snapshot_skipped_products = 0;


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
        $this->load->model('extension/module/luceed_store');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['revision_products']   = LuceedProductForRevision::with('product')->get();
        $data['price_zero_products'] = Product::where('price', 0)->get();
        $data['rev_ids']             = $data['revision_products']->pluck('sku')->take(200)->flatten();
        $last_rev                    = LuceedProductForRevisionData::orderBy('last_revision_date', 'desc')->first();

        $data['last_rev'] = 'Nepoznato';
        if ($last_rev) {
            $data['last_rev'] = Carbon::make($last_rev->last_revision_date)->diffForHumans();
        }

        $data['store_table_installed'] = $this->model_extension_module_luceed_store->isInstalled();
        $data['store_audit'] = $this->buildStoreCrudData();
        $data['pickup_stores_enabled'] = (bool) $this->config->get('luceed_sync_pickup_stores_enabled');

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
     * Install the editable store table and seed it from the current env/JSON sources.
     */
    public function installStoreTable()
    {
        if ( ! $this->hasStoreModifyPermission()) {
            return $this->output(['status' => 403, 'message' => 'Nemate dozvolu za instalaciju tablice poslovnica.']);
        }

        $this->load->model('extension/module/luceed_store');
        $this->model_extension_module_luceed_store->install();

        $warehouse_config = agconf('import.warehouse');
        $working_hours = agconf('poslovnice_radno_vrijeme');
        $partner_map = agconf('luceed.pickup_korisnik_partner_uid');
        $inserted = $this->model_extension_module_luceed_store->seedLegacy(
            is_array($warehouse_config) ? $warehouse_config : [],
            is_array($working_hours) ? $working_hours : [],
            is_array($partner_map) ? $partner_map : [],
            $this->loadLegacyWarehouseRows(is_array($warehouse_config) ? ($warehouse_config['json'] ?? '') : '')
        );

        return $this->output([
            'status' => 200,
            'message' => 'Tablica poslovnica je instalirana. Uvezeno zapisa: ' . $inserted . '.'
        ]);
    }


    public function getStore()
    {
        $this->load->model('extension/module/luceed_store');

        if ( ! $this->model_extension_module_luceed_store->isInstalled()) {
            return $this->output(['status' => 300, 'message' => 'Tablica poslovnica nije instalirana.']);
        }

        $store = $this->model_extension_module_luceed_store->getStore((int) ($this->request->get['store_id'] ?? 0));

        if ( ! $store) {
            return $this->output(['status' => 300, 'message' => 'Poslovnica nije pronađena.']);
        }

        return $this->output(['status' => 200, 'store' => $store]);
    }


    public function saveStore()
    {
        if ( ! $this->hasStoreModifyPermission()) {
            return $this->output(['status' => 403, 'message' => 'Nemate dozvolu za izmjenu poslovnica.']);
        }

        $this->load->model('extension/module/luceed_store');

        if ( ! $this->model_extension_module_luceed_store->isInstalled()) {
            return $this->output(['status' => 300, 'message' => 'Tablica poslovnica nije instalirana.']);
        }

        $store_id = (int) ($this->request->post['store_id'] ?? 0);
        $errors = $this->model_extension_module_luceed_store->validate($this->request->post, $store_id);

        if ($errors) {
            return $this->output(['status' => 300, 'message' => implode('<br>', $errors), 'errors' => $errors]);
        }

        if ($store_id) {
            if ( ! $this->model_extension_module_luceed_store->getStore($store_id)) {
                return $this->output(['status' => 300, 'message' => 'Poslovnica nije pronađena.']);
            }

            $this->model_extension_module_luceed_store->editStore($store_id, $this->request->post);
            $message = 'Poslovnica je uspješno uređena.';
        } else {
            $store_id = $this->model_extension_module_luceed_store->addStore($this->request->post);
            $message = 'Poslovnica je uspješno dodana.';
        }

        return $this->output(['status' => 200, 'store_id' => $store_id, 'message' => $message]);
    }


    private function buildStoreCrudData(): array
    {
        if ( ! $this->model_extension_module_luceed_store->isInstalled()) {
            $warehouse_config = agconf('import.warehouse');
            $legacy_rows = $this->loadLegacyWarehouseRows(is_array($warehouse_config) ? ($warehouse_config['json'] ?? '') : '');

            return [
                'installed' => false,
                'rows' => [],
                'warnings' => ['Tablica poslovnica nije instalirana. Do instalacije se koristi postojeći env/JSON izvor.'],
                'stats' => ['total' => 0, 'active' => 0, 'pickup' => 0, 'legacy' => count($legacy_rows)],
            ];
        }

        $rows = $this->model_extension_module_luceed_store->getStores();

        return [
            'installed' => true,
            'rows' => $rows,
            'warnings' => $this->model_extension_module_luceed_store->getWarnings($rows),
            'stats' => [
                'total' => count($rows),
                'active' => count(array_filter($rows, static function ($row) { return (int) $row['status'] === 1; })),
                'pickup' => count(array_filter($rows, static function ($row) { return (int) $row['status'] === 1 && (int) $row['is_pickup'] === 1; })),
                'legacy' => 0,
            ],
        ];
    }


    private function loadLegacyWarehouseRows($primary_path = ''): array
    {
        $sources = [];

        if (is_string($primary_path) && $primary_path !== '') {
            $sources[] = $primary_path;
        }

        $sources[] = DIR_STORAGE . 'upload/assets/skladista.json';
        $sources[] = DIR_STORAGE . 'upload/luceed_json/skladista_lista.json';

        foreach (array_unique($sources) as $path) {
            if ( ! is_file($path)) {
                continue;
            }

            $decoded = json_decode(file_get_contents($path), true);
            if ( ! is_array($decoded)) {
                continue;
            }

            if (isset($decoded['result'][0]['skladista']) && is_array($decoded['result'][0]['skladista'])) {
                return $decoded['result'][0]['skladista'];
            }

            return array_values($decoded);
        }

        return [];
    }


    private function hasStoreModifyPermission(): bool
    {
        return $this->hasModifyPermission();
    }


    /**
     * Toggle checkout display of pickup stores list.
     *
     * @return void
     */
    public function savePickupStoresSwitch()
    {
        if ( ! $this->hasModifyPermission()) {
            $this->output([
                'status' => 403,
                'message' => 'Nemate dozvolu za izmjenu postavki.'
            ]);

            return;
        }

        $enabled = ! empty($this->request->post['enabled']) ? 1 : 0;
        $this->saveSettingForAllStores('luceed_sync_pickup_stores_enabled', (string) (int) $enabled, 0);

        $this->config->set('luceed_sync_pickup_stores_enabled', $enabled);

        $this->output([
            'status'  => 200,
            'enabled' => $enabled,
            'message' => $enabled ? 'Prikaz poslovnica u checkoutu je uključen.' : 'Prikaz poslovnica u checkoutu je isključen.'
        ]);
    }


    /**
     * Save mapping: skladiste -> korisnik__partner ID.
     *
     * @return void
     */
    public function savePickupPartnerIds()
    {
        if ( ! $this->hasModifyPermission()) {
            $this->output([
                'status' => 403,
                'message' => 'Nemate dozvolu za izmjenu postavki.'
            ]);

            return;
        }

        $mapping = $this->request->post['mapping'] ?? [];

        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            $mapping = is_array($decoded) ? $decoded : [];
        }

        if ( ! is_array($mapping)) {
            $this->output([
                'status'  => 300,
                'message' => 'Neispravan format mape poslovnica.'
            ]);

            return;
        }

        $allowed_codes = $this->getEditableStoreCodes();
        $validation_errors = [];
        $normalized = $this->normalizeKorisnikPartnerMap($mapping, $allowed_codes, $validation_errors);

        if ($validation_errors) {
            $this->output([
                'status'  => 300,
                'message' => 'ID korisnik__partner mora biti numerički. Problem kod: ' . implode(', ', array_unique($validation_errors))
            ]);

            return;
        }

        $this->saveKorisnikPartnerMap($normalized);
        $this->config->set(self::KORISNIK_PARTNER_SETTING_KEY, json_encode($normalized));

        $this->output([
            'status'  => 200,
            'message' => 'ID-evi korisnik__partner su uspješno spremljeni.'
        ]);
    }


    /**
     * Build one place where admin can see all store related dependencies.
     *
     * @return array
     */
    private function buildStoreAuditData(): array
    {
        $warehouse_config = agconf('import.warehouse');
        $working_hours    = agconf('poslovnice_radno_vrijeme');

        if ( ! is_array($warehouse_config)) {
            $warehouse_config = [];
        }

        if ( ! is_array($working_hours)) {
            $working_hours = [];
        }

        $included_codes   = $this->normalizeStoreCodes($warehouse_config['included'] ?? []);
        $default_codes    = $this->normalizeStoreCodes($warehouse_config['default'] ?? []);
        $view_codes       = $this->normalizeStoreCodes($warehouse_config['availability_view'] ?? []);
        $store_codes      = $this->normalizeStoreCodes($warehouse_config['stores'] ?? []);
        $pickup_codes     = $this->normalizeStoreCodes($warehouse_config['pickup'] ?? []);
        $hours_codes      = $this->normalizeStoreCodes(array_keys($working_hours));

        $required_codes = $this->uniqueStoreCodes(array_merge(
            $included_codes,
            $default_codes,
            $view_codes,
            $store_codes,
            $pickup_codes,
            $hours_codes
        ));

        $json_data = $this->loadWarehouseJsonData($warehouse_config['json'] ?? '');
        $json_path = $json_data['source'];
        $json_rows = $json_data['stores'];
        $korisnik_partner_map = $this->getKorisnikPartnerMap($required_codes, array_keys($json_rows));

        $rows = [];

        foreach ($required_codes as $code) {
            $store = $json_rows[$code] ?? [];
            $korisnik_partner_id = $korisnik_partner_map[$code] ?? '';
            $korisnik_partner_uid_preview = $korisnik_partner_id !== ''
                ? $korisnik_partner_id . '-' . self::KORISNIK_PARTNER_UID_SUFFIX
                : '-';

            $rows[] = [
                'code'               => $code,
                'name'               => $store['naziv'] ?? '-',
                'address'            => $store['adresa'] ?? '-',
                'uid'                => $store['skladiste_uid'] ?? '-',
                'pj'                 => $store['pj'] ?? '-',
                'in_json'            => (bool) $store,
                'in_included'        => in_array($code, $included_codes, true),
                'in_default'         => in_array($code, $default_codes, true),
                'in_availability'    => in_array($code, $view_codes, true),
                'in_stores'          => in_array($code, $store_codes, true),
                'in_pickup'          => in_array($code, $pickup_codes, true),
                'working_hours'      => $working_hours[$code] ?? '',
                'korisnik_partner_id' => $korisnik_partner_id,
                'korisnik_partner_uid_preview' => $korisnik_partner_uid_preview,
            ];
        }

        $warnings = [];
        $warnings = array_merge($warnings, $this->missingFrom($default_codes, $included_codes, 'import.warehouse.default', 'import.warehouse.included'));
        $warnings = array_merge($warnings, $this->missingFrom($view_codes, $included_codes, 'import.warehouse.availability_view', 'import.warehouse.included'));
        $warnings = array_merge($warnings, $this->missingFrom($store_codes, $included_codes, 'import.warehouse.stores', 'import.warehouse.included'));
        $warnings = array_merge($warnings, $this->missingFrom($pickup_codes, $included_codes, 'import.warehouse.pickup', 'import.warehouse.included'));

        foreach ($rows as $row) {
            if ( ! $row['in_json']) {
                $warnings[] = 'Sifra ' . $row['code'] . ' je u env mapiranju, ali ne postoji u ' . $json_path . '.';
            }

            if ($row['in_pickup'] && $row['code'] != '101' && $row['working_hours'] == '') {
                $warnings[] = 'Sifra ' . $row['code'] . ' koristi se za pickup, ali nema "poslovnice_radno_vrijeme".';
            }
        }

        return [
            'json_path'  => $json_path,
            'rows'       => $rows,
            'warnings'   => array_values(array_unique($warnings)),
            'special'    => [
                ['code' => '099', 'purpose' => 'LOC_Order web nalog (skladiste/na__skladiste)'],
                ['code' => '101', 'purpose' => 'Fallback centralno skladiste u Availability/LOC_Order'],
            ],
            'korisnik_partner_uid_suffix' => self::KORISNIK_PARTNER_UID_SUFFIX,
            'stats'      => [
                'required'  => count($required_codes),
                'in_json'   => count(array_filter($rows, static function($row) { return $row['in_json']; })),
                'pickup'    => count($pickup_codes),
            ]
        ];
    }


    /**
     * @return array
     */
    private function getEditableStoreCodes(): array
    {
        $warehouse_config = agconf('import.warehouse');

        if ( ! is_array($warehouse_config)) {
            $warehouse_config = [];
        }

        $included_codes = $this->normalizeStoreCodes($warehouse_config['included'] ?? []);
        $default_codes = $this->normalizeStoreCodes($warehouse_config['default'] ?? []);
        $view_codes = $this->normalizeStoreCodes($warehouse_config['availability_view'] ?? []);
        $store_codes = $this->normalizeStoreCodes($warehouse_config['stores'] ?? []);
        $pickup_codes = $this->normalizeStoreCodes($warehouse_config['pickup'] ?? []);

        $json_data = $this->loadWarehouseJsonData($warehouse_config['json'] ?? '');
        $json_codes = $this->normalizeStoreCodes(array_keys($json_data['stores']));

        return $this->uniqueStoreCodes(array_merge(
            $included_codes,
            $default_codes,
            $view_codes,
            $store_codes,
            $pickup_codes,
            $json_codes
        ));
    }


    /**
     * @param array $allowed_codes
     * @param array $seed_codes
     *
     * @return array
     */
    private function getKorisnikPartnerMap(array $allowed_codes = [], array $seed_codes = []): array
    {
        $allowed_codes = $this->normalizeStoreCodes($allowed_codes);
        $seed_codes = $this->normalizeStoreCodes($seed_codes);
        $query = $this->db->query(
            "SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `key` = '" . $this->db->escape(self::KORISNIK_PARTNER_SETTING_KEY) . "' LIMIT 1"
        );

        $raw = '';

        if ($query->num_rows && isset($query->row['value'])) {
            $raw = (string) $query->row['value'];
        }

        $decoded = [];

        if ($raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $decoded = $json;
            }
        }

        $errors = [];
        $normalized = $this->normalizeKorisnikPartnerMap($decoded, $allowed_codes, $errors);

        if (empty($normalized)) {
            $seed = $this->getSeededKorisnikPartnerMap($seed_codes ?: $allowed_codes);

            if ( ! empty($seed)) {
                $this->saveKorisnikPartnerMap($seed);
                $normalized = $seed;
            }
        }

        return $normalized;
    }


    /**
     * @param array $mapping
     * @param array $allowed_codes
     * @param array $errors
     *
     * @return array
     */
    private function normalizeKorisnikPartnerMap(array $mapping, array $allowed_codes = [], array &$errors = []): array
    {
        $normalized = [];
        $allowed_lookup = [];

        foreach ($allowed_codes as $code) {
            $allowed_lookup[$this->normalizeStoreCode($code)] = true;
        }

        foreach ($mapping as $code => $value) {
            $normalized_code = $this->normalizeStoreCode($code);

            if ($normalized_code === '' || ! ctype_digit($normalized_code)) {
                continue;
            }

            if ($allowed_lookup && ! isset($allowed_lookup[$normalized_code])) {
                continue;
            }

            $partner_id = trim((string) $value);

            if ($partner_id === '') {
                continue;
            }

            if ( ! ctype_digit($partner_id)) {
                $errors[] = $normalized_code;
                continue;
            }

            $normalized[$normalized_code] = $partner_id;
        }

        ksort($normalized);

        return $normalized;
    }


    /**
     * @param array $allowed_codes
     *
     * @return array
     */
    private function getSeededKorisnikPartnerMap(array $allowed_codes = []): array
    {
        return $this->normalizeKorisnikPartnerMap(self::KORISNIK_PARTNER_SEED, $allowed_codes);
    }


    /**
     * @param array $mapping
     *
     * @return void
     */
    private function saveKorisnikPartnerMap(array $mapping): void
    {
        $json = json_encode($mapping);

        if ($json === false) {
            $json = '{}';
        }

        $this->saveSettingForAllStores(self::KORISNIK_PARTNER_SETTING_KEY, $json, 0);
        $this->config->set(self::KORISNIK_PARTNER_SETTING_KEY, $json);
    }


    /**
     * @return bool
     */
    private function hasModifyPermission(): bool
    {
        return $this->user->hasPermission('modify', 'extension/module/luceed_sync')
            || $this->user->hasPermission('modify', 'extension/module/luceed_sync_dash');
    }


    /**
     * @return array
     */
    private function getStoreIds(): array
    {
        $store_ids = [0];
        $stores_query = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "store`");

        foreach ($stores_query->rows as $store) {
            $store_ids[] = (int) $store['store_id'];
        }

        return array_values(array_unique($store_ids));
    }


    /**
     * @param string $key
     * @param string $value
     * @param int $serialized
     *
     * @return void
     */
    private function saveSettingForAllStores(string $key, string $value, int $serialized = 0): void
    {
        foreach ($this->getStoreIds() as $store_id) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int) $store_id . "' AND `key` = '" . $this->db->escape($key) . "'");
            $this->db->query(
                "INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '" . (int) $store_id . "', `code` = 'luceed_sync', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', serialized = '" . (int) $serialized . "'"
            );
        }
    }


    /**
     * @param array $source
     * @param array $target
     * @param string $source_name
     * @param string $target_name
     *
     * @return array
     */
    private function missingFrom(array $source, array $target, string $source_name, string $target_name): array
    {
        $missing = array_diff($source, $target);
        $warnings = [];

        foreach ($missing as $code) {
            $warnings[] = 'Sifra ' . $code . ' postoji u "' . $source_name . '", ali ne postoji u "' . $target_name . '".';
        }

        return $warnings;
    }


    /**
     * @param mixed $path
     *
     * @return array
     */
    private function loadWarehouseJsonData($path): array
    {
        $sources = [];

        if (is_string($path) && $path != '') {
            $sources[] = $path;
        }

        $sources[] = DIR_STORAGE . 'upload/assets/skladista.json';
        $sources[] = DIR_STORAGE . 'upload/luceed_json/skladista_lista.json';

        $sources = array_values(array_unique($sources));

        foreach ($sources as $source) {
            $content = $this->loadJsonFile($source);

            if (empty($content)) {
                continue;
            }

            $list = $this->extractStoresList($content);

            if ($list) {
                $indexed = [];

                foreach ($list as $item) {
                    if ( ! is_array($item) || ! isset($item['skladiste'])) {
                        continue;
                    }

                    $code = $this->normalizeStoreCode($item['skladiste']);
                    $item['skladiste'] = $code;
                    $indexed[$code] = $item;
                }

                if ($indexed) {
                    return [
                        'source' => $source,
                        'stores' => $indexed
                    ];
                }
            }
        }

        return [
            'source' => DIR_STORAGE . 'upload/assets/skladista.json',
            'stores' => []
        ];
    }


    /**
     * @param string $path
     *
     * @return array
     */
    private function loadJsonFile(string $path): array
    {
        if ($path == '' || ! is_file($path)) {
            return [];
        }

        $decoded = json_decode(file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }


    /**
     * @param array $data
     *
     * @return array
     */
    private function extractStoresList(array $data): array
    {
        if (isset($data['result'][0]['skladista']) && is_array($data['result'][0]['skladista'])) {
            return $data['result'][0]['skladista'];
        }

        return $data;
    }


    /**
     * @param array $codes
     *
     * @return array
     */
    private function normalizeStoreCodes(array $codes): array
    {
        $response = [];

        foreach ($codes as $code) {
            $response[] = $this->normalizeStoreCode($code);
        }

        return $this->uniqueStoreCodes($response);
    }


    /**
     * @param mixed $code
     *
     * @return string
     */
    private function normalizeStoreCode($code): string
    {
        $code = (string) $code;

        if ($code !== '' && ctype_digit($code) && strlen($code) < 3) {
            return str_pad($code, 3, '0', STR_PAD_LEFT);
        }

        return $code;
    }


    /**
     * @param array $codes
     *
     * @return array
     */
    private function uniqueStoreCodes(array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes, static function($code) {
            return is_string($code) && $code !== '';
        })));

        sort($codes);

        return $codes;
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
        $this->load->model('extension/module/luceed_store');

        if ($this->model_extension_module_luceed_store->isInstalled()) {
            return $this->output([
                'status' => 300,
                'message' => 'Luceed import skladišta je isključen jer je instalirana tablica poslovnica.'
            ]);
        }

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
        return $this->runProductSync(function () {
            return $this->importProductBatch();
        });
    }


    private function importProductBatch()
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
        if ( ! $this->acquireLuceedProductSyncLock()) {
            return $this->output([
                'status'  => 202,
                'message' => 'Luceed product sync is already running.'
            ]);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $this->refreshLuceedProductsCache();

            $count      = 0;
            $_loc_ps    = new LOC_ProductSingle();
            $for_update = LuceedProductForUpdate::all();

            if ($for_update->count()) {
                foreach ($for_update as $item) {
                    if ($_loc_ps->hasForUpdate($item->uid)) {
                        if ( ! isset($_loc_ps->product['naziv'])) {
                            return $this->output($_loc_ps->finishUpdateError());
                        }

                        $product = $this->resolveOldProductData($_loc_ps->product_to_update);
                        // first check known errors
                        $product_for_update                    = $_loc_ps->makeForUpdate($product);
                        $product_for_update['quantity']        = $_loc_ps->product_to_update['quantity'];
                        $product_for_update['stock_status_id'] = $_loc_ps->product_to_update['stock_status_id'];

                        $this->model_catalog_product->editProduct(
                            $_loc_ps->product_to_update['product_id'],
                            $product_for_update
                        );

                        $_loc_ps->finishUpdate();
                        LuceedProductForUpdate::where('uid', $item->uid)->delete();

                        $count++;
                    }
                }
            }

            //$this->updateQuantities();
            $this->finishUpdateProduct();
            $this->reconcileProductStatuses();

            return $this->response($count, 'products');
        } catch (\Throwable $exception) {
            Log::store($exception->getMessage(), 'luceed_product_sync_error');
            $this->response->addHeader('HTTP/1.1 503 Service Unavailable');

            return $this->output([
                'status'  => 503,
                'message' => 'Luceed product sync failed. Check luceed_product_sync_error log.'
            ]);
        } finally {
            $this->releaseLuceedProductSyncLock();
        }
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function importLuceedProducts()
    {
        if ( ! $this->acquireLuceedProductSyncLock()) {
            return $this->output([
                'status'  => 202,
                'message' => 'Luceed product sync is already running.'
            ]);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            return $this->output($this->refreshLuceedProductsCache());
        } catch (\Throwable $exception) {
            Log::store($exception->getMessage(), 'luceed_product_sync_error');
            $this->response->addHeader('HTTP/1.1 503 Service Unavailable');

            return $this->output([
                'status'  => 503,
                'message' => 'Luceed product cache refresh failed. Check luceed_product_sync_error log.'
            ]);
        } finally {
            $this->releaseLuceedProductSyncLock();
        }
    }


    /**
     * @return mixed
     */
    public function updateProduct()
    {
        return $this->runProductSync(function () {
            // Interactive queue processing must follow the same snapshot
            // freshness rules as the automatic stock and product jobs.
            if (!isset($this->request->get['products'])) {
                $this->refreshProductSnapshotForStock();
                $readiness = $this->reconcileProductStatuses();

                if (!empty($readiness['skipped'])) {
                    $this->addServiceUnavailableHeader();
                    return $this->output(['status' => 503, 'message' => 'Ažuriranje čeka svjež provjeren Luceed popis artikala.']);
                }
            }
            return $this->updateProductBatch();
        });
    }


    private function updateProductBatch()
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

            $this->model_catalog_product->editProduct(
                $_loc_ps->product_to_update['product_id'],
                $product_for_update
            );

            $result = $_loc_ps->finishUpdate();
            LuceedProductForUpdate::where('uid', $_loc_ps->product_to_update['luceed_uid'])->delete();

            return $this->output($result);

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
        $limit  = isset($this->request->get['limit']) ? max((int) $this->request->get['limit'], 1) : 100;
        $total  = LuceedProductRecord::query()->count();

        if ( ! $total) {
            return $this->output([
                'status' => 300,
                'message' => 'Luceed cache proizvoda je prazan. Prvo osvježi Luceed cache pa pokreni update redoslijeda atributa.'
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

            $data = collect($data);

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
        return $this->runProductSync(function () {
            $this->refreshProductSnapshotForStock();
            $result = $this->reconcileProductStatuses();

            if (!empty($result['skipped'])) {
                $this->addServiceUnavailableHeader();
                return $this->output(['status' => 503, 'message' => 'Nema svježeg Luceed popisa za provjeru aktivacije.', 'activation' => $result]);
            }

            return $this->response([$result['activated'], $result['deactivated']], 'active');
        });
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
        try {
            $_loc = new LOC_Action(LuceedProduct::getActions());

            $updated = $_loc->collectProductsRegularPrices()
                            ->collectWebPrices()
                            ->update();

            return $this->response($updated, 'update');
        } catch (\Throwable $exception) {
            Log::store($exception->getMessage(), 'luceed_price_sync_error');
            Log::store($exception->getTraceAsString(), 'luceed_price_sync_error');

            $protocol = isset($this->request->server['SERVER_PROTOCOL'])
                ? $this->request->server['SERVER_PROTOCOL']
                : 'HTTP/1.1';

            $this->response->addHeader($protocol . ' 503 Service Unavailable');

            return $this->output([
                'status'  => 503,
                'message' => 'Luceed price sync failed. Check luceed_price_sync_error log.'
            ]);
        }
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateB2BPrices()
    {
        try {
            (new LOC_Manufacturer(LuceedManufacturer::all()))->updateByUids();
        } catch (\Throwable $e) {
            Log::store('updateB2BPrices(manufacturer_uid_sync) ::: ' . $e->getMessage());
        }

		$this->load->model('extension/module/partner_feed');
		$this->model_extension_module_partner_feed->install(false);

		try {
			$result = $this->model_extension_module_partner_feed->syncMappings();
			$updated = $result['total'];

			if (!empty($result['errors'])) {
				Log::store('updateB2BPrices(partial) ::: ' . implode(' | ', $result['errors']));
			}
		} catch (\Throwable $e) {
			Log::store('updateB2BPrices(partner_sync) ::: ' . $e->getMessage());
			$updated = 0;
		}

        return $this->response($updated, 'update');
    }


    /**
     * @return mixed
     */
    public function updateVpcPrices()
    {
        try {
            $_loc = new LOC_Product(LuceedProduct::all());
            $lp   = new LOC_Price();

            $updated = $lp->collectAndStore($_loc->getProducts(), 'vpc');

            return $this->response($updated, 'update');
        } catch (\Throwable $exception) {
            Log::store($exception->getMessage(), 'luceed_vpc_price_sync_error');
            Log::store($exception->getTraceAsString(), 'luceed_vpc_price_sync_error');

            $protocol = isset($this->request->server['SERVER_PROTOCOL'])
                ? $this->request->server['SERVER_PROTOCOL']
                : 'HTTP/1.1';

            $this->response->addHeader($protocol . ' 503 Service Unavailable');

            return $this->output([
                'status'  => 503,
                'message' => 'Luceed VPC price sync failed. Check luceed_vpc_price_sync_error log.'
            ]);
        }
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateQuantities()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if ($response = $this->abortStockSyncWhenLuceedUnavailable('updateQuantities')) {
            return $this->output($response);
        }

        if ( ! $this->acquireLuceedStockSyncLock()) {
            return $this->output([
                'status'  => 409,
                'message' => 'Sinkronizacija zaliha je već u tijeku.'
            ]);
        }

        try {
            $snapshot_warning = $this->refreshProductSnapshotForStock();
            $_loc = new LOC_Stock();

            $_loc->setSkladista(
                LuceedProduct::getWarehouseStock(agconf('import.warehouse.default'))
            )->sort();

            $_loc->setDobavljaci(
                LuceedProduct::getSuplierStock()
            )->sort();

            if ( ! $_loc->isValid()) {
                Log::store('Stock sync skipped because a feed was invalid or unexpectedly small.', 'luceed_stock_sync_invalid_feed');
                $this->addServiceUnavailableHeader();

                return $this->output([
                    'status'  => 503,
                    'message' => 'Luceed feed zaliha je neispravan ili nepotpun. Postojeće količine nisu promijenjene.'
                ]);
            }

            $updated = $_loc->createQuery()->update();

            return $this->stockSyncResponse($updated, $snapshot_warning);
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_stock_sync_error');
            Log::store($e->getTraceAsString(), 'luceed_stock_sync_error');
            $this->addServiceUnavailableHeader();

            return $this->output([
                'status'  => 503,
                'message' => 'Sinkronizacija zaliha nije uspjela. Promjene su poništene.'
            ]);
        } finally {
            $this->releaseLuceedStockSyncLock();
        }
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function updateQuantitiesWithoutSupliers()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if ($response = $this->abortStockSyncWhenLuceedUnavailable('updateQuantitiesWithoutSupliers')) {
            return $this->output($response);
        }

        if ( ! $this->acquireLuceedStockSyncLock()) {
            return $this->output([
                'status'  => 409,
                'message' => 'Sinkronizacija zaliha je već u tijeku.'
            ]);
        }

        try {
            $snapshot_warning = $this->refreshProductSnapshotForStock();
            $_loc = new LOC_Stock();

            $_loc->setSkladista(
                LuceedProduct::getWarehouseStock(agconf('import.warehouse.default'))
            )->sort();

            if ( ! $_loc->isValid()) {
                Log::store('Warehouse stock sync skipped because the feed was invalid or unexpectedly small.', 'luceed_stock_sync_invalid_feed');
                $this->addServiceUnavailableHeader();

                return $this->output([
                    'status'  => 503,
                    'message' => 'Luceed warehouse feed je neispravan ili nepotpun. Postojeće količine nisu promijenjene.'
                ]);
            }

            $updated = $_loc->createQuery()->updateWithoutSupliers();

            return $this->stockSyncResponse($updated, $snapshot_warning);
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_stock_sync_error');
            Log::store($e->getTraceAsString(), 'luceed_stock_sync_error');
            $this->addServiceUnavailableHeader();

            return $this->output([
                'status'  => 503,
                'message' => 'Sinkronizacija warehouse zaliha nije uspjela.'
            ]);
        } finally {
            $this->releaseLuceedStockSyncLock();
        }
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

            $protocol = isset($this->request->server['SERVER_PROTOCOL'])
                ? $this->request->server['SERVER_PROTOCOL']
                : 'HTTP/1.1';

            $this->response->addHeader($protocol . ' 503 Service Unavailable');

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
        $this->prepareLongRunningOrderStatusSync();

        $processed = 0;
        $updated   = 0;
        $cursor    = 0;
        $empty     = true;
        $batchSize = $this->getOrderStatusBatchSize();
        $batch     = $this->getOrderStatusBatch($cutoff, false, $cursor, $batchSize, $excludedLuceedStatuses);

        if ($batch->isEmpty()) {
            return [
                'status'      => 200,
                'processed'   => 0,
                'updated'     => 0,
                'done'        => true,
                'empty'       => true,
                'next_cursor' => 0
            ];
        }

        $loc              = new LOC_Order();
        $statuses         = $this->getSyncOrderStatusesCollection($excludedLuceedStatuses);
        $statusesById     = $statuses->keyBy('order_status_id');
        $statusesByLuceed = $statuses->keyBy('luceed_status_id');
        $statusList       = $loc->collectStatuses($statuses->all());
        $startDate        = $cutoff->format('d.m.Y');
        $luceedOrderSets  = [];

        foreach ([false, true] as $b2b) {
            $luceedOrderSets[] = $this->getLuceedOrdersByUid($statusList, $startDate, $b2b);
        }

        do {
            $updates = [];

            foreach ($luceedOrderSets as $luceedOrdersByUid) {
                $updates = $this->collectOrderStatusUpdatesFromLuceedOrders(
                    $updates,
                    $batch,
                    $statusesById,
                    $statusesByLuceed,
                    $luceedOrdersByUid,
                    true
                );
            }

            $batchUpdated = $this->applyOrderStatusUpdates(array_values($updates), true);

            foreach ($updates as $update) {
                $this->sendMail($update);
            }

            $processed += $batch->count();
            $updated   += $batchUpdated;
            $empty      = false;

            if ($batch->count() < $batchSize) {
                return [
                    'status'      => 200,
                    'processed'   => $processed,
                    'updated'     => $updated,
                    'done'        => true,
                    'empty'       => $empty,
                    'next_cursor' => 0
                ];
            }

            $nextCursor = (int) $batch->last()->order_id;

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
            $batch  = $this->getOrderStatusBatch($cutoff, false, $cursor, $batchSize, $excludedLuceedStatuses);
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
        return $this->collectOrderStatusUpdatesFromLuceedOrders(
            $updates,
            $batch,
            $statusesById,
            $statusesByLuceed,
            $this->getLuceedOrdersByUid($statusList, $startDate, $b2b),
            $withMail
        );
    }


    /**
     * @param string $statusList
     * @param string $startDate
     * @param bool   $b2b
     *
     * @return \Illuminate\Support\Collection
     */
    private function getLuceedOrdersByUid(string $statusList, string $startDate, bool $b2b)
    {
        $response = LuceedOrder::get(
            $statusList,
            $startDate,
            $b2b,
            self::ORDER_STATUS_REQUEST_TIMEOUT_SECONDS
        );

        if ( ! is_string($response) || trim($response) === '') {
            throw new \RuntimeException(
                'Luceed nije vratio podatke o statusima ' . ($b2b ? 'B2B' : 'B2C') . ' narudžbi.'
            );
        }

        $luceedOrders = $this->extractLuceedOrders($response);

        return $luceedOrders->keyBy('nalog_prodaje_uid');
    }


    /**
     * @param array                          $updates
     * @param \Illuminate\Support\Collection $batch
     * @param \Illuminate\Support\Collection $statusesById
     * @param \Illuminate\Support\Collection $statusesByLuceed
     * @param \Illuminate\Support\Collection $luceedOrdersByUid
     * @param bool                           $withMail
     *
     * @return array
     */
    private function collectOrderStatusUpdatesFromLuceedOrders(
        array $updates,
        $batch,
        $statusesById,
        $statusesByLuceed,
        $luceedOrdersByUid,
        bool $withMail = true
    ): array
    {
        if ($luceedOrdersByUid->isEmpty()) {
            return $updates;
        }

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

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Luceed je vratio neispravan JSON za statuse narudžbi.');
        }

        if ( ! isset($json->result[0]) || ! property_exists($json->result[0], 'nalozi_prodaje')) {
            throw new \RuntimeException('Luceed odgovor za statuse narudžbi nema očekivanu strukturu.');
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
            require_once DIR_SYSTEM . 'library/order_duration_rules.php';

            $this->ensureDurationNotificationTables();
            $activatedAt = $this->getDurationStatusActivationTime();

            if ($this->shouldRunFullDurationStatusCronSync()) {
                return $this->output($this->checkAllDurationStatusBatches($activatedAt));
            }

            $cursor = isset($this->request->get['cursor']) ? (int) $this->request->get['cursor'] : 0;

            return $this->output($this->checkDurationStatusBatch($activatedAt, $cursor));
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
     * The first cron run after deployment becomes the activation point. Orders
     * created before this value are never considered for duration mail.
     *
     * @return Carbon
     */
    private function getDurationStatusActivationTime(): Carbon
    {
        $activatedAt = self::DURATION_MAIL_ACTIVATED_AT;

        $this->db->query(
            "INSERT IGNORE INTO `" . DB_PREFIX . "order_duration_notification_state`
                (state_key, state_value, date_modified)
             VALUES (
                'activated_at',
                '" . $this->db->escape($activatedAt) . "',
                '" . $this->db->escape($activatedAt) . "'
             )"
        );

        $query = $this->db->query(
            "SELECT state_value
             FROM `" . DB_PREFIX . "order_duration_notification_state`
             WHERE state_key = 'activated_at'
             LIMIT 1"
        );

        if ( ! $query->num_rows || ! $query->row['state_value']) {
            throw new \RuntimeException('Nije moguće odrediti datum aktivacije vremenskih mailova.');
        }

        $storedActivation = Carbon::make($query->row['state_value']);
        $minimumActivation = Carbon::make(self::DURATION_MAIL_ACTIVATED_AT);

        return $storedActivation->greaterThan($minimumActivation) ? $storedActivation : $minimumActivation;
    }


    /**
     * @param Carbon $activatedAt
     * @param int $cursor
     *
     * @return array
     */
    private function checkDurationStatusBatch(Carbon $activatedAt, int $cursor = 0): array
    {
        $this->prepareLongRunningOrderStatusSync();

        $batchSize = $this->getOrderStatusBatchSize();
        $batch     = $this->getDurationStatusBatch($activatedAt, $cursor, $batchSize);

        if ($batch->isEmpty()) {
            return [
                'status'      => 200,
                'processed'   => 0,
                'eligible'    => 0,
                'updated'     => 0,
                'failed'      => 0,
                'skipped'     => 0,
                'done'        => true,
                'empty'       => ($cursor === 0),
                'next_cursor' => 0
            ];
        }

        $notifications = $this->collectDurationStatusNotifications($batch);
        $sent          = 0;
        $failed        = 0;
        $skipped       = 0;

        foreach ($notifications as $notification) {
            $result = $this->sendDurationStatusNotification($notification);

            if ($result === 'sent') {
                $sent++;
            } elseif ($result === 'failed') {
                $failed++;
            } else {
                $skipped++;
            }
        }

        return [
            'status'      => 200,
            'processed'   => $batch->count(),
            'eligible'    => count($notifications),
            'updated'     => $sent,
            'failed'      => $failed,
            'skipped'     => $skipped,
            'done'        => ($batch->count() < $batchSize),
            'empty'       => false,
            'next_cursor' => ($batch->count() < $batchSize) ? 0 : (int) $batch->last()->order_id
        ];
    }


    /**
     * @return bool
     */
    private function shouldRunFullDurationStatusCronSync(): bool
    {
        if (isset($this->request->get['cron']) && (int) $this->request->get['cron'] === 1) {
            return true;
        }

        if (isset($this->request->get['user_token']) && $this->request->get['user_token']) {
            return false;
        }

        return ! isset($this->request->get['cursor']) || (int) $this->request->get['cursor'] <= 0;
    }


    /**
     * @param Carbon $activatedAt
     *
     * @return array
     */
    private function checkAllDurationStatusBatches(Carbon $activatedAt): array
    {
        $processed = 0;
        $eligible  = 0;
        $sent      = 0;
        $failed    = 0;
        $skipped   = 0;
        $cursor    = 0;
        $empty     = true;

        do {
            $result = $this->checkDurationStatusBatch($activatedAt, $cursor);

            if ((int) $result['status'] !== 200) {
                return $result;
            }

            $processed += isset($result['processed']) ? (int) $result['processed'] : 0;
            $eligible  += isset($result['eligible']) ? (int) $result['eligible'] : 0;
            $sent      += isset($result['updated']) ? (int) $result['updated'] : 0;
            $failed    += isset($result['failed']) ? (int) $result['failed'] : 0;
            $skipped   += isset($result['skipped']) ? (int) $result['skipped'] : 0;
            $empty      = $empty && ! empty($result['empty']);

            if ( ! empty($result['done'])) {
                return [
                    'status'      => 200,
                    'processed'   => $processed,
                    'eligible'    => $eligible,
                    'updated'     => $sent,
                    'failed'      => $failed,
                    'skipped'     => $skipped,
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
                    'eligible'    => $eligible,
                    'updated'     => $sent,
                    'failed'      => $failed,
                    'skipped'     => $skipped,
                    'done'        => false,
                    'empty'       => $empty,
                    'next_cursor' => $nextCursor,
                    'message'     => 'Cron provjera vremenskih mailova je prekinuta zbog neispravnog kursora.'
                ];
            }

            $cursor = $nextCursor;
        } while (true);
    }


    /**
     * @param Carbon $activatedAt
     * @param int $cursor
     * @param int $limit
     *
     * @return \Illuminate\Support\Collection
     */
    private function getDurationStatusBatch(Carbon $activatedAt, int $cursor, int $limit)
    {
        $bounds = $this->getDurationThresholdBounds();

        if ($bounds['min'] <= 0 || $bounds['max'] <= 0) {
            return collect();
        }

        $now        = Carbon::now();
        $oldestTime = $now->copy()->subHours($bounds['max'] + OrderDurationRules::DELIVERY_WINDOW_HOURS);
        $newestTime = $now->copy()->subHours($bounds['min']);

        $query = Order::query()
                      ->select('order_id', 'email', 'payment_code', 'order_status_id', 'order_status_changed', 'date_added')
                      ->where('order_status_id', '!=', 0)
                      ->whereNotNull('order_status_changed')
                      ->where('date_added', '>=', $activatedAt->toDateTimeString())
                      ->where('order_status_changed', '>=', $oldestTime->toDateTimeString())
                      ->where('order_status_changed', '<=', $newestTime->toDateTimeString())
                      ->where('email', '!=', '');

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
        $configuration = $this->getDurationMailConfiguration();
        $now           = Carbon::now();

        foreach ($batch as $order) {
            $status = $statuses->get($order->order_status_id);

            if ( ! $status || ! $order->order_status_changed || ! isset($configuration[$order->payment_code])) {
                continue;
            }

            $statusCode = (string) $status->luceed_status_id;

            if ( ! isset($configuration[$order->payment_code][$statusCode])) {
                continue;
            }

            $changedAt = Carbon::make($order->order_status_changed);

            foreach ($configuration[$order->payment_code][$statusCode] as $thresholdHours => $mailKey) {
                $thresholdHours = (int) $thresholdHours;

                if ( ! $mailKey || ! OrderDurationRules::isDue($changedAt, $now, $thresholdHours)) {
                    continue;
                }

                $notifications[] = [
                    'order_id'             => (int) $order->order_id,
                    'order_status_id'      => (int) $order->order_status_id,
                    'order_status_changed' => $changedAt->toDateTimeString(),
                    'payment_code'         => (string) $order->payment_code,
                    'threshold_hours'      => $thresholdHours,
                    'email'                => $order->email,
                    'mail'                 => $mailKey
                ];
            }
        }

        return $notifications;
    }


    /**
     * @return array
     */
    private function getDurationMailConfiguration(): array
    {
        $mailConfiguration = agconf('mail');
        $durationRules     = [];

        if ( ! is_array($mailConfiguration)) {
            return $durationRules;
        }

        foreach ($mailConfiguration as $paymentCode => $items) {
            if ( ! is_array($items) || ! isset($items[0]) || ! is_array($items[0])) {
                continue;
            }

            $durationRules[$paymentCode] = $items[0];
        }

        return $durationRules;
    }


    /**
     * @return array
     */
    private function getDurationThresholdBounds(): array
    {
        $minimum = null;
        $maximum = null;

        foreach ($this->getDurationMailConfiguration() as $statuses) {
            foreach ($statuses as $thresholds) {
                if ( ! is_array($thresholds)) {
                    continue;
                }

                foreach ($thresholds as $hours => $mailKey) {
                    $hours = (int) $hours;

                    if ($hours <= 0 || ! $mailKey) {
                        continue;
                    }

                    $minimum = $minimum === null ? $hours : min($minimum, $hours);
                    $maximum = $maximum === null ? $hours : max($maximum, $hours);
                }
            }
        }

        return [
            'min' => $minimum ?: 0,
            'max' => $maximum ?: 0
        ];
    }


    /**
     * @param array $notification
     *
     * @return string
     */
    private function sendDurationStatusNotification(array $notification): string
    {
        $notificationId = $this->reserveDurationStatusNotification($notification);

        if ( ! $notificationId) {
            return 'skipped';
        }

        $now       = Carbon::now()->toDateTimeString();
        $staleTime = Carbon::now()->subMinutes(30)->toDateTimeString();

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order_duration_notification`
             SET status = 'processing',
                 attempts = attempts + 1,
                 mail_id = '" . $this->db->escape((string) $notification['mail']) . "',
                 last_error = NULL,
                 date_modified = '" . $this->db->escape($now) . "'
             WHERE order_duration_notification_id = " . (int) $notificationId . "
               AND (
                   status IN ('pending', 'failed')
                   OR (status = 'processing' AND date_modified < '" . $this->db->escape($staleTime) . "')
               )"
        );

        if ($this->db->countAffected() !== 1) {
            return 'skipped';
        }

        if ( ! $this->isDurationNotificationStillCurrent($notification)) {
            $this->markDurationNotificationSkipped($notificationId, 'Status narudžbe promijenjen je prije slanja.');

            return 'skipped';
        }

        try {
            $this->sendMail($notification);
        } catch (\Throwable $e) {
            $this->markDurationNotificationFailed($notificationId, $e->getMessage());
            Log::store(
                'Order ' . (int) $notification['order_id'] . ', mail ' . (string) $notification['mail'] . ': ' . $e->getMessage(),
                'luceed_order_duration_mail_error'
            );

            return 'failed';
        }

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order_duration_notification`
             SET status = 'sent',
                 last_error = NULL,
                 date_sent = '" . $this->db->escape($now) . "',
                 date_modified = '" . $this->db->escape($now) . "'
             WHERE order_duration_notification_id = " . (int) $notificationId
        );

        return 'sent';
    }


    /**
     * @param array $notification
     *
     * @return int
     */
    private function reserveDurationStatusNotification(array $notification): int
    {
        $now                  = Carbon::now()->toDateTimeString();
        $orderStatusChanged   = $this->db->escape($notification['order_status_changed']);
        $paymentCode          = $this->db->escape($notification['payment_code']);
        $mailId               = $this->db->escape((string) $notification['mail']);

        $this->db->query(
            "INSERT IGNORE INTO `" . DB_PREFIX . "order_duration_notification`
                (order_id, order_status_id, order_status_changed, payment_code, threshold_hours, mail_id, status, attempts, last_error, date_added, date_modified, date_sent)
             VALUES (
                " . (int) $notification['order_id'] . ",
                " . (int) $notification['order_status_id'] . ",
                '" . $orderStatusChanged . "',
                '" . $paymentCode . "',
                " . (int) $notification['threshold_hours'] . ",
                '" . $mailId . "',
                'pending',
                0,
                NULL,
                '" . $this->db->escape($now) . "',
                '" . $this->db->escape($now) . "',
                NULL
             )"
        );

        $query = $this->db->query(
            "SELECT order_duration_notification_id
             FROM `" . DB_PREFIX . "order_duration_notification`
             WHERE order_id = " . (int) $notification['order_id'] . "
               AND order_status_id = " . (int) $notification['order_status_id'] . "
               AND order_status_changed = '" . $orderStatusChanged . "'
               AND threshold_hours = " . (int) $notification['threshold_hours'] . "
             LIMIT 1"
        );

        return $query->num_rows ? (int) $query->row['order_duration_notification_id'] : 0;
    }


    /**
     * @param array $notification
     *
     * @return bool
     */
    private function isDurationNotificationStillCurrent(array $notification): bool
    {
        $query = $this->db->query(
            "SELECT order_status_id, order_status_changed
             FROM `" . DB_PREFIX . "order`
             WHERE order_id = " . (int) $notification['order_id'] . "
             LIMIT 1"
        );

        if ( ! $query->num_rows || ! $query->row['order_status_changed']) {
            return false;
        }

        $currentChangedAt = Carbon::make($query->row['order_status_changed'])->toDateTimeString();

        return (int) $query->row['order_status_id'] === (int) $notification['order_status_id']
            && $currentChangedAt === $notification['order_status_changed'];
    }


    /**
     * @param int    $notificationId
     * @param string $message
     *
     * @return void
     */
    private function markDurationNotificationFailed(int $notificationId, string $message): void
    {
        $now = Carbon::now()->toDateTimeString();

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order_duration_notification`
             SET status = 'failed',
                 last_error = '" . $this->db->escape(substr($message, 0, 2000)) . "',
                 date_modified = '" . $this->db->escape($now) . "'
             WHERE order_duration_notification_id = " . $notificationId
        );
    }


    /**
     * @param int    $notificationId
     * @param string $message
     *
     * @return void
     */
    private function markDurationNotificationSkipped(int $notificationId, string $message): void
    {
        $now = Carbon::now()->toDateTimeString();

        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order_duration_notification`
             SET status = 'skipped',
                 last_error = '" . $this->db->escape(substr($message, 0, 2000)) . "',
                 date_modified = '" . $this->db->escape($now) . "'
             WHERE order_duration_notification_id = " . $notificationId
        );
    }


    /**
     * @return void
     */
    private function ensureDurationNotificationTables(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_duration_notification` (
                `order_duration_notification_id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `order_status_id` INT(11) NOT NULL,
                `order_status_changed` DATETIME NOT NULL,
                `payment_code` VARCHAR(128) NOT NULL,
                `threshold_hours` SMALLINT(5) UNSIGNED NOT NULL,
                `mail_id` VARCHAR(32) NOT NULL,
                `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
                `attempts` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
                `last_error` TEXT NULL,
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                `date_sent` DATETIME NULL,
                PRIMARY KEY (`order_duration_notification_id`),
                UNIQUE KEY `uk_order_duration_notification` (`order_id`, `order_status_id`, `order_status_changed`, `threshold_hours`),
                KEY `idx_duration_notification_status` (`status`, `date_modified`),
                KEY `idx_duration_notification_order` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_duration_notification_state` (
                `state_key` VARCHAR(64) NOT NULL,
                `state_value` VARCHAR(255) NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`state_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
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
     * Dedicated Luceed availability check for external cron services.
     *
     * @return mixed
     */
    public function monitorAvailability()
    {
        if ( ! $this->validateMonitorAvailabilityToken()) {
            return $this->output([
                'status'  => 403,
                'message' => 'Invalid cron token.'
            ]);
        }

        $service = new \Agmedia\Luceed\Connection\LuceedService();
        $result  = $service->probeAvailability();

        return $this->output([
            'status'        => 200,
            'message'       => 'Luceed availability probe executed.',
            'service_status'=> $result['status'],
            'notification'  => $result['notification'] ?? null,
            'checked_at'    => $result['checked_at'] ?? null,
            'down_since'    => $result['down_since'] ?? null,
            'http_code'     => $result['http_code'] ?? null,
            'error'         => $result['error'] ?? '',
            'url'           => $result['url'] ?? ''
        ]);
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
     * Hard-stop stock syncs while Luceed is unavailable so existing quantities stay untouched.
     *
     * @param string $context
     *
     * @return array|null
     */
    private function abortStockSyncWhenLuceedUnavailable(string $context): ?array
    {
        $probe = (new \Agmedia\Luceed\Connection\LuceedService())->probeAvailability();

        if (($probe['status'] ?? 'down') === 'up') {
            return null;
        }

        Log::store([
            'context'    => $context,
            'checked_at' => $probe['checked_at'] ?? null,
            'down_since' => $probe['down_since'] ?? null,
            'http_code'  => $probe['http_code'] ?? null,
            'error'      => $probe['error'] ?? null,
        ], 'luceed_stock_sync_skipped');

        return [
            'status'  => 300,
            'message' => 'Luceed nije dostupan. Sinkronizacija zaliha je preskočena kako postojeće količine ne bi bile promijenjene.',
            'details' => [
                'checked_at' => $probe['checked_at'] ?? null,
                'down_since' => $probe['down_since'] ?? null,
                'http_code'  => $probe['http_code'] ?? null,
                'error'      => $probe['error'] ?? null,
            ],
        ];
    }


    /**
     * @return bool
     */
    private function validateMonitorAvailabilityToken(): bool
    {
        $token = isset($this->request->get['cron_token']) ? (string) $this->request->get['cron_token'] : '';

        if ( ! $token) {
            return false;
        }

        return hash_equals($this->resolveMonitorAvailabilityToken(), $token);
    }


    /**
     * @return string
     */
    private function resolveMonitorAvailabilityToken(): string
    {
        return hash('sha256', implode('|', [
            (string) agconf('service.base_url'),
            (string) agconf('service.username'),
            (string) agconf('service.password'),
            'luceed-health-monitor'
        ]));
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
            $data['mail_data'] = $email['data'];
            $data['oib']       = isset($order['custom_field'][1]) ? $order['custom_field'][1] : null;
            $data['tvrtka'] = isset($order['custom_field'][2]) ? $order['custom_field'][2] : null;
            $data['mail_poziv_na_broj'] = \Agmedia\LuceedOpencartWrapper\Helpers\PaymentReference::forOrder($data);

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

            if ( ! empty($order['reminder'])) {
                $this->markReminderMailSent($order);
            }
        }
    }


    /**
     * @param array $order
     *
     * @return void
     */
    private function markReminderMailSent(array $order): void
    {
        if (empty($order['order_id']) || empty($order['mail'])) {
            return;
        }

        $order_status_id = isset($order['order_status_id']) ? (int) $order['order_status_id'] : 0;

        if ($order_status_id <= 0) {
            $order_data = Order::query()->select('order_status_id')->where('order_id', (int) $order['order_id'])->first();
            $order_status_id = $order_data ? (int) $order_data->order_status_id : 0;
        }

        $comment = 'luceed_reminder_mail:' . (int) $order['mail'] . ':' . Carbon::now()->format('Y-m-d');

        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order['order_id'] . "', order_status_id = '" . (int) $order_status_id . "', notify = '0', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
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
        $mail->addBCC('z-el.duric@chipoteka.hr');
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
     * @return array
     */
    private function refreshLuceedProductsCache(bool $reconcile = true): array
    {
        $_loc = new LOC_Product(LuceedProduct::all());
        $result = $_loc->populateLuceedData();
        $this->snapshot_skipped_products = (int) ($result['skipped_products'] ?? 0);
        if ($reconcile) {
            $result['activation'] = $this->reconcileProductStatuses();
        }

        return $result;
    }


    /**
     * Refresh publication rules automatically after deployment and daily. A
     * product-feed failure must not prevent an independent valid stock update.
     */
    private function refreshProductSnapshotForStock(): ?string
    {
        try {
            if ((new LOC_ProductStatus())->needsSnapshotRefresh()) {
                $this->refreshLuceedProductsCache(false);
            }
            return null;
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_product_sync_error');
            $detail = $e instanceof \UnexpectedValueException ? ' Razlog: ' . $e->getMessage() : '';
            return 'Luceed popis artikala nije obnovljen. Provjera aktivacije koristi samo prethodne podatke koji nisu stariji od 48 sati.' . $detail;
        }
    }


    private function reconcileProductStatuses(): array
    {
        $result = (new LOC_ProductStatus())->reconcile();
        $this->cache->delete('product');
        $this->cache->delete('pqe.products');

        return $result;
    }


    private function stockSyncResponse(int $updated, ?string $snapshot_warning)
    {
        $this->cache->delete('product');
        $this->cache->delete('pqe.products');

        try {
            $activation = $this->reconcileProductStatuses();
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_product_status_error');
            $this->addServiceUnavailableHeader();
            return $this->output([
                'status' => 503,
                'message' => 'Zalihe su ažurirane, ali provjera aktivacije nije uspjela. Provjera će se ponoviti sljedećim cronom.'
            ]);
        }

        $this->load->language('extension/module/luceed_sync');
        $failed = !$updated || $snapshot_warning !== null || !empty($activation['skipped']);

        if ($failed) {
            $this->addServiceUnavailableHeader();
        }

        return $this->output([
            'status' => $failed ? 503 : 200,
            'message' => ($updated ? $this->language->get('text_success_update_stock') : $this->language->get('text_warning_update_stock'))
                . ' Aktivirano: ' . $activation['activated'] . ', deaktivirano: ' . $activation['deactivated'] . '.'
                . ($this->snapshot_skipped_products > 0 ? ' Zbog nepotpunih podataka odgođeno je artikala: ' . $this->snapshot_skipped_products . '. Ostali su obrađeni.' : '')
                . ($snapshot_warning ? ' ' . $snapshot_warning : '')
                . (!empty($activation['skipped']) ? ' Aktivacija je preskočena jer nema svježeg provjerenog popisa artikala.' : ''),
            'activation' => $activation,
            'snapshot_skipped_products' => $this->snapshot_skipped_products
        ]);
    }


    private function runProductSync(callable $callback)
    {
        if (!$this->acquireLuceedProductSyncLock()) {
            return $this->output(['status' => 409, 'message' => 'Sinkronizacija artikala ili zaliha je već u tijeku.']);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_product_sync_error');
            $this->addServiceUnavailableHeader();
            return $this->output(['status' => 503, 'message' => 'Sinkronizacija artikala nije uspjela. Preostali artikli ostaju za sljedeći pokušaj.']);
        } finally {
            $this->releaseLuceedProductSyncLock();
        }
    }


    /**
     * @return bool
     */
    private function acquireLuceedProductSyncLock(): bool
    {
        $result = $this->db->query("SELECT GET_LOCK('" . self::LUCEED_PRODUCT_SYNC_LOCK . "', 0) AS acquired");

        return isset($result->row['acquired']) && (int) $result->row['acquired'] === 1;
    }


    /**
     * @return void
     */
    private function releaseLuceedProductSyncLock(): void
    {
        try {
            $this->db->query("SELECT RELEASE_LOCK('" . self::LUCEED_PRODUCT_SYNC_LOCK . "')");
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_product_sync_lock_release_error');
        }
    }


    /**
     * Prevent overlapping automatic and manually triggered stock updates.
     */
    private function acquireLuceedStockSyncLock(): bool
    {
        $result = $this->db->query("SELECT GET_LOCK('" . self::LUCEED_STOCK_SYNC_LOCK . "', 0) AS acquired");

        return isset($result->row['acquired']) && (int)$result->row['acquired'] === 1;
    }


    private function releaseLuceedStockSyncLock(): void
    {
        try {
            $this->db->query("SELECT RELEASE_LOCK('" . self::LUCEED_STOCK_SYNC_LOCK . "')");
        } catch (\Throwable $e) {
            Log::store($e->getMessage(), 'luceed_stock_sync_lock_release_error');
        }
    }


    private function addServiceUnavailableHeader(): void
    {
        $protocol = isset($this->request->server['SERVER_PROTOCOL'])
            ? $this->request->server['SERVER_PROTOCOL']
            : 'HTTP/1.1';

        $this->response->addHeader($protocol . ' 503 Service Unavailable');
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
