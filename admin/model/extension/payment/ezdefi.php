<?php

class ModelExtensionPaymentEzdefi extends Model {
    CONST TIME_REMOVE_AMOUNT_ID = 3;
    CONST TIME_REMOVE_EXCEPTION = 7;
    CONST ORDER_STATUS_PENDING = 0;
    CONST NUMBER_OF_ORDERS_IN_PAGE = 10;

    public function install() {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_coin` (
			  `coin_id`             int auto_increment,
			  `ezdefi_coin_id`      varchar(255),
			  `order`               int(11) NOT NULL,
              `logo`                varchar(255),
		      `symbol`              varchar(255),
			  `name`                varchar(255) NOT NULL,
			  `discount`            int(4),
		      `payment_lifetime`    int(11),
		      `wallet_address`      varchar(255) NOT NULL,
		      `safe_block_distant`  int(11),
		      `decimal`             int(11) DEFAULT 8,
			  `created`             DATETIME NOT NULL,
			  `modified`            DATETIME NOT NULL,
			  PRIMARY KEY (`coin_id`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;");
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_amount` (
                `id`         int auto_increment,
                `temp`       INT not null,
                `amount`     DECIMAL(15, 4) not null,
                `tag_amount` DECIMAL(25, 14) not null,
                `expiration` TIMESTAMP  not null,
                `currency`   varchar(255) not null,
                primary key (id),
                constraint tag_amount
                    unique (tag_amount, currency)
            ) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;");
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_exception` (
                `id` int auto_increment,
			    `order_id` int(11),
                `amount_id` decimal(25,14) not null,
                `currency` varchar(255) not null,
		        `paid` int(4) default 0,
		        `has_amount` tinyint(1) not null,
		        `expiration` TIMESTAMP,
		        `explorer_url` varchar(255) default null,
			    PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;");

        $this->db->query("
            CREATE EVENT IF NOT EXISTS `ezdefi_remove_amount_id_event`
            ON SCHEDULE EVERY ".self::TIME_REMOVE_AMOUNT_ID." DAY
            STARTS DATE(NOW())
            DO
            DELETE FROM `" . DB_PREFIX . "ezdefi_tag_amount` WHERE DATEDIFF( NOW( ) ,  expiration ) >= 86400;");

        $this->db->query("
            CREATE EVENT  IF NOT EXISTS `ezdefi_remove_exception_event`
            ON SCHEDULE EVERY ".self::TIME_REMOVE_EXCEPTION." DAY
            STARTS DATE(NOW())
            DO
            DELETE FROM `" . DB_PREFIX . "ezdefi_exception` WHERE DATEDIFF( NOW( ) ,  expiration ) >= 86400;");
        $this->db->query("SET GLOBAL event_scheduler='ON';");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_coin`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_amount`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_exception`;");
    }

    public function updateCoins($data) {
        foreach($data as $key => $coin_record) {
            if(isset($coin_record['coin_wallet_address'])) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "ezdefi_coin` SET `ezdefi_coin_id` = '" . $this->db->escape($coin_record['coin_id']) .
                    "', `order` = '" . (int)$coin_record['coin_order'] .
                    "', `logo` = '" . $this->db->escape($coin_record['coin_logo']) .
                    "', `symbol` = '" . $this->db->escape($coin_record['coin_symbol']) .
                    "', `name` = '" . $this->db->escape($coin_record['coin_name']) .
                    "', `discount` = '" .(float)$coin_record['coin_discount'] .
                    "', `payment_lifetime` = '" . (int)$coin_record['coin_payment_life_time'].
                    "', `wallet_address` = '" . $this->db->escape($coin_record['coin_wallet_address']) .
                    "', `safe_block_distant` = '" . (int)$coin_record['coin_safe_block_distant'] .
                    "', `decimal` = '" . (int)$coin_record['coin_decimal'] .
                    "', `created` = now(), `modified` = now()");
            } else {
                $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_coin` SET `order` = " . (int)$coin_record['coin_order'] . ", `modified` = now()" ." WHERE `ezdefi_coin_id` ='". $this->db->escape($coin_record['coin_id'])."'");
            }
        }
    }

    public function updateCoinConfig($dataUpdate) {
         return $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_coin` SET `discount` = '" . (float)$dataUpdate['discount'] .
            "', `payment_lifetime` = '". (int)$dataUpdate['payment_lifetime'].
            "', `wallet_address` = '". $this->db->escape($dataUpdate['wallet_address']).
            "', `safe_block_distant` = '". (int)$dataUpdate['safe_block_distant'].
            "', `decimal` = '". (int)$dataUpdate['decimal'].
            "', `modified` = now()" ." WHERE `ezdefi_coin_id` ='". $this->db->escape($dataUpdate['coin_id'])."'");
    }

    public function checkUniqueCoinConfig($coinIds) {
        $sql = "SELECT `ezdefi_coin_id` FROM `" . DB_PREFIX . "ezdefi_coin` WHERE";

        foreach ($coinIds as $key => $coinId) {
            if ($key == 0) {
                $sql .= " `ezdefi_coin_id` = '$coinId'";
            } else {
                $sql .= " OR `ezdefi_coin_id` = '$coinId'";
            }
        }
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return ['unique_coins' => true];
        } else {
            return ['unique_coins' => false];
        }
    }

    public function getCoinsConfig() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_coin` WHERE 1 ORDER BY `order` ASC");

        if ($query->num_rows) {
            $order = $query->rows;
            return $order;
        } else {
            return false;
        }
    }

    public function deleteCoinConfigByCoinId($coinId) {
        return $this->db->query("DELETE FROM `" . DB_PREFIX . "ezdefi_coin` WHERE `ezdefi_coin_id` = '".$coinId."'");
    }

    public function getAllCoinAvailable($apiUrl, $keyword) {
        $url = $apiUrl . "/token/list?keyword=$keyword";
        $list_coin_support = $this->sendCurl($url, "GET");

        if($list_coin_support) {
            return $list_coin_support;
        } else {
            return json_encode(['status' => 'failure', 'message' => 'Something error when get coins']);
        }
    }

    public function checkWalletAddress($apiUrl, $api_key, $address) {
        $list_wallet = $this->sendCurl($apiUrl . '/user/list_wallet', "GET", $api_key);
        if ($list_wallet) {
            $Wallets_data = json_decode($list_wallet)->data;
            foreach ($Wallets_data as $key => $Wallet_data) {
                if($Wallet_data->address === $address) {
                    echo "true";
                    return;
                }
            }
            echo "false";
        } else {
            return json_encode(['status' => 'failure', 'message' => 'Something error when get list wallet']);
        }

    }

    //-------------------------------------------------Exception------------------------------------------------------
    public function getTotalException($keyword, $currency) {
        $sql = "select count(*) as total_exceptions
            from `".DB_PREFIX."ezdefi_exception` `exception`
                left join `".DB_PREFIX."order` `order` on exception.order_id = order.order_id
            where (order.email like '%".$keyword."%'
                OR exception.order_id like '%".$keyword."%'
                OR exception.amount_id like '%".$keyword."%')";
        if($currency) {
            $sql .= " AND exception.currency = '".strtoupper($currency)."'";
        }
        $sql .= " group by exception.amount_id, exception.currency";
        $query = $this->db->query($sql);

        return $query->num_rows;
    }

    public function deleteExceptionById($exception_id) {
        $this->db->query("DELETE FROM `".DB_PREFIX."ezdefi_exception` WHERE `id`=".$exception_id);
    }

    public function deleteExceptionByOrderId($order_id) {
        $this->db->query("DELETE FROM `".DB_PREFIX."ezdefi_exception` WHERE `order_id`=".$order_id);
    }

    public function searchExceptions($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, $limit) {
        $start = ($page-1) * $limit;

        $sql = "select amount_id, currency, exception.id , order.order_id, order.email, exception.expiration, exception.paid, exception.has_amount, exception.explorer_url
                from `".DB_PREFIX."ezdefi_exception` `exception`
                    left join `".DB_PREFIX."order` `order` on exception.order_id = order.order_id
                where exception.amount_id like '%".$keyword_amount."%'";
        if($keyword_order_id) {
            $sql .= " AND exception.order_id = '".$keyword_order_id."'";
        }
        if($keyword_email) {
            $sql .= " AND order.email = '".$keyword_email."'";
        }
        if($currency) {
            $sql .= " AND exception.currency = '".strtoupper($currency)."'";
        }
        $sql .= " ORDER BY exception.id DESC
                LIMIT ".$start.','.$limit;

//        echo $sql;die;
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function revertOrderException($exception_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_exception` SET `order_id` = null, `paid`=3 WHERE `id` = ".(int)$exception_id);
    }

    // ------------------------------order------------------------------------
    public function searchOrderPending($keyword = '', $page) {
        $start = self::NUMBER_OF_ORDERS_IN_PAGE * ($page-1);
        $query = $this->db->query("SELECT email, order_id as id, date_added, total, currency_code, firstname, lastname 
                                    FROM `".DB_PREFIX."order` 
                                    WHERE (email like '%".$this->db->escape($keyword)."%'
                                        OR order_id like '%".$this->db->escape($keyword)."%'
                                        OR CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($keyword) . "%')
                                        AND order_status_id = ".self::ORDER_STATUS_PENDING."
                                    LIMIT ".$start.",".self::NUMBER_OF_ORDERS_IN_PAGE);
        return $query->rows;
    }

    /**
     * @param $url
     * @param $method
     * @param null $api_key
     * @return bool|string
     */
    public function sendCurl($url, $method, $api_key = null) {
        $curlopt_httpheader = ['accept: application/xml'];
        if ($api_key) {
            $curlopt_httpheader[] =  'api-key: '.$api_key;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $curlopt_httpheader,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return $response;
        }
    }
}