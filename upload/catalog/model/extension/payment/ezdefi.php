<?php
class ModelExtensionPaymentEzdefi extends Model {
    const PENDING = 0;
    const DONE = 2;

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/ezdefi');

		$status = true;

		// stripe does not allow payment for 0 amount
		if($total <= 0) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'ezdefi',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_ezdefi_sort_order')
			);
		}

		return $method_data;
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

    public function createPaymentEscrow($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderInfo['total'] = 1;
        // get coin config
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');
        $coinConfig = $this->getCoinConfigByEzdefiCoinId($coinId);
        //create param
        $value = $orderInfo['total'] - ($orderInfo['total'] * $coinConfig['discount']/100);             // get discount price for this order
        $params = "?uoid=".$orderInfo['order_id']."-0&to=".$coinConfig['wallet_address']."&value=".$value."&currency=".$orderInfo['currency_code']."%3A".$coinConfig['symbol']."&callback=".urlencode($callback);
        if($coinConfig['payment_lifetime'] > 0) {
            $params .= "&duration=".$coinConfig['payment_lifetime'];
        }
        // Send api to create payment in gateway
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/payment/create'.$params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'accept: application/xml',
                'api-key: '.$apiKey
            ),
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

    public function createPaymentSimple($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // get config
        $this->load->model('setting/setting');
        $decimal = $this->config->get('payment_ezdefi_decimal');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');
        $coinConfig = $this->getCoinConfigByEzdefiCoinId($coinId);
        // create params
        $orderInfo['total'] = 1;
        $price = $orderInfo['total'] - ($orderInfo['total'] * $coinConfig['discount']/100);                             // get discount price for this order
        $amountId = $this->createAmountId($coinConfig['symbol'], $price, $decimal);
        $params = "?amountId=true&uoid=".$orderInfo['order_id']."-1&to=".$coinConfig['wallet_address']."&value=".$amountId."&currency=".$orderInfo['currency_code']."%3A".$coinConfig['symbol']."&callback=".urlencode($callback);
        if($coinConfig['payment_lifetime'] > 0) {
            $params .= "&duration=".$coinConfig['payment_lifetime'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/payment/create'.$params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'accept: application/xml',
                'api-key: '.$apiKey
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return false;
        } else {
            $expiredTime = json_decode($response)->data->expiredTime;
            $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_amount_id_log` SET `expired_time`='".$expiredTime."' WHERE `amount_id` = ".$amountId." AND `currency` = '".$coinConfig['symbol']."'");

            return $response;
        }
    }

    /**
     * @param $currency
     * @param $price
     * @param $decimal
     * @return float|int
     */
    public function createAmountId($currency, $price, $decimal) {
	    // get amount Id form valid amountId
        date_default_timezone_set('UTC');
        $queryValidAmountId = $this->db->query("SELECT `amount_id` FROM `" . DB_PREFIX . "ezdefi_amount_id_log` WHERE `price` = ".$price." AND `currency` = '".$currency."' AND valid=1 AND `expired_time` < '".date('Y-m-d H:i:s', time())."'");
        if($queryValidAmountId->num_rows) {
            return $queryValidAmountId->row['amount_id'];
        }

        // if dont have valid amountId, find satisfy amountId and insert it
        $query = $this->db->query("SELECT `amount_id`, `price`, count(`amount_id`) as `count_amount_id`, Abs(price - amount_id) as `abs` FROM `" . DB_PREFIX . "ezdefi_amount_id_log` WHERE `price` = ".$price." AND `currency` = '".$currency."'AND `decimal` = ".$decimal." GROUP BY `abs`");
        if($query->num_rows) {
            $amountIdRecords = $query->rows;
            $flagInsertedAmountId = false;

            foreach ($amountIdRecords as $key => $amountIdRecord) {
                if ($amountIdRecord['count_amount_id'] == 1 && $amountIdRecord['abs'] != 0) {
                    // Insert amount with abs same this record's abs
                    $amountId = $amountIdRecord['amount_id'] - $amountIdRecord['price'] > 0 ? $amountIdRecord['price'] - $amountIdRecord['abs'] : $amountIdRecord['price'] + $amountIdRecord['abs'];
                    $flagInsertedAmountId = true;
                    $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_amount_id_log` SET `valid` = 1, `decimal` = ".$decimal.", `price` = ". $price.", `currency` ='".$currency."', `amount_id` = ".$amountId .", `created` = now()");
                    return $amountId;
                }
            }
            // Insert amount id with new abs
            if($flagInsertedAmountId == false) {
                $maxDiff = $amountIdRecord['abs'];
                $amountId = $price + $maxDiff + 1/pow(10, $decimal);
                if ((int)round($amountId * pow(10, $decimal)) % 10 == 0 ) {
                    $amountId += 1/pow(10, $decimal);
                }
                $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_amount_id_log` SET `valid` = 1, `decimal` = ".$decimal.", `price` = ". $price.", `currency` ='".$currency."', `amount_id` = ".$amountId .", `created` = now()");
                return $amountId;
            }
        } else {
            // Insert when this price do not have amount_id
            $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_amount_id_log` SET `valid` = 1, `decimal` = ".$decimal.", `price` = ". $price.", `currency` ='".$currency."', `amount_id` = ".$price .", `created` = now()");
            return $price;
        }
    }

    /**
     * @param $coinId
     * @return bool
     */
    public function getCoinConfigByEzdefiCoinId($coinId) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_coin` WHERE `ezdefi_coin_id` ='".$coinId."' LIMIT 1");

        if ($query->num_rows) {
            $coinConfig = $query->rows;
            return $coinConfig[0];
        } else {
            return false;
        }
    }

    /**
     * @param $apiUrl
     * @param $apiKey
     * @param $paymentId
     * @return array?
     */
    public function checkPaymentComplete($apiUrl, $apiKey, $paymentId, $hasAmountId) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/payment/get?paymentid='.$paymentId,
            CURLOPT_RETURNTRANSFER => true,
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
            $responseData = json_decode($response)->data;
            $paymentStatus = $responseData->status;
            if($paymentStatus == "PENDING") {
                return ['status' => "PENDING", 'code' => self::PENDING];
            } elseif ($paymentStatus == "DONE") {
                if($hasAmountId) {
                    $amountId = $responseData->originValue;
                    $curency = $responseData->currency;
                    $this->checkAmountIdUsed($amountId, $curency);
                    $this->removeExpiredAmountId($amountId, $curency);
                }
                return ['status' => "DONE", 'code' => self::DONE];
            }
            echo $response;
        }
    }

    public function checkAmountIdUsed($amountId, $currency) {
        $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_amount_id_log` SET `valid`=0 WHERE `amount_id` = ".$amountId." AND `currency` = '".$currency."'");
    }

    public function removeExpiredAmountId() {
        $this->db->query("DELETE FROM `". DB_PREFIX . "ezdefi_amount_id_log` WHERE `created` < now() - 86400");
    }
}
