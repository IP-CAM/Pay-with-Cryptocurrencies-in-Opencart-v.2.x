<?php

class ModelExtensionPaymentEzpay extends Model {
    public function install() {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ezpay_coin` (
			  `coin_id` int(11) NOT NULL AUTO_INCREMENT,
			  `ezpay_coin_id` varchar(255),
			  `order` int(11) NOT NULL,
              `logo` varchar(255),
			  `name` varchar(255) NOT NULL,
		      `full_name` varchar(255),
			  `discount` int(11),
		      `payment_lifetime` int(11),
		      `wallet_address` varchar(255) NOT NULL,
		      `safe_block_distant` int(11),
			  `created` DATETIME NOT NULL,
			  `modified` DATETIME NOT NULL,
			  PRIMARY KEY (`coin_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ezpay_coin`;");
    }

    public function updateCoin($data) {
        foreach($data as $key => $coinRecord) {
            if(isset($coinRecord['coin-wallet-address'])) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "ezpay_coin` SET `ezpay_coin_id` = '" . $this->db->escape($coinRecord['coin-id']) .
                    "', `order` = '" . (int)$coinRecord['coin-order'] .
                    "', `logo` = '" . $this->db->escape($coinRecord['coin-logo']) .
                    "', `name` = '" . $this->db->escape($coinRecord['coin-symbol']) .
                    "', `full_name` = '" . $this->db->escape($coinRecord['coin-name']) .
                    "', `discount` = '" .(int)$coinRecord['coin-discount'] .
                    "', `payment_lifetime` = '" . (int)$coinRecord['coin-payment-life-time'].
                    "', `wallet_address` = '" . $this->db->escape($coinRecord['coin-wallet-address']) .
                    "', `safe_block_distant` = '" . (int)$coinRecord['coin-safe-block-distant'] .
                    "', `created` = now(), `modified` = now()");
            } else {
                $this->db->query("UPDATE `" . DB_PREFIX . "ezpay_coin` SET `order` = " . (int)$coinRecord['coin-order'] . ", `created` = now(), `modified` = now()" ." WHERE `ezpay_coin_id` ='". $this->db->escape($coinRecord['coin-id'])."'");
            }
        }
    }

    public function checkUniqueCoinConfig($coinIds) {
        $sql = "SELECT `ezpay_coin_id` FROM `" . DB_PREFIX . "ezpay_coin` WHERE";

        foreach ($coinIds as $key => $coinId) {
            if ($key == 0) {
                $sql .= " `ezpay_coin_id` = '$coinId'";
            } else {
                $sql .= " OR `ezpay_coin_id` = '$coinId'";
            }
        }

        $query = $this->db->query($sql);

        echo "<pre>";
        print_r($query);die;

        if ($query->num_rows) {
            return ['unique_coins' => true];
        } else {
            return ['unique_coins' => false];
        }
    }

    public function getCoinsConfig() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezpay_coin` WHERE 1 ORDER BY `order` ASC");

        if ($query->num_rows) {
            $order = $query->rows;
            return $order;
        } else {
            return false;
        }
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