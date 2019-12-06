<?php

class ModelExtensionPaymentEzdefi extends Model {
    const TIME_REMOVE_AMOUNT_ID = 3;
    const TIME_REMOVE_EXCEPTION = 7;

    public function install() {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_coin` (
			  `coin_id` varchar(255),
			  `order` int(11) NOT NULL,
              `logo` varchar(255),
		      `symbol` varchar(255),
			  `name` varchar(255) NOT NULL,
			  `discount` int(11),
		      `payment_lifetime` int(11),
		      `wallet_address` varchar(255) NOT NULL,
		      `safe_block_distant` int(11),
		      `decimal` int(11) DEFAULT 8,
			  `created` DATETIME NOT NULL,
			  `modified` DATETIME NOT NULL,
			  PRIMARY KEY (`coin_id`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;
			
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_tag_amount` (
                `id`         int auto_increment,
                `temp`       INT not null,
                `amount`     DECIMAL(15, 4) not null,
                `tag_amount` DECIMAL(25, 14) not null,
                `expiration` TIMESTAMP  not null,
                `currency`   varchar(255) not null,
                primary key (id),
                constraint tag_amount
                    unique (tag_amount, currency)
            ) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;
            
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_exception` (
                `exception_id` int auto_increment,
			    `order_id` int(11) NOT NULL,
                `amount_id` varchar(255),
                `currency` varchar(255)
		        `status` varchar(255),
			    PRIMARY KEY (`exception_id`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_general_ci;
             
            CREATE EVENT `ezdefi_remove_amount_id_event`
            ON SCHEDULE EVERY ".self::TIME_REMOVE_AMOUNT_ID." DAY
            STARTS DATE(NOW())
            DO
            DELETE FROM `" . DB_PREFIX . "ezdefi_tag_amount` WHERE DATEDIFF( NOW( ) ,  expiration ) >= 86400;
            SET GLOBAL event_scheduler='ON';
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_coin`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezdefi_amount_id_log`;");
    }

    public function updateCoins($data) {
        foreach($data as $key => $coinRecord) {
            if(isset($coinRecord['coin_wallet_address'])) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "ezdefi_coin` SET `ezdefi_coin_id` = '" . $this->db->escape($coinRecord['coin_id']) .
                    "', `order` = '" . (int)$coinRecord['coin_order'] .
                    "', `logo` = '" . $this->db->escape($coinRecord['coin_logo']) .
                    "', `symbol` = '" . $this->db->escape($coinRecord['coin_symbol']) .
                    "', `name` = '" . $this->db->escape($coinRecord['coin_name']) .
                    "', `discount` = '" .(int)$coinRecord['coin_discount'] .
                    "', `payment_lifetime` = '" . (int)$coinRecord['coin_payment_life_time'].
                    "', `wallet_address` = '" . $this->db->escape($coinRecord['coin_wallet_address']) .
                    "', `safe_block_distant` = '" . (int)$coinRecord['coin_safe_block_distant'] .
                    "', `created` = now(), `modified` = now()");
            } else {
                $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_coin` SET `order` = " . (int)$coinRecord['coin_order'] . ", `modified` = now()" ." WHERE `ezdefi_coin_id` ='". $this->db->escape($coinRecord['coin_id'])."'");
            }
        }
    }

    public function updateCoinConfig($dataUpdate) {
        $coinId = $dataUpdate['coin_id'];
        $discount = $dataUpdate['discount'];
        $paymentLifetime = $dataUpdate['payment_lifetime'];
        $walletAddress = $dataUpdate['wallet_address'];
        $safeBlockDistant = $dataUpdate['safe_block_distant'];

         return $this->db->query("UPDATE `" . DB_PREFIX . "ezdefi_coin` SET `discount` = '" . (int)$discount .
            "', `payment_lifetime` = '". (int)$paymentLifetime.
            "', `wallet_address` = '". $this->db->escape($walletAddress).
            "', `safe_block_distant` = '". (int)$safeBlockDistant.
            "', `modified` = now()" ." WHERE `ezdefi_coin_id` ='". $this->db->escape($coinId)."'");
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
        $listCoinSupport = $this->sendCurl($url, "GET");

        if($listCoinSupport) {
            return $listCoinSupport;
        } else {
            return json_encode(['status' => 'failure', 'message' => 'Something error when get coins']);
        }
    }

    public function checkWalletAddress($apiUrl, $api_key, $address) {
        $listWallet = $this->sendCurl($apiUrl . '/user/list_wallet', "GET", $api_key);
        if ($listWallet) {
            $WalletsData = json_decode($listWallet)->data;
            foreach ($WalletsData as $key => $WalletData) {
                if($WalletData->address === $address) {
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
    public function getTotalException() {
        $query = $this->db->query("SELECT count(id) as total_exception FROM `".DB_PREFIX."ezdefi_exception` group by amount_id, currency");
        return $query->row['total_exception'];
    }

    public function getExceptions($page, $limit) {
        $start = ($page-1) * $limit;
        $exceptions = $this->db->query("select amount_id, currency, GROUP_CONCAT(oc_ezdefi_exception.id , '--', oc_order.order_id, '--', oc_order.email, '--', oc_ezdefi_exception.expiration, '--', oc_ezdefi_exception.paid, '--', oc_ezdefi_exception.has_amount ORDER BY paid DESC) group_order
            from `".DB_PREFIX."ezdefi_exception`
                left join `".DB_PREFIX."order` on oc_ezdefi_exception.order_id = oc_order.order_id
            group by amount_id, currency
            LIMIT ".$start.",".$limit);

        return $exceptions->rows;
    }

    public function getExceptionById($exception_id) {
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."ezdefi_exception` WHERE `id`=".$exception_id);
        return $query->row;
    }

    public function deleteExceptionById($exception_id) {
        $this->db->query("DELETE FROM `".DB_PREFIX."ezdefi_exception` WHERE `id`=".$exception_id);
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