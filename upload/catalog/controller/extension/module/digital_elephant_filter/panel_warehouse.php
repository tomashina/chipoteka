<?php
Class ControllerExtensionModuleDigitalElephantFilterPanelWarehouse extends Controller
{
    /**
     * @var ControllerExtensionModuleDigitalElephantFilterHelperUrl
     */
    private $helperUrl = null;

    public function prototype()
    {
        return $this;
    }

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperUrl = $this->load->controller('extension/module/digital_elephant_filter/helper_url/prototype');
    }

    private function formatWarehouseLabel(array $warehouse)
    {
        $label = trim((string)($warehouse['pj_naziv'] ?? ''));

        if ($label === '') {
            $label = trim((string)($warehouse['naziv'] ?? ''));
        }

        $label = preg_replace('/^PJ\s+/u', '', $label);
        $label = preg_replace('/^ZG\s+/u', 'Zagreb ', $label);
        $label = preg_replace('/\s+/u', ' ', $label);

        return trim($label);
    }

    public function getSectionGroups($setting, $category_id)
    {
        $section_groups = $this->cache->get('DEF_panel_wh_' . (int)$category_id);

        if ($this->helperUrl->isAjaxRequest() || !$section_groups || !isset($setting['DEF_settings']['cache']['isset'])) {
            $this->load->language('extension/module/digitalElephantFilter');

            $warehouses = $this->model_extension_module_digitalElephantFilter->getAvailabilityWarehouses();

            if (!$warehouses) {
                return array();
            }

            $values = array();

            foreach ($warehouses as $warehouse) {
                $warehouse_code = trim((string)($warehouse['skladiste'] ?? ''));

                if ($warehouse_code === '') {
                    continue;
                }

                $values[] = array(
                    'input_value' => $warehouse_code,
                    'input_name'  => 'warehouse',
                    'input_label' => $this->formatWarehouseLabel($warehouse),
                    'value_id'    => $warehouse_code,
                    'image'       => '',
                    'is_enable'   => true
                );
            }

            if (!$values) {
                return array();
            }

            $section_groups = array(
                array(
                    'group_name' => '',
                    'sections'   => array(
                        array(
                            'id'     => 'location',
                            'name'   => $this->language->get('text_store_availability'),
                            'values' => $values,
                            'type'   => 'select',
                            'open'   => true,
                        )
                    ),
                )
            );
        }

        return $section_groups;
    }
}
