<?php
class ModelExtensionMpGdprMpgdpr extends \Mpgdpr\Model {
    public function getTodayDeleteMeRequest($customer_id) {
        $query=$this->db->query("SELECT COUNT(mpgdpr_deleteme_id) as total FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `customer_id`='" . $this->db->escape($customer_id) . "' AND DATE(`date_added`)=CURDATE()");
        /*AND `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."' */
        return $query->row['total'];
    }

    public function getTodayPersonalDataRequest($customer_id) {
        $query=$this->db->query("SELECT COUNT(mpgdpr_datarequest_id) as total FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `customer_id`='" . $this->db->escape($customer_id) . "' AND DATE(`date_added`)=CURDATE()");
        /*AND `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."' */
        return $query->row['total'];
    }

    // 01-05-2022: updation start

    public function getDeleteMeRequests($customer_id, $data=[]) {

        $sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_deleteme d WHERE d.mpgdpr_deleteme_id > 0 AND `customer_id`='" . $this->db->escape($customer_id) . "'";
        /*13 sep 2019 gdpr session starts*/
        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND d.status = '" . (int)$data['filter_status'] . "'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_request_id'])) {
            $sql .= " AND d.mpgdpr_deleteme_id = '" . (int)$data['filter_request_id'] . "'";
        }
        if (!empty($data['filter_date_deletion'])) {
            $sql .= " AND DATE(d.date_deletion) = DATE('" . $this->db->escape($data['filter_date_deletion']) . "')";
        }
        if (!empty($data['filter_useragent'])) {
            $sql .= " AND d.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_server_ip'])) {
            $sql .= " AND d.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
        }
        if (!empty($data['filter_client_ip'])) {
            $sql .= " AND d.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
            $sql .= " AND DATE(d.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(d.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(d.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }

        // get past xx days/month/years requests
        if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
            $sql .= " AND d.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND d.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
        }

        $sql .= " GROUP BY d.mpgdpr_deleteme_id";

        $sort_data = [
            'd.mpgdpr_deleteme_id',
            'd.status',
            'd.date_deletion',
            'd.date_added',
            'd.expire_on',
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY d.mpgdpr_deleteme_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getTotalDeleteMeRequests($customer_id, $data=[]) {
        $sql = "SELECT COUNT(DISTINCT r.mpgdpr_deleteme_id) AS total FROM " . DB_PREFIX . "mpgdpr_deleteme r WHERE r.mpgdpr_deleteme_id > 0 AND `customer_id`='" . $this->db->escape($customer_id) . "'";
        /*13 sep 2019 gdpr session starts*/
        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_request_id'])) {
            $sql .= " AND r.mpgdpr_deleteme_id = '" . (int)$data['filter_request_id'] . "'";
        }
        if (!empty($data['filter_date_deletion'])) {
            $sql .= " AND DATE(r.date_deletion) = DATE('" . $this->db->escape($data['filter_date_deletion']) . "')";
        }
        if (!empty($data['filter_useragent'])) {
            $sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_server_ip'])) {
            $sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
        }
        if (!empty($data['filter_client_ip'])) {
            $sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        /*13 sep 2019 gdpr session starts*/
        // get past xx days/month/years requests
        if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
            $sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL  1 DAY ";
        }
        /*13 sep 2019 gdpr session ends*/
        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getDeleteMeRequestById($mpgdpr_deleteme_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `mpgdpr_deleteme_id`='" . (int)$mpgdpr_deleteme_id . "'");
        return $query->row;
    }

    public function resentDeleteMeRequestCode($mpgdpr_deleteme_id, $customer_email) {

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `mpgdpr_deleteme_id`='" . (int)$mpgdpr_deleteme_id . "' AND `status`='" . $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) . "' AND `expire_on` < '". $this->db->escape(date('Y-m-d H:i:s')) ."'");

        if ($query->row) {
            if (!isset($query->row['email']) || empty($query->row['email']) || !filter_var($query->row['email'], FILTER_VALIDATE_EMAIL)) {
                $query->row['email'] = $customer_email;
            }


            $mpgdpr_timeout = $this->config->get('mpgdpr_timeout');
            if (!empty($mpgdpr_timeout['requestdelete_personaldata'])) {
                $hours = $mpgdpr_timeout['requestdelete_personaldata'];
            } else {
                $hours = 2;
            }

            $datemodify = $date = date('Y-m-d H:i:s');

            $expire_on = date('Y-m-d H:i:s', strtotime('+ '. (int)$hours .' HOURS ' .$date));

            do {
                $code = $this->mpgdpr->token(10);
                $codequery = $this->db->query("SELECT code FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `code`='" . $this->db->escape($code) . "'");
                $exits = false;
                if ($codequery->num_rows) {
                    $exits = true;
                }
            } while ($exits);
            // 01-05-2022: updation start
            // Add: email='" . (int)data.email . "'

            $this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_deleteme` SET `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `code`='". $this->db->escape($code) ."', `status`='". $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_modified` = '". $this->db->escape($datemodify) ."', `expire_on`='". $expire_on ."' WHERE `mpgdpr_deleteme_id`='". (int)$mpgdpr_deleteme_id ."'");

            // send email to
            // 01-05-2022: updation start
            $mail_admin = $this->config->get('mpgdpr_mail_admin');
            $mail_user = $this->config->get('mpgdpr_mail_user');
            /*
             * subject : ''
             * msg : ''
             */
            $email_template_admin = [];
            $email_template_user = [];
            $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
            if (isset($emailtemplate['rcrdpd'])) {
                if (isset($emailtemplate['rcrdpd']['admin'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_admin = $emailtemplate['rcrdpd']['admin'][ (int)$this->config->get('config_language_id') ];
                }
                if (isset($emailtemplate['rcrdpd']['user'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_user = $emailtemplate['rcrdpd']['user'][ (int)$this->config->get('config_language_id') ];
                }
            }

            $store_info = $this->storeInfo();

            $this->load->language('mpgdpr/mail');

            // mail to customer for confirmation they add request for resent verification code for delete me. We do verification first.
            if (!isset($mail_user['rcrdpd']) || (isset($mail_user['rcrdpd']) && $mail_user['rcrdpd'] == 1)) {
                $find = [
                    '{code}',
                    '{verification_url}',
                    // 01-05-2022: updation start
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                    // 01-05-2022: updation end
                ];

                $replace = [
                    'code' => $code,
                    'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                    // 01-05-2022: updation start
                    'user_email' => ((empty($query->row['email']) || (!empty($query->row['email']) && $query->row['email'] != $customer_email)) ? $query->row['email'] .'-' . $customer_email : $customer_email),
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                    // 01-05-2022: updation end
                ];

                $mail_subject = $this->language->get('text_deleteme_resentcode_customer_subject');
                $mail_message = $this->language->get('text_deleteme_resentcode_customer_message');
                // 01-05-2022: updation start
                if (!empty($email_template_user['subject'])) {
                    $mail_subject = $email_template_user['subject'];
                }
                if (!empty($email_template_user['msg'])) {
                    $mail_message = $email_template_user['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($query->row['email']);

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($this->config->get('config_email'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();

                // send email to given customer email, if by chance email in request and customer email is different.
                if ($query->row['email'] != $customer_email) {
                    $mail->setTo($customer_email);
                    $mail->send();
                }
            }


            $replyto = $query->row['email'];
            if (empty($replyto) || (!empty($replyto) && $replyto != $customer_email)) {
                $replyto = $customer_email;
            }
            // mail to admins for resent verification code for delete me by user/customer
            if (!isset($mail_admin['rcrdpd']) || (isset($mail_admin['rcrdpd']) && $mail_admin['rcrdpd'] == 1)) {
                // 01-05-2022: updation start
                $find = [
                    '{code}',
                    '{verification_url}',
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];

                $replace = [
                    'code' => $code,
                    'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                    'user_email' => ((empty($query->row['email']) || (!empty($query->row['email']) && $query->row['email'] != $customer_email)) ? $query->row['email'] .'-' . $customer_email : $customer_email),
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];
                // 01-05-2022: updation end

                $mail_subject = $this->language->get('text_deleteme_resentcode_admin_subject');
                $mail_message = $this->language->get('text_deleteme_resentcode_admin_message');

                // 01-05-2022: updation start
                if (!empty($email_template_admin['subject'])) {
                    $mail_subject = $email_template_admin['subject'];
                }
                if (!empty($email_template_admin['msg'])) {
                    $mail_message = $email_template_admin['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));


                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($this->config->get('config_email'));

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($replyto);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
            // 01-05-2022: updation end

            return true;
        }
        return false;
    }
    public function getPersonalDataRequests($customer_id, $data=[]) {

        $sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_datarequest dr WHERE dr.mpgdpr_datarequest_id > 0 AND dr.`customer_id`='" . (int)$customer_id . "'";

        /*13 sep 2019 gdpr session starts*/
        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND dr.status = '" . (int)$data['filter_status'] . "'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_request_id'])) {
            $sql .= " AND dr.mpgdpr_datarequest_id = '" . (int)$data['filter_request_id'] . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_date_send'])) {
            $sql .= " AND DATE(dr.date_send) = DATE('" . $this->db->escape($data['filter_date_send']) . "')";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_useragent'])) {
            $sql .= " AND dr.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_server_ip'])) {
            $sql .= " AND dr.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
        }
        if (!empty($data['filter_client_ip'])) {
            $sql .= " AND dr.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
            $sql .= " AND DATE(dr.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(dr.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(dr.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }

        // get past xx days/month/years requests
        if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
            $sql .= " AND dr.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND dr.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
        }

        $sql .= " GROUP BY dr.mpgdpr_datarequest_id";

        $sort_data = [
            'dr.mpgdpr_datarequest_id',
            'dr.status',
            'dr.date_send',
            'dr.date_added',
            'dr.expire_on'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY dr.mpgdpr_datarequest_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getTotalPersonalDataRequests($customer_id, $data=[]) {
        $sql = "SELECT COUNT(DISTINCT r.mpgdpr_datarequest_id) AS total FROM " . DB_PREFIX . "mpgdpr_datarequest r WHERE r.mpgdpr_datarequest_id > 0 AND r.`customer_id`='" . (int)$customer_id . "'";
        /*13 sep 2019 gdpr session starts*/
        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_request_id'])) {
            $sql .= " AND r.mpgdpr_datarequest_id = '" . (int)$data['filter_request_id'] . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_date_send'])) {
            $sql .= " AND DATE(r.date_send) = DATE('" . $this->db->escape($data['filter_date_send']) . "')";
        }
        /*13 sep 2019 gdpr session ends*/

        if (!empty($data['filter_useragent'])) {
            $sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
        }
        /*13 sep 2019 gdpr session starts*/
        if (!empty($data['filter_server_ip'])) {
            $sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
        }
        if (!empty($data['filter_client_ip'])) {
            $sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
        }
        /*13 sep 2019 gdpr session ends*/
        if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        /*13 sep 2019 gdpr session starts*/
        // get past xx days/month/years requests
        if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
            $sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL  1 DAY ";
        }
        /*13 sep 2019 gdpr session ends*/
        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getPersonalDataRequestById($mpgdpr_datarequest_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `mpgdpr_datarequest_id`='" . (int)$mpgdpr_datarequest_id . "'");
        return $query->row;
    }
    public function resentPersonalDataRequestCode($mpgdpr_datarequest_id, $customer_email) {

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `mpgdpr_datarequest_id`='" . (int)$mpgdpr_datarequest_id . "' AND `status`='" . $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) . "' AND `expire_on` < '". $this->db->escape(date('Y-m-d H:i:s')) ."'");

        if ($query->row) {
            if (!isset($query->row['email']) || empty($query->row['email']) || !filter_var($query->row['email'], FILTER_VALIDATE_EMAIL)) {
                $query->row['email'] = $customer_email;
            }


            $mpgdpr_timeout = $this->config->get('mpgdpr_timeout');
            if (!empty($mpgdpr_timeout['requestget_personaldata'])) {
                $hours = $mpgdpr_timeout['requestget_personaldata'];
            } else {
                $hours = 2;
            }

            $datemodify = $date = date('Y-m-d H:i:s');

            $expire_on = date('Y-m-d H:i:s', strtotime('+ '. (int)$hours .' HOURS ' .$date));

            do {
                $code = $this->mpgdpr->token(10);
                $codequery = $this->db->query("SELECT code FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `code`='" . $this->db->escape($code) . "'");
                $exits = false;
                if ($codequery->num_rows) {
                    $exits = true;
                }
            } while ($exits);
            // 01-05-2022: updation start
            // Add: email='" . (int)data.email . "'

            $this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_datarequest` SET `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `code`='". $this->db->escape($code) ."', `status`='". $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_modified` = '". $this->db->escape($datemodify) ."', `expire_on`='". $expire_on ."' WHERE `mpgdpr_datarequest_id`='". (int)$mpgdpr_datarequest_id ."'");

            // send email to
            // 01-05-2022: updation start
            $mail_admin = $this->config->get('mpgdpr_mail_admin');
            $mail_user = $this->config->get('mpgdpr_mail_user');
            /*
             * subject : ''
             * msg : ''
             */
            $email_template_admin = [];
            $email_template_user = [];
            $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
            if (isset($emailtemplate['rcapd'])) {
                if (isset($emailtemplate['rcapd']['admin'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_admin = $emailtemplate['rcapd']['admin'][ (int)$this->config->get('config_language_id') ];
                }
                if (isset($emailtemplate['rcapd']['user'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_user = $emailtemplate['rcapd']['user'][ (int)$this->config->get('config_language_id') ];
                }
            }

            $store_info = $this->storeInfo();

            $this->load->language('mpgdpr/mail');

            // mail to customer for confirmation they add request for resent verification code for access to personal data. We do verification first.
            if (!isset($mail_user['rcapd']) || (isset($mail_user['rcapd']) && $mail_user['rcapd'] == 1)) {
                $find = [
                    '{code}',
                    '{verification_url}',
                    // 01-05-2022: updation start
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                    // 01-05-2022: updation end
                ];
                $replace = [
                    'code' => $code,
                    'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                    // 01-05-2022: updation start
                    'user_email' => ((empty($query->row['email']) || (!empty($query->row['email']) && $query->row['email'] != $customer_email)) ? $query->row['email'] .'-' . $customer_email : $customer_email),
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                    // 01-05-2022: updation end
                ];

                $mail_subject = $this->language->get('text_datarequest_resentcode_customer_subject');
                $mail_message = $this->language->get('text_datarequest_resentcode_customer_message');

                // 01-05-2022: updation start
                if (!empty($email_template_user['subject'])) {
                    $mail_subject = $email_template_user['subject'];
                }
                if (!empty($email_template_user['msg'])) {
                    $mail_message = $email_template_user['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($query->row['email']);

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($this->config->get('config_email'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();

                // send email to given customer email, if by chance email in request and customer email is different.
                if ($query->row['email'] != $customer_email) {
                    $mail->setTo($customer_email);
                    $mail->send();
                }
            }

            $replyto = $query->row['email'];
            if (empty($replyto) || (!empty($replyto) && $replyto != $customer_email)) {
                $replyto = $customer_email;
            }
            // mail to admins for resent verification code for access to personal data by user/customer
            if (!isset($mail_admin['rcapd']) || (isset($mail_admin['rcapd']) && $mail_admin['rcapd'] == 1)) {
                // 01-05-2022: updation start
                $find = [
                    '{code}',
                    '{verification_url}',
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'code' => $code,
                    'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                    'user_email' => ((empty($query->row['email']) || (!empty($query->row['email']) && $query->row['email'] != $customer_email)) ? $query->row['email'] .'-' . $customer_email : $customer_email),
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];
                // 01-05-2022: updation end

                $mail_subject = $this->language->get('text_datarequest_resentcode_admin_subject');
                $mail_message = $this->language->get('text_datarequest_resentcode_admin_message');

                // 01-05-2022: updation start
                if (!empty($email_template_admin['subject'])) {
                    $mail_subject = $email_template_admin['subject'];
                }
                if (!empty($email_template_admin['msg'])) {
                    $mail_message = $email_template_admin['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));


                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($this->config->get('config_email'));

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($replyto);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
            // 01-05-2022: updation end

            return true;
        }
        return false;
    }

    private function storeInfo() {
        if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
        } else {
            $server = $this->config->get('config_url');
        }

        $logo = '';
        if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
            $logo = $server . 'image/' . $this->config->get('config_logo');
        }

        return [
            'logo' => $logo,
            'name' => $this->config->get('config_name'),
            'href' => $this->url->link('common/home', '', true)
        ];
    }
    // 01-05-2022: updation end
    public function getCustomerIdFromEmail($email) {
        $query = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "customer` WHERE `email`='" . $this->db->escape($email) . "'");
        if ($query->row) {
            return $query->row['customer_id'];
        } else {
            return 0;
        }
    }

    public function getCustomerIdEmail($customer_id) {
        $query = $this->db->query("SELECT email FROM `" . DB_PREFIX . "customer` WHERE `customer_id`='" . (int)$customer_id . "'");
        if ($query->row) {
            return $query->row['email'];
        } else {
            return '';
        }
    }

    public function getCustomerData($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE `customer_id`='" . (int)$customer_id . "'");
        return $query->row;
    }


    public function getCustomerAddresses($customer_id) {
        $address_data = [];

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");

        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        foreach ($query->rows as $result) {

            $country_info = $this->model_localisation_country->getCountry($result['country_id']);

            if ($country_info) {
                $country = $country_info['name'];
                $iso_code_2 = $country_info['iso_code_2'];
                $iso_code_3 = $country_info['iso_code_3'];
                $address_format = $country_info['address_format'];
            } else {
                $country = '';
                $iso_code_2 = '';
                $iso_code_3 = '';
                $address_format = '';
            }

            $zone_info = $this->model_localisation_zone->getZone($result['zone_id']);

            if ($zone_info) {
                $zone = $zone_info['name'];
                $zone_code = $zone_info['code'];
            } else {
                $zone = '';
                $zone_code = '';
            }

            $address_data[$result['address_id']] = [
                'address_id'     => $result['address_id'],
                'customer_id'     => $result['customer_id'],
                'firstname'      => $result['firstname'],
                'lastname'       => $result['lastname'],
                'company'        => $result['company'],
                'address_1'      => $result['address_1'],
                'address_2'      => $result['address_2'],
                'postcode'       => $result['postcode'],
                'city'           => $result['city'],
                'zone_id'        => $result['zone_id'],
                'zone'           => $zone,
                'zone_code'      => $zone_code,
                'country_id'     => $result['country_id'],
                'country'        => $country,
                'iso_code_2'     => $iso_code_2,
                'iso_code_3'     => $iso_code_3,
                'address_format' => $address_format,
                'custom_field'   => $result['custom_field']
            ];
        }

        return $address_data;
    }

    public function getCustomerOrders($customer_id, $language_id, $start = '', $limit = '') {

        $sql = "SELECT o.*, os.name as status FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$customer_id . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND os.language_id = '" . (int)$language_id . "' ORDER BY o.order_id DESC ";

        if ($start != '' && $limit != '') {
            if ($start < 0) {
                $start = 0;
            }

            if ($limit < 1) {
                $limit = 1;
            }
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getOrderProduct($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

        return $query->row;
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getOrderOptions($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

        return $query->rows;
    }

    public function getOrderVouchers($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getVoucherTheme($voucher_id, $language_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "voucher_theme_description` WHERE voucher_id = '" . (int)$voucher_id . "' AND language_id='". (int)$language_id ."'");

        return $query->rows;
    }

    public function getOrderTotals($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

        return $query->rows;
    }

    public function getOrderHistories($order_id, $language_id) {
        $query = $this->db->query("SELECT date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$language_id . "' ORDER BY oh.date_added");

        return $query->rows;
    }

    public function getProduct($product_id) {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getProduct($product_id);
    }

    public function getCustomerWishLists($customer_id) {

        if (VERSION >= '2.1.0.1') {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_wishlist` WHERE `customer_id`='" . (int)$customer_id . "'");
            return $query->rows;
        } else {

            $customer_query = $this->db->query("SELECT wishlist FROM `" . DB_PREFIX . "customer` WHERE `customer_id`='" . (int)$customer_id . "'");
            $wishlists = [];
            if ($customer_query->num_rows) {
                if ($customer_query->row['wishlist'] && is_string($customer_query->row['wishlist'])) {
                    $wishlist = unserialize($customer_query->row['wishlist']);
                    foreach ($wishlist as $product_id) {
                        if (!in_array($product_id, $wishlists)) {
                            $wishlists[] = [
                                'customer_id' => $customer_id,
                                'product_id' => $product_id
                            ];
                        }
                    }
                }
            }

            return $wishlists;
        }
    }

    public function getCustomerMpGdprRequestLists($customer_id=0, $email='') {
        $sql = "SELECT * FROM `" . DB_PREFIX . "mpgdpr_requestlist` WHERE status=1";
        $implode = [];
        if ($customer_id) {
            $implode[] = "`customer_id`='" . (int)$customer_id . "'";
        }
        if ($email) {
            $implode[] = "`email`='" . $this->db->escape($email) . "'";
        }
        if ($implode) {
            $sql .= " AND ( ". implode(" OR ", $implode) ." )";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCustomerSearchHistory($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_search` WHERE `customer_id`='" . (int)$customer_id . "'");
        return $query->rows;
    }

    public function getCategory($category_id, $language_id=0, $store_id=0) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "category` c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND c.status = '1'";

        if ($language_id) {
            $sql .= " AND cd.language_id = '" . (int)$language_id . "'";
        }

        if ($store_id) {
            $sql .= " AND c2s.store_id = '" . (int)$store_id . "'";
        }
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getCustomerRewardPoints($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id`='" . (int)$customer_id . "' ORDER BY date_added DESC ");
        return $query->rows;
    }
    public function getCustomerRewardTotal($customer_id) {
        $query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

        return $query->row['total'];
    }

    public function getCustomerActivities($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_activity` WHERE `customer_id`='" . (int)$customer_id . "'");
        return $query->rows;
    }

    public function getCustomerTransactions($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_transaction` WHERE `customer_id`='" . (int)$customer_id . "' ORDER BY date_added DESC");
        return $query->rows;
    }
    public function getCustomerTransactionTotal($customer_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");

        return $query->row['total'];
    }
    public function getCustomerHistories($customer_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_history WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC");
        return $query->rows;
    }


    public function updatePersonalDataRequestStatus($mpgdpr_datarequest_id, $status) {
        $this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_datarequest` SET status='". (int)$status ."' WHERE `mpgdpr_datarequest_id`='" . (int)$mpgdpr_datarequest_id . "'");
    }

    public function getPersonalDataRequestByCode($code) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `code`='" . $this->db->escape($code) . "'");
        return $query->row;
    }

    public function addPersonalDataRequest($data) {
        $mpgdpr_timeout = $this->config->get('mpgdpr_timeout');
        if (!empty($mpgdpr_timeout['requestget_personaldata'])) {
            $hours = $mpgdpr_timeout['requestget_personaldata'];
        } else {
            $hours = 2;
        }

        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }

        $expire_on = date('Y-m-d H:i:s', strtotime('+ '. (int)$hours .' HOURS ' .$date));


        do {
            $code = $this->mpgdpr->token(10);
            $query = $this->db->query("SELECT code FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `code`='" . $this->db->escape($code) . "'");
            $exits = false;
            if ($query->num_rows) {
                $exits = true;
            }
        } while ($exits);
        // 01-05-2022: updation start
        // Add: email='" . (int)data.email . "'
        $this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_datarequest` SET `customer_id`='" . (int)$data['customer_id'] . "', `email`='" . $this->db->escape($data['email']) . "', `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `code`='". $this->db->escape($code) ."', `status`='". $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_added` = '". $date ."', `expire_on`='". $expire_on ."'");
        // 01-05-2022: updation end

        $mpgdpr_datarequest_id = $this->db->getLastId();

        // send email to
        // 01-05-2022: updation start
        $mail_admin = $this->config->get('mpgdpr_mail_admin');
        $mail_user = $this->config->get('mpgdpr_mail_user');
        /*
         * subject : ''
         * msg : ''
         */
        $email_template_admin = [];
        $email_template_user = [];
        $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
        if (isset($emailtemplate['apd'])) {
            if (isset($emailtemplate['apd']['admin'][ (int)$this->config->get('config_language_id') ])) {
                $email_template_admin = $emailtemplate['apd']['admin'][ (int)$this->config->get('config_language_id') ];
            }
            if (isset($emailtemplate['apd']['user'][ (int)$this->config->get('config_language_id') ])) {
                $email_template_user = $emailtemplate['apd']['user'][ (int)$this->config->get('config_language_id') ];
            }
        }

        $store_info = $this->storeInfo();

        $this->load->language('mpgdpr/mail');

        // mail to customer for confirmation they add request for access to personal data. We do verification first.
        if (!isset($mail_user['apd']) || (isset($mail_user['apd']) && $mail_user['apd'] == 1)) {
            $find = [
                '{code}',
                '{verification_url}',
                // 01-05-2022: updation start
                '{user_email}',
                '{store_name}',
                '{store_link}',
                '{store_logo}',
                // 01-05-2022: updation end
            ];
            $replace = [
                'code' => $code,
                'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                // 01-05-2022: updation start
                'user_email' => $data['email'],
                'store_name' => $store_info['name'],
                'store_link' => $store_info['href'],
                'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                // 01-05-2022: updation end
            ];

            $mail_subject = $this->language->get('text_datarequest_customer_subject');
            $mail_message = $this->language->get('text_datarequest_customer_message');

            // 01-05-2022: updation start
            if (!empty($email_template_user['subject'])) {
                $mail_subject = $email_template_user['subject'];
            }
            if (!empty($email_template_user['msg'])) {
                $mail_message = $email_template_user['msg'];
            }
            // 01-05-2022: updation end

            $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

            $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

            $mail = $this->mpgdpr->getMailObject();

            $mail->setTo($data['email']);

            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setReplyTo($this->config->get('config_email'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }


        // mail to admins for add request for access to personal data by user/customer.
        if (!isset($mail_admin['apd']) || (isset($mail_admin['apd']) && $mail_admin['apd'] == 1)) {
            // 01-05-2022: updation start
            $find = [
                '{code}',
                '{verification_url}',
                '{user_email}',
                '{store_name}',
                '{store_link}',
                '{store_logo}',
            ];
            $replace = [
                'code' => $code,
                'verification_url' => $this->url->link('extension/mpgdpr/verification_datarequest', '', true),
                'user_email' => $data['email'],
                'store_name' => $store_info['name'],
                'store_link' => $store_info['href'],
                'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
            ];
            // 01-05-2022: updation end

            $mail_subject = $this->language->get('text_datarequest_admin_subject');
            $mail_message = $this->language->get('text_datarequest_admin_message');

            // 01-05-2022: updation start
            if (!empty($email_template_admin['subject'])) {
                $mail_subject = $email_template_admin['subject'];
            }
            if (!empty($email_template_admin['msg'])) {
                $mail_message = $email_template_admin['msg'];
            }
            // 01-05-2022: updation end

            $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

            $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));


            $mail = $this->mpgdpr->getMailObject();

            $mail->setTo($this->config->get('config_email'));

            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setReplyTo($data['email']);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }
        // 01-05-2022: updation end
        return $mpgdpr_datarequest_id;
    }

    public function getRestrictProcessing($customer_id) {
        /*13 sep 2019 gdpr session starts*/
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_restrict_processing` WHERE `customer_id`='" . (int)$customer_id . "' ORDER BY date_added DESC");
        /*13 sep 2019 gdpr session ends*/
        return $query->row;
    }

    public function addRestrictProcessing($data) {
        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }
        // 01-05-2022: updation start
        // add: , `store_id`='" . (int)$this->config->get('config_store_id') . "'
         $this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_restrict_processing` SET `customer_id`='" . (int)$data['customer_id'] . "', `store_id`='" . (int)$this->config->get('config_store_id') . "', `status`='". (int)$data['status'] ."', `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_added`='". $date ."'");

        $mpgdpr_restrict_processing_id=$this->db->getLastId();
        if ((int)$data['status'] == 1) {
            $this->mailRestrictProcessing($data['customer_id']);
        }
        if (!(int)$data['status']) {
            $this->mailDisableRestrictProcessing($data['customer_id']);
        }
        return $mpgdpr_restrict_processing_id;
        // 01-05-2022: updation end
    }
    /*13 sep 2019 gdpr session starts*/
    public function editRestrictProcessing($data) {
        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }

        // 01-05-2022: updation start
        // Add: `store_id`='" . (int)$this->config->get('config_store_id') . "',
        $this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_restrict_processing` SET  `store_id`='" . (int)$this->config->get('config_store_id') . "', `status`='". (int)$data['status'] ."', `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_modified`='". $date ."' WHERE `customer_id`='" . (int)$data['customer_id'] . "'");

        if ((int)$data['status'] == 1) {
            $this->mailRestrictProcessing($data['customer_id']);
        }
        if (!(int)$data['status']) {
            $this->mailDisableRestrictProcessing($data['customer_id']);
        }
        // 01-05-2022: updation end
    }

    // 01-05-2022: updation start
    protected function mailRestrictProcessing($customer_id) {


        $info_restrictprocessing = $this->getRestrictProcessing($customer_id);
        $customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");

        $customer_info = $customer_query->row;
        if ($info_restrictprocessing && $customer_info) {
            // send email to
            // 01-05-2022: updation start
            $mail_admin = $this->config->get('mpgdpr_mail_admin');
            $mail_user = $this->config->get('mpgdpr_mail_user');

            /*
             * subject : ''
             * msg : ''
             */
            $email_template_admin = [];
            $email_template_user = [];
            $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
            if (isset($emailtemplate['rfp'])) {
                if (isset($emailtemplate['rfp']['admin'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_admin = $emailtemplate['rfp']['admin'][ (int)$this->config->get('config_language_id') ];
                }
                if (isset($emailtemplate['rfp']['user'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_user = $emailtemplate['rfp']['user'][ (int)$this->config->get('config_language_id') ];
                }
            }

            $store_info = $this->storeInfo();

            $this->load->language('mpgdpr/mail');

            // mail to customer for confirmation they enable restrict processing.
            if (!isset($mail_user['rfp']) || (isset($mail_user['rfp']) && $mail_user['rfp'] == 1)) {
                $find = [
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'user_email' => $customer_info['email'],
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];

                $mail_subject = $this->language->get('text_restrict_processing_customer_subject');
                $mail_message = $this->language->get('text_restrict_processing_customer_message');

                // 01-05-2022: updation start
                if (!empty($email_template_user['subject'])) {
                    $mail_subject = $email_template_user['subject'];
                }
                if (!empty($email_template_user['msg'])) {
                    $mail_message = $email_template_user['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($customer_info['email']);

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($this->config->get('config_email'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }

            // mail to admins for enable restrict processing by customer
            if (!isset($mail_admin['rfp']) || (isset($mail_admin['rfp']) && $mail_admin['rfp'] == 1)) {
                $find = [
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'user_email' => $customer_info['email'],
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];

                $mail_subject = $this->language->get('text_restrict_processing_admin_subject');
                $mail_message = $this->language->get('text_restrict_processing_admin_message');

                // 01-05-2022: updation start
                if (!empty($email_template_admin['subject'])) {
                    $mail_subject = $email_template_admin['subject'];
                }
                if (!empty($email_template_admin['msg'])) {
                    $mail_message = $email_template_admin['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($this->config->get('config_email'));

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($customer_info['email']);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
            // 01-05-2022: updation end
        }
    }
    protected function mailDisableRestrictProcessing($customer_id) {
        $info_restrictprocessing = $this->getRestrictProcessing($customer_id);
        $customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");

        $customer_info = $customer_query->row;
        if ($info_restrictprocessing && $customer_info) {
            // send email to
            // 01-05-2022: updation start
            $mail_admin = $this->config->get('mpgdpr_mail_admin');
            $mail_user = $this->config->get('mpgdpr_mail_user');
            /*
             * subject : ''
             * msg : ''
             */
            $email_template_admin = [];
            $email_template_user = [];
            $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
            if (isset($emailtemplate['nrfp'])) {
                if (isset($emailtemplate['nrfp']['admin'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_admin = $emailtemplate['nrfp']['admin'][ (int)$this->config->get('config_language_id') ];
                }
                if (isset($emailtemplate['nrfp']['user'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_user = $emailtemplate['nrfp']['user'][ (int)$this->config->get('config_language_id') ];
                }
            }

            $store_info = $this->storeInfo();

            $this->load->language('mpgdpr/mail');

            // mail to customer for confirmation they disable restrict processing.
            if (!isset($mail_user['nrfp']) || (isset($mail_user['nrfp']) && $mail_user['nrfp'] == 1)) {
                $find = [
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'user_email' => $customer_info['email'],
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];

                $mail_subject = $this->language->get('text_restrict_processing_no_customer_subject');
                $mail_message = $this->language->get('text_restrict_processing_no_customer_message');

                // 01-05-2022: updation start
                if (!empty($email_template_user['subject'])) {
                    $mail_subject = $email_template_user['subject'];
                }
                if (!empty($email_template_user['msg'])) {
                    $mail_message = $email_template_user['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($customer_info['email']);

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($this->config->get('config_email'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }

            // mail to admins for disable restrict processing by customer
            if (!isset($mail_admin['nrfp']) || (isset($mail_admin['nrfp']) && $mail_admin['nrfp'] == 1)) {
                $find = [
                    '{user_email}',
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'user_email' => $customer_info['email'],
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];

                $mail_subject = $this->language->get('text_restrict_processing_no_admin_subject');
                $mail_message = $this->language->get('text_restrict_processing_no_admin_message');

                // 01-05-2022: updation start
                if (!empty($email_template_admin['subject'])) {
                    $mail_subject = $email_template_admin['subject'];
                }
                if (!empty($email_template_admin['msg'])) {
                    $mail_message = $email_template_admin['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($this->config->get('config_email'));

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($customer_info['email']);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
            // 01-05-2022: updation end
        }
    }
    // 01-05-2022: updation end
    /*13 sep 2019 gdpr session ends*/
    public function updateDeleteMeRequestStatus($mpgdpr_deleteme_id, $status) {
        $this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_deleteme` SET status='". (int)$status ."' WHERE `mpgdpr_deleteme_id`='" . (int)$mpgdpr_deleteme_id . "'");
    }

    public function getDeleteMeRequestByCode($code) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `code`='" . $this->db->escape($code) . "'");
        return $query->row;
    }

	public function addDeleteMeRequest($data) {

        $mpgdpr_timeout = $this->config->get('mpgdpr_timeout');
        if (!empty($mpgdpr_timeout['requestdelete_personaldata'])) {
            $hours = $mpgdpr_timeout['requestdelete_personaldata'];
        } else {
            $hours = 2;
        }

        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }
        $expire_on = date('Y-m-d H:i:s', strtotime('+ '. (int)$hours .' HOURS ' .$date));

         do {
            $code = $this->mpgdpr->token(10);
            $query = $this->db->query("SELECT code FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `code`='" . $this->db->escape($code) . "'");
            $exits = false;
            if ($query->num_rows) {
                $exits = true;
            }
        } while ($exits);
        // 01-05-2022: updation start
        // Add: email='" . (int)data.email . "'
		$this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_deleteme` SET `customer_id`='" . (int)$data['customer_id'] . "', `email`='" . $this->db->escape($data['email']) . "', `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `code`='". $this->db->escape($code) ."', `status`='". $this->db->escape(\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING) ."', `session_id`='". $this->db->escape($this->mpgdpr->session_id()) ."', `date_added`='". $date ."', `expire_on`='". $expire_on ."'");
        // 01-05-2022: updation end
		$mpgdpr_deleteme_id=$this->db->getLastId();

        // send email to
        // 01-05-2022: updation start
        $mail_admin = $this->config->get('mpgdpr_mail_admin');
        $mail_user = $this->config->get('mpgdpr_mail_user');
        /*
         * subject : ''
         * msg : ''
         */
        $email_template_admin = [];
        $email_template_user = [];
        $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
        if (isset($emailtemplate['rdpd'])) {
            if (isset($emailtemplate['rdpd']['admin'][ (int)$this->config->get('config_language_id') ])) {
                $email_template_admin = $emailtemplate['rdpd']['admin'][ (int)$this->config->get('config_language_id') ];
            }
            if (isset($emailtemplate['rdpd']['user'][ (int)$this->config->get('config_language_id') ])) {
                $email_template_user = $emailtemplate['rdpd']['user'][ (int)$this->config->get('config_language_id') ];
            }
        }

        $store_info = $this->storeInfo();

        $this->load->language('mpgdpr/mail');

        // mail to user/customer for confirmation they add request for delete me. We do verification first.
        if (!isset($mail_user['rdpd']) || (isset($mail_user['rdpd']) && $mail_user['rdpd'] == 1)) {
            $find = [
                '{code}',
                '{verification_url}',
                // 01-05-2022: updation start
                '{user_email}',
                '{store_name}',
                '{store_link}',
                '{store_logo}',
                // 01-05-2022: updation end
            ];
            $replace = [
                'code' => $code,
                'verification_url' => $this->url->link('extension/mpgdpr/verification_deleteme', '', true),
                // 01-05-2022: updation start
                'user_email' => $data['email'],
                'store_name' => $store_info['name'],
                'store_link' => $store_info['href'],
                'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                // 01-05-2022: updation end
            ];

            $mail_subject = $this->language->get('text_deleteme_customer_subject');
            $mail_message = $this->language->get('text_deleteme_customer_message');

            // 01-05-2022: updation start
            if (!empty($email_template_user['subject'])) {
                $mail_subject = $email_template_user['subject'];
            }
            if (!empty($email_template_user['msg'])) {
                $mail_message = $email_template_user['msg'];
            }
            // 01-05-2022: updation end

            $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

            $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

            $mail = $this->mpgdpr->getMailObject();

            $mail->setTo($data['email']);

            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setReplyTo($this->config->get('config_email'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }

        // mail to admins for add request for delete me by user/customer
        if (!isset($mail_admin['rdpd']) || (isset($mail_admin['rdpd']) && $mail_admin['rdpd'] == 1)) {
            // 01-05-2022: updation start
            $find = [
                '{code}',
                '{verification_url}',
                '{user_email}',
                '{store_name}',
                '{store_link}',
                '{store_logo}',
            ];
            $replace = [
                'code' => $code,
                'verification_url' => $this->url->link('extension/mpgdpr/verification_deleteme', '', true),
                'user_email' => $data['email'],
                'store_name' => $store_info['name'],
                'store_link' => $store_info['href'],
                'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
            ];
            // 01-05-2022: updation end

            $mail_subject = $this->language->get('text_deleteme_admin_subject');
            $mail_message = $this->language->get('text_deleteme_admin_message');

            // 01-05-2022: updation start
            if (!empty($email_template_admin['subject'])) {
                $mail_subject = $email_template_admin['subject'];
            }
            if (!empty($email_template_admin['msg'])) {
                $mail_message = $email_template_admin['msg'];
            }
            // 01-05-2022: updation end

            $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

            $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

            $mail = $this->mpgdpr->getMailObject();

            $mail->setTo($this->config->get('config_email'));

            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setReplyTo($data['email']);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }
        // 01-05-2022: updation end
        return $mpgdpr_deleteme_id;
	}

    public function anonymouseCustomerData($customer_id) {

        $this->mpgdpr->log("called front model.mpgdpr.anonymouseCustomerData()");


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id='" . (int)$customer_id . "'");
        // echo "\n";
        // print_r($query->row);
        if ($query->num_rows) {
            $this->mpgdpr->log("customer data found for customer_id: {". $customer_id . "} "); //. print_r($query->row, 1)
        } else {
            $this->mpgdpr->log("customer data not found customer_id: {". $customer_id . "}");
        }

        if ($query->row) {
            // echo "\n";
            // echo "ANONYMOUSE QUERY";
            // echo "\n\n";
            $sql = "UPDATE " . DB_PREFIX . "customer SET firstname='". $this->mpgdpr->anonymouse($query->row['firstname']) ."', lastname='". $this->db->escape($this->mpgdpr->anonymouse($query->row['lastname'])) ."', email='". $this->db->escape($this->mpgdpr->anonymouse($query->row['email'])) ."', telephone='". $this->db->escape($this->mpgdpr->anonymouse($query->row['telephone'])) ."', fax='". $this->db->escape($this->mpgdpr->anonymouse($query->row['fax'])) ."', cart='". $this->db->escape($this->mpgdpr->anonymouse($query->row['cart'])) ."', wishlist='". $this->db->escape($this->mpgdpr->anonymouse($query->row['wishlist'])) ."', custom_field='". $this->db->escape($this->mpgdpr->anonymouse($query->row['custom_field'])) ."', ip='". $this->db->escape($this->mpgdpr->anonymouse($query->row['ip'])) ."' WHERE customer_id='" . (int)$customer_id . "'";

            $this->db->query($sql);

            $this->mpgdpr->log("anonymouse query customer_id: {". $customer_id . "} " . print_r($sql ,1));

            // delete other customer data
            $this->mpgdpr->log("anonymouse customer other data customer_id: {". $customer_id . "}");
            $this->deleteCustomer($customer_id, $query);

             // send last forgotten email to customer
            $this->mpgdpr->log("send last forgotten email to customer customer_id: {". $customer_id . "} email: {". $query->row['email'] ."}");

            // 01-05-2022: updation start

            $mail_user = $this->config->get('mpgdpr_mail_user');
            /*
             * subject : ''
             * msg : ''
             */
            $email_template_user = [];
            $emailtemplate = $this->config->get('mpgdpr_emailtemplate');
            if (isset($emailtemplate['rcrdpd'])) {
                if (isset($emailtemplate['rcrdpd']['user'][ (int)$this->config->get('config_language_id') ])) {
                    $email_template_user = $emailtemplate['rcrdpd']['user'][ (int)$this->config->get('config_language_id') ];
                }
            }

            $store_info = $this->storeInfo();

            $this->load->language('mpgdpr/mail');

            // final last email to customer about data forgotten, this is after completion of remove personal data process.
            if (!isset($mail_user['frdpd']) || (isset($mail_user['frdpd']) && $mail_user['frdpd'] == 1)) {
                $find = [
                    '{store_name}',
                    '{store_link}',
                    '{store_logo}',
                ];
                $replace = [
                    'store_name' => $store_info['name'],
                    'store_link' => $store_info['href'],
                    'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
                ];

                $mail_subject = $this->language->get('text_deletemecomplete_customer_subject');
                $mail_message = $this->language->get('text_deletemecomplete_customer_message');

                // 01-05-2022: updation start
                if (!empty($email_template_user['subject'])) {
                    $mail_subject = $email_template_user['subject'];
                }
                if (!empty($email_template_user['msg'])) {
                    $mail_message = $email_template_user['msg'];
                }
                // 01-05-2022: updation end

                $subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

                $message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));


                $mail = $this->mpgdpr->getMailObject();

                $mail->setTo($query->row['email']);

                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setReplyTo($this->config->get('config_email'));
                $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                // echo "\n";
                // echo "FRONT MAIL DATA";
                // echo "\n\n";
                // print_r($mail);
                $this->mpgdpr->log("Last email go to customer. customer_id: {". $customer_id . "}, to: {". $query->row['email'] ."} ");
                // $this->mpgdpr->log("Last email go to customer. customer_id: {". $customer_id . "} " . print_r($mail, 1));
                $mail->send();
            }
            // 01-05-2022: updation end
        }
    }

    public function deleteCustomer($customer_id, $query) {
        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomer({$customer_id}, query)");

        $this->deleteCustomerAddresses($customer_id, $query);
        $this->deleteCustomerActivity($customer_id, $query);
        $this->deleteCustomerHistories($customer_id, $query);
        $this->deleteCustomerIp($customer_id, $query);
        $this->deleteCustomerLogins($customer_id, $query);
        $this->deleteCustomerRewards($customer_id, $query);
        $this->deleteCustomerTransactions($customer_id, $query);
        $this->deleteCustomerWishlists($customer_id, $query);
        $this->anonymouseCustomerGDPRData($customer_id, $query);

        $this->load->model('mpgdpr/mpgdpr_others');

        $this->mpgdpr->log("if having further customer related data, then it also need to anonymouse. Thus we call separate model function mpgdpr.mpgdpr_others.anonymouseCustomerOtherData. Parameters are  customer_id: {". $customer_id . "}, query ");

        $this->model_mpgdpr_mpgdpr_others->anonymouseCustomerOtherData($customer_id, $query);
    }


    public function anonymouseCustomerGDPRData($customer_id, $query) {
        // here we all anonymouse all gdpr data. remember do not delete it.
        //table lists
        //mpgdpr_policyacceptance
        //mpgdpr_requestlist

        $this->mpgdpr->log("called front model.mpgdpr.anonymouseCustomerGDPRData({$customer_id}, query)");
        $this->mpgdpr->log("here we all anonymouse all gdpr data.");

        $query1 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_policyacceptance` WHERE (`customer_id`='" . (int)$customer_id . "' OR email='". $this->db->escape($query->row['email']) ."')");
        foreach ($query1->rows as $key => $value) {
            if (!empty($value['email'])) {
                $this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_policyacceptance SET email='". $this->mpgdpr->anonymouse($query1->row['email']) ."' WHERE mpgdpr_policyacceptance_id='" . (int)$query1->row['mpgdpr_policyacceptance_id'] . "'");
            }
        }

        $query1 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_requestlist` WHERE (`customer_id`='" . (int)$customer_id . "' OR email='". $this->db->escape($query->row['email']) ."')");
        foreach ($query1->rows as $key => $value) {
            if (!empty($value['email'])) {
                $this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_requestlist SET email='". $this->mpgdpr->anonymouse($query1->row['email']) ."' WHERE mpgdpr_requestlist_id='" . (int)$query1->row['mpgdpr_requestlist_id'] . "'");
            }
        }
        // 01-05-2022: updation start
        $query1 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE (`customer_id`='" . (int)$customer_id . "' OR email='". $this->db->escape($query->row['email']) ."')");
        foreach ($query1->rows as $key => $value) {
            if (!empty($value['email'])) {
                $this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_datarequest SET email='". $this->mpgdpr->anonymouse($query1->row['email']) ."' WHERE mpgdpr_requestlist_id='" . (int)$query1->row['mpgdpr_requestlist_id'] . "'");
            }
        }

        $query1 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE (`customer_id`='" . (int)$customer_id . "' OR email='". $this->db->escape($query->row['email']) ."')");
        foreach ($query1->rows as $key => $value) {
            if (!empty($value['email'])) {
                $this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_deleteme SET email='". $this->mpgdpr->anonymouse($query1->row['email']) ."' WHERE mpgdpr_requestlist_id='" . (int)$query1->row['mpgdpr_requestlist_id'] . "'");
            }
        }
        // 01-05-2022: updation end
    }

    public function deleteCustomerAddresses($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerAddresses({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "address` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerActivity($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerActivity({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_activity` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerHistories($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerHistories({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_history` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerIp($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerIp({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_ip` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerLogins($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerLogins({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_login` WHERE `email`='" . (int)$query->row['email'] . "'");
    }
    public function deleteCustomerRewards($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerRewards({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerTransactions($customer_id, $query) {

        $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerTransactions({$customer_id}, query)");

        $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE `customer_id`='" . (int)$customer_id . "'");
    }
    public function deleteCustomerWishlists($customer_id, $query) {
        if (VERSION >= '2.1.0.1') {

            $this->mpgdpr->log("called front model.mpgdpr.deleteCustomerWishlists({$customer_id}, query)");

            $this->db->query("DELETE FROM `" . DB_PREFIX . "customer_wishlist` WHERE `customer_id`='" . (int)$customer_id . "'");
        }
    }

    /*Add policy acceptance record*/
    public function addPolicyAcceptance($request_type, $data) {
        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_policyacceptance` SET `customer_id`='" . (int)$data['customer_id'] . "', `store_id`='" . (int)$this->config->get('config_store_id') . "', `policy_id`='" . (int)$data['policy_id'] . "', `email`='" .(isset($data['email']) ? $this->db->escape($data['email']) : ''). "', `policy_title`='" . $this->db->escape($data['policy_title']) . "', `policy_description`='" . $this->db->escape($data['policy_description']) . "', `requessttype`='" . $this->db->escape($request_type) . "', `status` = 1, `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `date_added`='". $date ."'");
        /*13 sep 2019 gdpr session starts*/
        $mpgdpr_policyacceptance_id = $this->db->getLastId();
        return $mpgdpr_policyacceptance_id;
        /*13 sep 2019 gdpr session ends*/
    }
    /*Add requests record*/
    public function addRequest($request_type, $data) {
        if (empty($data['date'])) {
            $date = date('Y-m-d H:i:s');
        } else {
            $date = $data['date'];
        }
        /*13 sep 2019 gdpr session starts*/
        $this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_requestlist` SET `customer_id`='" . (int)$data['customer_id'] . "', `email`='" .(isset($data['email']) ? $this->db->escape($data['email']) : ''). "', `store_id`='" . (int)$this->config->get('config_store_id') . "', `requessttype`='" . $this->db->escape($request_type) . "', `custom_string`='" .(isset($data['custom_string']) ? $this->db->escape($data['custom_string']) : ''). "', `status`=1, `server_ip`='" . $this->db->escape($this->mpgdpr->getServerIp()) . "', `client_ip`='" . $this->db->escape($this->mpgdpr->getClientIp()) . "', `user_agent`='". $this->db->escape($this->mpgdpr->getUserAgent()) ."', `accept_language`='". $this->db->escape($this->mpgdpr->getAcceptLanguage()) ."', `date_added`='". $date ."'");
        /*13 sep 2019 gdpr session ends*/

        return $this->db->getLastId();
    }
}
