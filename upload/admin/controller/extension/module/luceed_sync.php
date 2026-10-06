<?php

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedGroup;
use Agmedia\Luceed\Facade\LuceedManufacturer;
use Agmedia\Luceed\Facade\LuceedOrder;
use Agmedia\Luceed\Facade\LuceedPayments;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Facade\LuceedWarehouse;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForRevisionData;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
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
        $loc = new LOC_Order();

        $loc->setOrders(
            LuceedOrder::get(
                $loc->collectStatuses(),
                Carbon::now()->subWeek(2)->format('d.m.Y') //agconf('import.orders.from_date')
            )
        );

        $updated = $loc->sort()->updateStatuses();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        $updated += $this->updateB2BOrderStatuses();

        return $this->response($updated, 'orders');
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
        $loc = new LOC_Order();

        $updated = $loc->checkStatusDuration();

        foreach ($loc->collection as $order) {
            $this->sendMail($order);
        }

        return $this->response($updated, 'orders');
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
