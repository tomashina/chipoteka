<?php
Class ControllerExtensionModuleDigitalElephantFilterPanelManufacturer extends Controller
{
    /**
     * @var ControllerExtensionModuleDigitalElephantFilterGetProduct
     */
    private $getProduct = null;

    /**
     * @var ControllerExtensionModuleDigitalElephantFilterHelperUrl
     */
    private $helperUrl = null;

    public function prototype() {
        return $this;
    }

    public function __construct($registry) {
        parent::__construct($registry);
        $this->getProduct = $this->load->controller('extension/module/digital_elephant_filter/get_product/prototype');
        $this->helperUrl = $this->load->controller('extension/module/digital_elephant_filter/helper_url/prototype');
    }

    public function getSectionGroups($setting, $category_id)
    {
        // manufacturers setting
        $manufacturers_setting = $setting['DEF_settings']['manufacturers'][0];
        $manufacturers_setting_advance = $setting['DEF_settings']['advance']['manufacturers'];

        $section_groups = $this->cache->get('DEF_panel_man_' . $category_id);
        $urlBase = $this->helperUrl->getUrlData();
        $hasActionFilter = !empty($urlBase['action_filter']) || !empty($urlBase['action_category_id']);

        if (($this->helperUrl->isAjaxRequest() || $hasActionFilter || !$section_groups || !isset($setting['DEF_settings']['cache']['isset']))
            && !isset($manufacturers_setting['hide'])) {
            $args = array(
                'category_id' => $category_id,
                'filter_sub_category' => true
            );

            if (!empty($urlBase['warehouse'])) {
                $args['warehouses'] = $urlBase['warehouse'];
            }

            if ($hasActionFilter) {
                $args['action_filter_only'] = 1;
                $args['action_category_id'] = !empty($urlBase['action_category_id']) ? (int)$urlBase['action_category_id'] : 0;
                $args['action_parent_id'] = 308;
            }

            $manufacturers = $this->model_extension_module_digitalElephantFilter->getManufacturers($setting['DEF_settings'], $args);

            $primary = isset($urlBase['def_primary']) ? (string)$urlBase['def_primary'] : '';

            // fallback: prvi odabrani atribut postaje primarni
            if (!$primary && !empty($urlBase['attribute']) && is_array($urlBase['attribute'])) {
                $firstAttrId = array_key_first($urlBase['attribute']);
                if ($firstAttrId) {
                    $primary = 'attribute:' . (int)$firstAttrId;
                }
            }

            // fallback: ako nema atributa, a ima brand odabir
            if (!$primary && !empty($urlBase['manufacturers']) && !empty($urlBase['manufacturers']['manufacturer'])) {
                $primary = 'manufacturer';
            }

            $primaryAttrId = 0;
            if ($primary && strpos($primary, 'attribute:') === 0) {
                $primaryAttrId = (int)str_replace('attribute:', '', $primary);
            }
            // --- NOVO END ---

            $section_groups = [];
            $sections = [];
            $values = [];

            $this->load->model('tool/image');
            $this->load->language('extension/module/digitalElephantFilter');

            foreach ($manufacturers as $manufacturer) {

                $image = '';
                if (!empty($manufacturer['image']) && is_file(DIR_IMAGE . $manufacturer['image'])) {
                    $image_width = $manufacturers_setting_advance['image']['width'];
                    $image_height = $manufacturers_setting_advance['image']['height'];
                    $image = $this->model_tool_image->resize($manufacturer['image'], $image_width, $image_height);
                }

                $input_label = $manufacturer['name'];
                $is_enable = true;

                if (isset($setting['DEF_settings']['is_display_total'])) {

                    // start: trenutni URL state
                    $url = $urlBase;
                    $url['category_id'] = $category_id;

                    /**
                     * Pravilo:
                     * - Primarni definira bazni skup
                     * - Nasljedni (manufacturer ovdje) se računa unutar primarnog skupa
                     */

                    // CASE A: manufacturer je PRIMARNI
                    if ($primary === 'manufacturer') {

                        // brendovi se računaju "čisto" (bez sekundarnih filtera)
                        $url['attribute'] = [];
                        $url['option'] = [];

                        // postavi test brand
                        $url['manufacturers'] = [];
                        $url['manufacturers']['manufacturer'] = [(int)$manufacturer['manufacturer_id']];
                    }

                    // CASE B: primarni je ATTRIBUTE
                    elseif ($primaryAttrId > 0) {

                        // ostavi samo primarni atribut (njegove odabrane vrijednosti)
                        $keepAttr = [];
                        if (isset($url['attribute'][$primaryAttrId]) && is_array($url['attribute'][$primaryAttrId])) {
                            $keepAttr[$primaryAttrId] = $url['attribute'][$primaryAttrId];
                        }
                        $url['attribute'] = $keepAttr;

                        // makni ostale sekundarne koji ne bi smjeli diktirati enable logiku
                        $url['option'] = [];

                        // postavi test brand (nasljedni)
                        $url['manufacturers'] = [];
                        $url['manufacturers']['manufacturer'] = [(int)$manufacturer['manufacturer_id']];
                    }

                    // CASE C: nema primarnog (prije prvog odabira)
                    else {
                        $url['manufacturers'] = [];
                        $url['manufacturers']['manufacturer'] = [(int)$manufacturer['manufacturer_id']];
                    }

                    $filter_data = $this->getProduct->getFilterDataByUrl($url);
                    $totalProducts = $this->getProduct->getTotalProducts($filter_data);

                    $input_label = $manufacturer['name'] . ' <span>(' . (int)$totalProducts . ')</span>';
                    $is_enable = ((int)$totalProducts > 0);
                }

                // ✅ OVDJE UBACI
                if ($primary && $primary !== 'manufacturer' && !$is_enable) {
                    continue;
                }

                $values[] = array(
                    'input_value' => (int)$manufacturer['manufacturer_id'],
                    'input_name'  => 'manufacturers',
                    'input_label' => $input_label,
                    'value_id'    => (int)$manufacturer['manufacturer_id'],
                    'image'       => $image,
                    'is_enable'   => $is_enable
                );
            }

            // type
            $type = 'none';
            if (isset($manufacturers_setting['type']) && $manufacturers_setting['type']) {
                $type = $manufacturers_setting['type'];
            }

            // open
            $opened = true;
            if (isset($manufacturers_setting['close'])) {
                $opened = false;
            }

            $section_name = $this->language->get('text_manufacturers');
            $section_id = 'manufacturer';

            $sections[] = array(
                'id'     => $section_id,
                'name'   => $section_name,
                'values' => $values,
                'type'   => $type,
                'open'   => $opened,
            );

            $section_groups[] = array(
                'group_name' => '',
                'sections'   => $sections,
            );
        }

        return $section_groups;
    }
}
