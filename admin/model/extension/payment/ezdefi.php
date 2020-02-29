<?php

class ModelExtensionPaymentEzdefi extends Model
{
    CONST TIME_REMOVE_AMOUNT_ID    = 3;
    CONST TIME_REMOVE_EXCEPTION    = 7;
    CONST ORDER_STATUS_PENDING     = 0;
    CONST NUMBER_OF_ORDERS_IN_PAGE = 10;

    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_exception` (
                `id` int auto_increment,
                `payment_id` varchar(255) default null,
			    `order_id` int(11),
                `amount_id` decimal(60,30) not null,
                `currency` varchar(255) not null,
		        `paid` int(4) default 0,
		        `has_amount` tinyint(1) not null,
		        `expiration` TIMESTAMP,
		        `explorer_url` varchar(255) default null,
			    PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;");

        $this->db->query("
            CREATE EVENT  IF NOT EXISTS `ezdefi_remove_exception_event`
            ON SCHEDULE EVERY " . self::TIME_REMOVE_EXCEPTION . " DAY
            STARTS DATE(NOW())
            DO
            DELETE FROM `" . DB_PREFIX . "ezdefi_exception` WHERE DATEDIFF( NOW( ) ,  expiration ) >= 86400;");
        $this->db->query("SET GLOBAL event_scheduler='ON';");
    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_exception`;");
        $this->db->query("DROP EVENT IF EXISTS `ezdefi_remove_exception_event`;");
    }

    //-------------------------------------------------Exception------------------------------------------------------
    public function getExceptionById($exception_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_exception` WHERE `id` ='".$exception_id."' LIMIT 1");
        if ($query->num_rows) {
            $exception = $query->rows;
            return $exception[0];
        } else {
            return false;
        }
    }


    public function getTotalException($keyword_amount, $keyword_order_id, $keyword_email, $currency)
    {
        $sql = "select count(*) as total_exceptions
            from `" . DB_PREFIX . "ezdefi_exception` `exception`
                    left join `" . DB_PREFIX . "order` `order` on exception.order_id = order.order_id
                where exception.amount_id like '%" . $keyword_amount . "%'";
        if ($keyword_order_id) {
            $sql .= " AND exception.order_id = '" . $keyword_order_id . "'";
        }
        if ($keyword_email) {
            $sql .= " AND order.email = '" . $keyword_email . "'";
        }
        if ($currency) {
            $sql .= " AND exception.currency = '" . strtoupper($currency) . "'";
        }
        $query = $this->db->query($sql);
        return $query->row['total_exceptions'];
    }

    public function deleteExceptionById($exception_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "ezdefi_exception` WHERE `id`=" . $exception_id);
    }

    public function deleteExceptionByOrderId($order_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "ezdefi_exception` WHERE `order_id`=" . $order_id);
    }

    public function searchExceptions($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, $limit)
    {
        $start = ($page - 1) * $limit;

        // search exception and prioritize `amount_id` = $amount_id to the top
        $sql = "select rank, amount_id, currency, exception.id , order.order_id, order.email, exception.expiration, exception.paid, exception.has_amount, exception.explorer_url
                from (
                    SELECT 1 as rank, id, payment_id,order_id, amount_id, currency, paid, has_amount, expiration, explorer_url
                    FROM `" . DB_PREFIX . "ezdefi_exception` 
                    WHERE amount_id = '" . $keyword_amount . "'
                    UNION 
                    SELECT 2 as rank, id, payment_id,order_id, amount_id, currency, paid, has_amount, expiration, explorer_url
                    FROM `" . DB_PREFIX . "ezdefi_exception` 
                    WHERE amount_id like '%" . $keyword_amount . "%'
                        AND amount_id != '" . $keyword_amount . "'
                ) `exception`
                    left join `" . DB_PREFIX . "order` `order` on exception.order_id = order.order_id
                where exception.amount_id like '%" . $keyword_amount . "%'";
        if ($keyword_order_id) {
            $sql .= " AND exception.order_id = '" . $keyword_order_id . "'";
        }
        if ($keyword_email) {
            $sql .= " AND order.email = '" . $keyword_email . "'";
        }
        if ($currency) {
            $sql .= " AND exception.currency = '" . strtoupper($currency) . "'";
        }
        $sql .= " ORDER BY exception.rank, exception.id DESC
                LIMIT " . $start . ',' . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function revertOrderException($exception_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_exception` SET `order_id` = null, `paid`=3 WHERE `id` = " . (int)$exception_id);
    }

    // ------------------------------order------------------------------------
    public function searchOrderPending($keyword = '', $page)
    {
        $start = self::NUMBER_OF_ORDERS_IN_PAGE * ($page - 1);
        $query = $this->db->query("SELECT email, order_id as id, date_added, total, currency_code, firstname, lastname 
                                    FROM `" . DB_PREFIX . "order` 
                                    WHERE (email like '%" . $this->db->escape($keyword) . "%'
                                        OR order_id like '%" . $this->db->escape($keyword) . "%'
                                        OR CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($keyword) . "%')
                                        AND order_status_id = " . self::ORDER_STATUS_PENDING . "
                                    LIMIT " . $start . "," . self::NUMBER_OF_ORDERS_IN_PAGE);
        return $query->rows;
    }

    public function setProcessingForOrder($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order`  SET `order_status_id`='1' WHERE  `order_id`=".$order_id);
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` (`order_id`, `order_status_id`, `notify`, `comment`, `date_added`) VALUES (".$order_id.", '2', '0', 'Set order status to Processing from Ezdefi Exception magement', DATE(NOW()) )" );
    }

    public function setPendingForOrder($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order`  SET `order_status_id`='0' WHERE  `order_id`=".$order_id);
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` (`order_id`, `order_status_id`, `notify`, `comment`, `date_added`) VALUES (".$order_id.", '0', '0', 'Set order status to Pending from Ezdefi Exception magement', DATE(NOW()) )" );
    }

    // --------------------------------------- curl---------------------------------------------
    public function checkApiKey($apiUrl, $api_key)
    {
        $list_wallet = $this->sendCurl($apiUrl . '/user/show', "GET", $api_key);

        $list_wallet_data = json_decode($list_wallet);

        if ($list_wallet_data && $list_wallet_data->code == 1 && $list_wallet_data->message == 'ok') {
            return 'true';
        } else {
            return 'false';
        }
    }

    public function checkPublicKey($public_key, $api_url, $api_key)
    {
        $website_data = $this->sendCurl($api_url . '/website/' . $public_key, "GET", $api_key);

        $website_data_obj = json_decode($website_data);

        if ($website_data_obj && $website_data_obj->code == 1 && $website_data_obj->message == 'ok') {
            return 'true';
        } else {
            return 'false';
        }
    }


    public function getCurrencies() {
        $api_url =  $this->config->get('payment_ezdefi_gateway_api_url');
        $api_key =  $this->config->get('payment_ezdefi_api_key');
        $public_key =  $this->config->get('payment_ezdefi_public_key');
        $website_data = $this->sendCurl($api_url.'/website/' . $public_key, 'GET', $api_key);

        return json_decode($website_data, true)['data']['coins'];
    }

    /**
     * @param $url
     * @param $method
     * @param null $api_key
     * @return bool|string
     */
    public function sendCurl($url, $method, $api_key = null)
    {
        $curlopt_httpheader = ['accept: application/xml'];
        if ($api_key) {
            $curlopt_httpheader[] = 'api-key: ' . $api_key;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $curlopt_httpheader,
        ));
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return $response;
        }
    }
}