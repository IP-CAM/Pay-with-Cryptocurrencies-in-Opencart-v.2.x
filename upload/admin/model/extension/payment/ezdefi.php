<?php

class ModelExtensionPaymentEzdefi extends Model {
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
			  `created` DATETIME NOT NULL,
			  `modified` DATETIME NOT NULL,
			  PRIMARY KEY (`coin_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
			
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezdefi_amount_id_log` (
			  `price` DECIMAL(15,4) NOT NULL,
			  `amount_id` DECIMAL(25,14) NOT NULL,
			  `currency` varchar(255) NOT NULL,
			  `decimal` integer(11) NOT NULL,
              `valid` varchar(255) NOT NULL DEFAULT 0,
              `expire_timestamp` int() NOT NULL,
			  `created` DATETIME NOT NULL,
			  `modified` DATETIME NOT NULL,
			  PRIMARY KEY (`currency`, `amount_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
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
        $param = "?keyword=$keyword";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/token/list'.$param,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "accept: application/xml",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function checkWalletAddress($apiUrl, $apiKey, $address) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/user/list_wallet',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'accept: application/xml',
                'api-key: '.$apiKey
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $WalletsData = json_decode($response)->data;
            foreach ($WalletsData as $key => $WalletData) {
                if($WalletData->address === $address) {
                    echo "true";
                    return;
                }
            }
            echo "false";
        }
    }


}