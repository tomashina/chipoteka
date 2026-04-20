<?php
class ControllerExtensionModuleDigitalElephantFilterPanelAttribute extends Controller
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
        $this->helperUrl  = $this->load->controller('extension/module/digital_elephant_filter/helper_url/prototype');
    }

    /**
     * Decimal display normalization:
     * - trim trailing zeros
     * - trim trailing decimal point
     * Examples: "15.60" -> "15.6", "13.00" -> "13"
     */
    private function defNormalizeDecimal($value) {
        if (!is_string($value)) {
            return $value;
        }

        $v = trim($value);
        if ($v === '') return $v;

        // if ever comes as "15,60"
        $v = str_replace(',', '.', $v);

        if (strpos($v, '.') !== false) {
            $v = rtrim($v, '0');
            $v = rtrim($v, '.');
        }

        return $v;
    }

    private function defLooksLikePlainNumber($value) {
        if (!is_string($value)) {
            return false;
        }

        $candidate = str_replace(',', '.', trim($value));

        return (bool)preg_match('/^-?\d+(?:\.\d+)?$/', $candidate);
    }

    private function defBooleanTrueValues() {
        return array('D', 'DA', 'T', 'TRUE', 'Y', 'YES');
    }

    private function defBooleanFalseValues() {
        return array('N', 'NE', 'F', 'FALSE', 'NO', '0');
    }

    private function defNormalizeBooleanToken($value) {
        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        return strtoupper($value);
    }

    private function defIsEnergyLabelSection($name) {
        $name = trim((string)$name);

        if ($name === '') {
            return false;
        }

        if (function_exists('mb_strtolower')) {
            $name = mb_strtolower($name, 'UTF-8');
        } else {
            $name = strtolower($name);
        }

        return (strpos($name, 'energetska oznaka') === 0);
    }

    private function defIsBooleanAttributeSection($name, array $values) {
        if ($this->defIsEnergyLabelSection($name)) {
            return false;
        }

        $allowed = array_flip(array_merge(
            $this->defBooleanTrueValues(),
            $this->defBooleanFalseValues()
        ));

        $tokens = array();

        foreach ($values as $value) {
            foreach (explode(':', (string)$value) as $part) {
                $token = $this->defNormalizeBooleanToken($part);

                if ($token === '') {
                    continue;
                }

                $tokens[$token] = true;
            }
        }

        if ( ! $tokens) {
            return false;
        }

        foreach (array_keys($tokens) as $token) {
            if ( ! isset($allowed[$token])) {
                return false;
            }
        }

        return true;
    }

    private function defNormalizeBooleanValue($value) {
        $token = $this->defNormalizeBooleanToken($value);

        if ($token === '') {
            return null;
        }

        if (in_array($token, $this->defBooleanTrueValues(), true)) {
            return 'D';
        }

        if (in_array($token, $this->defBooleanFalseValues(), true)) {
            return null;
        }

        return trim((string)$value);
    }

    public function getSectionGroups($setting, $category_id)
    {
        $category_id = (int)$category_id;

        $section_groups = $this->cache->get('DEF_panel_attr_' . $category_id);

        if (($this->helperUrl->isAjaxRequest() || !$section_groups || !isset($setting['DEF_settings']['cache']['isset']))) {
            $urlBase = $this->helperUrl->getUrlData();

            $args = array(
                'category_id' => $category_id,
                'filter_sub_category' => true
            );

            if (!empty($urlBase['warehouse'])) {
                $args['warehouses'] = $urlBase['warehouse'];
            }

            $attributes_groups = $this->model_extension_module_digitalElephantFilter->getAttributes($setting['DEF_settings'], $args);

            $attributes_type_by_setting = '';
            if (isset($setting['DEF_settings']['attributes'])) {
                $attributes_type_by_setting = $setting['DEF_settings']['attributes'];
            }

            // ===================== URL STATE + PRIMARY CONTEXT =====================
            // SVI trenutno odabrani atributi (primarni + sekundarni)
            $activeAttributes = [];
            if (!empty($urlBase['attribute']) && is_array($urlBase['attribute'])) {
                $activeAttributes = $urlBase['attribute'];
            }

            // def_primary iz URL-a (npr. "attribute:301" ili "manufacturer")
            $primary = isset($urlBase['def_primary']) ? (string)$urlBase['def_primary'] : '';

            // fallback: ako nema def_primary, uzmi prvi odabrani atribut
            if (!$primary && !empty($urlBase['attribute']) && is_array($urlBase['attribute'])) {
                $firstAttrId = array_key_first($urlBase['attribute']);
                if ($firstAttrId) {
                    $primary = 'attribute:' . (int)$firstAttrId;
                }
            }

            // fallback: ako nema atributa, a ima manufacturer odabir
            if (!$primary && !empty($urlBase['manufacturers']) && !empty($urlBase['manufacturers']['manufacturer'])) {
                $primary = 'manufacturer';
            }

            $primaryIsManufacturer = ($primary === 'manufacturer');

            // izvući ID primarnog atributa
            $primaryId = 0;
            if ($primary && strpos($primary, 'attribute:') === 0) {
                $primaryId = (int)str_replace('attribute:', '', $primary);
            }

            // FORCE totals čim postoji primary (attribute ili manufacturer)
            $forceTotals = ($primaryId > 0 || $primaryIsManufacturer);

            // želiš li prikazivati (x) uz vrijednost
            $showTotalsInLabel = false;
            // ======================================================================

            $section_groups = [];

            foreach ($attributes_groups as $attribute_groups_id => $attribute_group) {

                $sections = [];

                foreach ($attribute_group['attribute_values'] as $attribute_value_id => $attribute_value) {

                    $attribute_value_id = (int)$attribute_value_id;

                    if (isset($attributes_type_by_setting[$attribute_value_id]['hide'])) {
                        continue;
                    }

                    $values = [];
                    $seenValues = [];
                    $isBooleanAttr = $this->defIsBooleanAttributeSection(
                        $attribute_value['name'],
                        $attribute_value['values']
                    );

                    foreach ($attribute_value['values'] as $value) {

                        $rawValue = is_string($value) ? trim($value) : $value;

                        // ne prikazuj prazne (ali dozvoli '0')
                        if ($rawValue === '' || $rawValue === null) {
                            continue;
                        }

                        if ($isBooleanAttr) {
                            $rawValue = $this->defNormalizeBooleanValue($rawValue);

                            if ($rawValue === null) {
                                continue;
                            }
                        }

                        $displayValue = $rawValue;

                        // Za prikaz makni suvišne završne nule samo na čistim brojčanim vrijednostima.
                        if ($this->defLooksLikePlainNumber($rawValue)) {
                            $displayValue = $this->defNormalizeDecimal($rawValue);
                        }

                        if (isset($seenValues[(string)$rawValue])) {
                            continue;
                        }

                        $seenValues[(string)$rawValue] = true;

                        $input_label = $displayValue;
                        $is_enable = true;

                        // računaj totals ako je:
                        // - forceTotals (postoji primary)
                        // ili
                        // - originalna opcija is_display_total
                        if ($forceTotals || isset($setting['DEF_settings']['is_display_total'])) {

                            // start: trenutni URL state
                            $url = $urlBase;
                            $url['category_id'] = $category_id;

                            /**
                             * Pravilo:
                             * - Primarni definira bazni skup
                             * - Ostali se računaju unutar tog skupa
                             */

                            // CASE A: primarni je MANUFACTURER -> totals za atribute unutar branda
                            if ($primaryIsManufacturer) {

                                // zadrži samo manufacturer selekciju
                                $keepMan = [];
                                if (isset($url['manufacturers']) && is_array($url['manufacturers'])) {
                                    $keepMan = $url['manufacturers'];
                                }
                                $url['manufacturers'] = $keepMan;

                                // makni sekundarne da ne guše enable logiku
                                $url['attribute'] = [];
                                $url['option'] = [];
                            }

                            // CASE B: primarni je ATTRIBUTE -> totals za ostale atribute unutar kombinacije (primarni + sekundarni)
                            else if ($primaryId > 0) {

                                // zadrži sve odabrane atribute, ali izbaci trenutni atribut (čist test)
                                $tmpAttrs = $activeAttributes;
                                unset($tmpAttrs[$attribute_value_id]);
                                $url['attribute'] = $tmpAttrs;

                                // makni ostale sekundarne
                                $url['option'] = [];
                                // $url['manufacturers'] = []; // po želji: makni brand kad je primarni atribut
                            }

                            // Dodaj ovu test vrijednost
                            if (!isset($url['attribute'][$attribute_value_id]) || !is_array($url['attribute'][$attribute_value_id])) {
                                $url['attribute'][$attribute_value_id] = [];
                            }

                            // spriječi duplikate
                            if (!in_array($rawValue, $url['attribute'][$attribute_value_id], true)) {
                                $url['attribute'][$attribute_value_id][] = $rawValue;
                            }

                            $filter_data = $this->getProduct->getFilterDataByUrl($url);
                            $totalProducts = $this->getProduct->getTotalProducts($filter_data);

                            $is_enable = ((int)$totalProducts > 0);

                            // Label: po želji pokaži (total)
                            if ($showTotalsInLabel || isset($setting['DEF_settings']['is_display_total'])) {
                                $input_label = $displayValue . ' <span>(' . (int)$totalProducts . ')</span>';
                            } else {
                                $input_label = $displayValue;
                            }
                        }

                        // ✅ SAKRIJ vrijednosti koje ne postoje u nasljednim atributima

                        // 1) primarni manufacturer -> sakrij SVE attribute vrijednosti s 0
                        if ($primaryIsManufacturer && !$is_enable) {
                            continue;
                        }

                        // 2) primarni attribute -> sakrij sekundarne atribute s 0
                        if ($primaryId > 0 && (int)$attribute_value_id !== $primaryId && !$is_enable) {
                            continue;
                        }

                        // 3) ako postoji sekundarni atribut (više od 1 odabran), suzi i primarni po kombinaciji
                        if ($primaryId > 0 && count($activeAttributes) > 1 && !$primaryIsManufacturer && !$is_enable) {
                            continue;
                        }

                        $values[] = array(
                            'input_value' => $rawValue,
                            'display_value' => $displayValue,
                            'input_name'  => 'attribute',
                            'input_label' => $input_label,
                            'value_id'    => $rawValue,
                            'image'       => '',
                            'is_enable'   => $is_enable
                        );
                    }

                    if ($values) {

                        // type
                        $type = '';
                        if (isset($attributes_type_by_setting[$attribute_value_id]['type'])) {
                            $type = $attributes_type_by_setting[$attribute_value_id]['type'];
                        }

                        // open
                        $opened = true;
                        if (isset($attributes_type_by_setting[$attribute_value_id]['close'])) {
                            $opened = false;
                        }

                        $sections[] = array(
                            'id'         => $attribute_value_id,
                            'name'       => $attribute_value['name'],
                            'values'     => $values,
                            'type'       => $type,
                            'open'       => $opened,
                            'is_boolean' => $isBooleanAttr,
                        );
                    }
                }

                if ($sections) {
                    $section_groups[] = array(
                        'group_name' => $attribute_group['name'],
                        'sections'   => $sections,
                    );
                }
            }
        }

        return $section_groups;
    }
}
