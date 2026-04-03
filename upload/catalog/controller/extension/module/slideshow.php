<?php
class ControllerExtensionModuleSlideshow extends Controller {
    public function index($setting) {
        static $module = 0;

        $this->load->model('design/banner');
        $this->load->model('tool/image');

        $data['banners'] = array();

        // U OC catalog model/design/banner->getBanner(banner_id) obično vraća banner_image zapise
        $results = $this->model_design_banner->getBanner($setting['banner_id']);

        $today = date('Y-m-d'); // dovoljno jer radimo samo s DATE (bez vremena)

        foreach ($results as $result) {

            // --- FILTER PO DATUMU (NOVO) ---
            $date_from = !empty($result['date_from']) ? $result['date_from'] : '';
            $date_to   = !empty($result['date_to']) ? $result['date_to'] : '';

            // Ako su oba popunjena, mora biti u intervalu (uključivo)
            if ($date_from && $date_to) {
                if ($today < $date_from || $today > $date_to) {
                    continue; // preskoči banner izvan intervala
                }
            }
            // Ako je jedan popunjen a drugi nije (ne bi se smjelo događati zbog validacije),
            // možeš odlučiti: preskoči ili prikaži. Ja preskačem zbog sigurnosti:
            if (($date_from && !$date_to) || (!$date_from && $date_to)) {
                continue;
            }
            // --- /FILTER ---

            if (empty($result['image']) || !is_file(DIR_IMAGE . $result['image'])) {
                continue;
            }

            $image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);

            // Kad nema posebne mobilne slike, koristi isti banner kako tap na mobitelu
            // ne bi završio na praznom ili kolabiranom slideu.
            if (!empty($result['image_responsive']) && is_file(DIR_IMAGE . $result['image_responsive'])) {
                $image_responsive = $this->model_tool_image->resize($result['image_responsive'], 500, 500);
            } else {
                $image_responsive = $image;
            }

            if ($image) {
                $data['banners'][] = array(
                    'title'            => $result['title'],
                    'actiontitle'      => isset($result['actiontitle']) ? $result['actiontitle'] : '',
                    'buttontitle'      => isset($result['buttontitle']) ? $result['buttontitle'] : '',
                    'text'             => isset($result['text']) ? $result['text'] : '',
                    'link'             => $result['link'],
                    'bojapozadine'     => isset($result['bojapozadine']) ? $result['bojapozadine'] : '',
                    'image'            => $image,
                    'image_responsive' => $image_responsive
                );
            }
        }

        $data['module'] = $module++;

        return $this->load->view('extension/module/slideshow', $data);
    }
}
