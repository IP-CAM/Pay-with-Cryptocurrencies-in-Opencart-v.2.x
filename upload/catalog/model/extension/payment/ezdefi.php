<?php
class ModelExtensionPaymentEzdefi extends Model {
    const PENDING = 0;
    const DONE = 2;
    const HAS_AMOUNT = 1;
    const NO_AMOUNT = 0;


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
        // get coin config
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');
        $coinConfig = $this->getCoinConfigByEzdefiCoinId($coinId);
        //create param
        $price = $orderInfo['total'] - ($orderInfo['total'] * $coinConfig['discount']/100);             // get discount price for this order
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coinConfig['payment_lifetime']);
        $exchange_rate = $this->sendCurl($apiUrl."/token/exchange/".$orderInfo['currency_code']."%3A".$coinConfig['symbol'], 'GET');
        $this->setPaidForException($orderInfo['order_id'], $orderInfo['currency_code'], $price * json_decode($exchange_rate)->data, $expiration, self::NO_AMOUNT);
        $params = "?uoid=".$orderInfo['order_id']."-0&to=".$coinConfig['wallet_address']."&value=".$price."&currency=".$orderInfo['currency_code']."%3A".$coinConfig['symbol']."&callback=".urlencode($callback);
        if($coinConfig['payment_lifetime'] > 0) {
            $params .= "&duration=".$coinConfig['payment_lifetime'];
        }
        // Send api to create payment in gateway
        return $this->sendCurl($apiUrl . '/payment/create'.$params, "POST", $apiKey);
    }

    public function createPaymentSimple($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // get config
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');
        $coinConfig = $this->getCoinConfigByEzdefiCoinId($coinId);
        // create params
        $originValue = $orderInfo['total'] - ($orderInfo['total'] * $coinConfig['discount']/100);                       // get discount price for this order
        $exchangeRate = $this->sendCurl($apiUrl."/token/exchange/".$orderInfo['currency_code']."%3A".$coinConfig['symbol'], 'GET');
        $amount = $originValue * json_decode($exchangeRate)->data;
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coinConfig['payment_lifetime']);
        $amountId = $this->createAmountId($coinConfig['symbol'], $amount, $expiration, $coinConfig['decimal']);
        $this->setPaidForException($orderInfo['order_id'], $orderInfo['currency_code'], $amountId, $expiration, self::HAS_AMOUNT);

        $params = "?amountId=true&uoid=".$orderInfo['order_id']."-1&to=".$coinConfig['wallet_address']."&value=".$amountId."&currency=".$coinConfig['symbol']."%3A".$coinConfig['symbol']."&callback=".urlencode($callback);
        if($coinConfig['payment_lifetime'] > 0) {
            $params .= "&duration=".$coinConfig['payment_lifetime'];
        }

        return $this->sendCurl($apiUrl . '/payment/create'.$params, "POST", $apiKey);
    }

    public function createAmountId($currency, $amount, $expiration, $decimal) {
        $this->db->query("START TRANSACTION;");
        $this->db->query("INSERT INTO `".DB_PREFIX."ezdefi_amount` (`temp`, `amount`, `tag_amount`, `expiration`, `currency`)
                            SELECT (case when(MIN(t1.temp + 1) is null) then 0 else MIN(t1.temp + 1) end) as `temp`, " .$amount." as `amount`, ".$amount." + (CASE WHEN(MIN(t1.temp + 1) is NULL) THEN 0 WHEN(MIN(t1.temp+1)%2 = 0) then MIN(t1.temp+1)/2 else -(MIN(t1.temp+1)+1)/2 end) * pow(10, -".$decimal.") as `tag_amount`,'".$expiration."' as `expiration`, '".$currency. "' as `currency`
                            FROM `".DB_PREFIX."ezdefi_amount` t1
                            LEFT JOIN `".DB_PREFIX."ezdefi_amount` t2 ON t1.temp + 1 = t2.temp and t1.amount = t2.amount
                            WHERE t2.temp IS NULL
                                AND t1.amount = " .$amount."
                        ON DUPLICATE KEY UPDATE `expiration`='".$expiration."';");
        $amount_id = $this->db->query("select tag_amount from `".DB_PREFIX."ezdefi_amount` where `currency` = '" .$currency."' AND `amount`=".$amount." order by `id` DESC limit 1;");
        $this->db->query("COMMIT;");

        return $amount_id->row['tag_amount'];
    }

    public function getCoinConfigByEzdefiCoinId($coin_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_coin` WHERE `ezdefi_coin_id` ='".$coin_id."' LIMIT 1");

        if ($query->num_rows) {
            $coinConfig = $query->rows;
            return $coinConfig[0];
        } else {
            return false;
        }
    }

    public function checkPaymentComplete($api_url, $payment_id) {
	    $checkStatusResponse = $this->sendCurl($api_url . '/payment/get?paymentid='.$payment_id);
        if ($checkStatusResponse) {
            $responseData = json_decode($checkStatusResponse)->data;
            $paymentStatus = $responseData->status;
            if($paymentStatus == "PENDING") {
                return ['status' => "PENDING", 'code' => self::PENDING];
            } elseif ($paymentStatus == "DONE") {
                return ['status' => "DONE", 'code' => self::DONE];
            }
        } else {
            return ['status' => "failure"];
        }
    }

    // ----------------------------------------------------------Exception model------------------------------------------------------------
    public function setPaidForException($order_id, $currency, $amount_id, $expiration, $has_amount, $paid = 0) {
        $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_exception` (`order_id`, `currency`, `amount_id`, `expiration`, `has_amount`, `paid`) VALUES 
        ('".$order_id."', '".$currency."', '".$amount_id."', '".$expiration."', '".$has_amount."', '".$paid."')");
    }
    // --------------------------------------------------------End exception model-----------------------------------------------------------

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