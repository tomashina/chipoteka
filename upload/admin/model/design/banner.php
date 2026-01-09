<?php
class ModelDesignBanner extends Model {

    public function addBanner($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "banner SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "'");

        $banner_id = $this->db->getLastId();

        if (isset($data['banner_image'])) {
            foreach ($data['banner_image'] as $language_id => $value) {
                foreach ($value as $banner_image) {

                    // Datumi: spremi NULL ako prazno
                    $date_from = !empty($banner_image['date_from']) ? $this->db->escape($banner_image['date_from']) : null;
                    $date_to   = !empty($banner_image['date_to']) ? $this->db->escape($banner_image['date_to']) : null;

                    // Za polja koja možda nisu poslana (da ne baca notice)
                    $actiontitle   = isset($banner_image['actiontitle']) ? $banner_image['actiontitle'] : '';
                    $buttontitle   = isset($banner_image['buttontitle']) ? $banner_image['buttontitle'] : '';
                    $text          = isset($banner_image['text']) ? $banner_image['text'] : '';
                    $bojapozadine  = isset($banner_image['bojapozadine']) ? $banner_image['bojapozadine'] : '';
                    $image         = isset($banner_image['image']) ? $banner_image['image'] : '';
                    $image_resp    = isset($banner_image['image_responsive']) ? $banner_image['image_responsive'] : '';
                    $link          = isset($banner_image['link']) ? $banner_image['link'] : '';
                    $title         = isset($banner_image['title']) ? $banner_image['title'] : '';
                    $sort_order    = isset($banner_image['sort_order']) ? (int)$banner_image['sort_order'] : 0;

                    $this->db->query("INSERT INTO " . DB_PREFIX . "banner_image
						SET banner_id = '" . (int)$banner_id . "',
							language_id = '" . (int)$language_id . "',
							title = '" . $this->db->escape($title) . "',
							actiontitle = '" . $this->db->escape($actiontitle) . "',
							buttontitle = '" . $this->db->escape($buttontitle) . "',
							text = '" . $this->db->escape($text) . "',
							link = '" . $this->db->escape($link) . "',
							bojapozadine = '" . $this->db->escape($bojapozadine) . "',
							image = '" . $this->db->escape($image) . "',
							image_responsive = '" . $this->db->escape($image_resp) . "',
							date_from = " . ($date_from ? "'" . $date_from . "'" : "NULL") . ",
							date_to = " . ($date_to ? "'" . $date_to . "'" : "NULL") . ",
							sort_order = '" . $sort_order . "'
					");
                }
            }
        }

        return $banner_id;
    }

    public function editBanner($banner_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "banner
			SET name = '" . $this->db->escape($data['name']) . "',
				status = '" . (int)$data['status'] . "'
			WHERE banner_id = '" . (int)$banner_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");

        if (isset($data['banner_image'])) {
            foreach ($data['banner_image'] as $language_id => $value) {
                foreach ($value as $banner_image) {

                    $date_from = !empty($banner_image['date_from']) ? $this->db->escape($banner_image['date_from']) : null;
                    $date_to   = !empty($banner_image['date_to']) ? $this->db->escape($banner_image['date_to']) : null;

                    $actiontitle   = isset($banner_image['actiontitle']) ? $banner_image['actiontitle'] : '';
                    $buttontitle   = isset($banner_image['buttontitle']) ? $banner_image['buttontitle'] : '';
                    $text          = isset($banner_image['text']) ? $banner_image['text'] : '';
                    $bojapozadine  = isset($banner_image['bojapozadine']) ? $banner_image['bojapozadine'] : '';
                    $image         = isset($banner_image['image']) ? $banner_image['image'] : '';
                    $image_resp    = isset($banner_image['image_responsive']) ? $banner_image['image_responsive'] : '';
                    $link          = isset($banner_image['link']) ? $banner_image['link'] : '';
                    $title         = isset($banner_image['title']) ? $banner_image['title'] : '';
                    $sort_order    = isset($banner_image['sort_order']) ? (int)$banner_image['sort_order'] : 0;

                    $this->db->query("INSERT INTO " . DB_PREFIX . "banner_image
						SET banner_id = '" . (int)$banner_id . "',
							language_id = '" . (int)$language_id . "',
							title = '" . $this->db->escape($title) . "',
							actiontitle = '" . $this->db->escape($actiontitle) . "',
							buttontitle = '" . $this->db->escape($buttontitle) . "',
							text = '" . $this->db->escape($text) . "',
							link = '" . $this->db->escape($link) . "',
							bojapozadine = '" . $this->db->escape($bojapozadine) . "',
							image = '" . $this->db->escape($image) . "',
							image_responsive = '" . $this->db->escape($image_resp) . "',
							date_from = " . ($date_from ? "'" . $date_from . "'" : "NULL") . ",
							date_to = " . ($date_to ? "'" . $date_to . "'" : "NULL") . ",
							sort_order = '" . $sort_order . "'
					");
                }
            }
        }
    }

    public function deleteBanner($banner_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");
    }

    public function getBanner($banner_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");
        return $query->row;
    }

    public function getBanners($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "banner";

        $sort_data = array('name','status');

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getBannerImages($banner_id) {
        $banner_image_data = array();

        $banner_image_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "' ORDER BY sort_order ASC");

        foreach ($banner_image_query->rows as $banner_image) {
            $banner_image_data[$banner_image['language_id']][] = array(
                'title'      => $banner_image['title'],
                'actiontitle'=> $banner_image['actiontitle'],
                'buttontitle'=> $banner_image['buttontitle'],
                'text'       => $banner_image['text'],
                'link'       => $banner_image['link'],
                'bojapozadine'=> $banner_image['bojapozadine'],
                'image'      => $banner_image['image'],
                'image_responsive' => $banner_image['image_responsive'],
                'date_from'  => isset($banner_image['date_from']) ? $banner_image['date_from'] : '',
                'date_to'    => isset($banner_image['date_to']) ? $banner_image['date_to'] : '',
                'sort_order' => $banner_image['sort_order']
            );
        }

        return $banner_image_data;
    }

    public function getTotalBanners() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "banner");
        return $query->row['total'];
    }
}
