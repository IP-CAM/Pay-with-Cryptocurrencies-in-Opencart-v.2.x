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

	public function getCoinsConfigWithPrice($order) {
        $this->load->model('setting/setting');
        $api_key = $this->config->get('payment_ezdefi_api_key');
        $api_url = $this->config->get('payment_ezdefi_gateway_api_url');
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_coin` WHERE 1 ORDER BY `order` ASC");
        $coins = $query->rows;
        $symbols = '';
        foreach ($coins as $coin) {
            $symbols .= $symbols === '' ? $coin['symbol'] : ','.$coin['symbol'];
        }
        $url_get_price = $api_url.'/token/exchanges?amount='.$order['total'].'&from='.$order['currency_code'].'&to='.$symbols;
        $exchanges_response = $this->sendCurl($url_get_price, 'GET', $api_key);

        if($exchanges_response) {
            $exchanges_data = json_decode($exchanges_response)->data;
            foreach ($exchanges_data as $currency_exchange) {
                foreach ($coins as $key => $coin) {
                    if ($coin['symbol'] == $currency_exchange->token) {
                        $coins[$key]['price'] = $currency_exchange->amount * ((100 - $coin['discount']) / 100);
                    }
                }
            }
        }

        return $coins;
    }

    public function createPaymentEscrow($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // get coin config
        $this->load->model('setting/setting');
        $api_url = $this->config->get('payment_ezdefi_gateway_api_url');
        $api_key = $this->config->get('payment_ezdefi_api_key');
        $coin_config = $this->getCoinConfigByEzdefiCoinId($coinId);
        //create param
        $price = $order_info['total'] - ($order_info['total'] * $coin_config['discount']/100);             // get discount price for this order
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coin_config['payment_lifetime']);
        $exchange_rate = $this->sendCurl($api_url."/token/exchange/".$order_info['currency_code']."%3A".$coin_config['symbol'], 'GET');
        $this->addException($order_info['order_id'], strtoupper($coin_config['symbol']), $price * json_decode($exchange_rate)->data, $expiration, self::HAS_AMOUNT);
        $params = "?uoid=".$order_info['order_id']."-0&to=".$coin_config['wallet_address']."&value=".$price."&currency=".$order_info['currency_code']."%3A".$coin_config['symbol']."&callback=".urlencode($callback);
        if($coin_config['payment_lifetime'] > 0) {
            $params .= "&duration=".$coin_config['payment_lifetime'];
        }
        // Send api to create payment in gateway
        return $this->sendCurl($api_url . '/payment/create'.$params, "POST", $api_key);
    }

    public function createPaymentSimple($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // get config
        $this->load->model('setting/setting');
        $api_url = $this->config->get('payment_ezdefi_gateway_api_url');
        $api_key = $this->config->get('payment_ezdefi_api_key');
        $coin_config = $this->getCoinConfigByEzdefiCoinId($coinId);
        // create params
        $origin_value = $order_info['total'] - ($order_info['total'] * $coin_config['discount']/100);                       // get discount price for this order
        $exchange_rate = $this->sendCurl($api_url."/token/exchange/".$order_info['currency_code']."%3A".$coin_config['symbol'], 'GET');
        $amount = $origin_value * json_decode($exchange_rate)->data;
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coin_config['payment_lifetime']);
        $amount_id = $this->createAmountId($coin_config['symbol'], $amount, $expiration, $coin_config['decimal']);

        $this->addException($order_info['order_id'], strtoupper($coin_config['symbol']), $amount_id, $expiration, self::NO_AMOUNT);

        $params = "?amountId=true&uoid=".$order_info['order_id']."-1&to=".$coin_config['wallet_address']."&value=".$amount_id."&currency=".$coin_config['symbol']."%3A".$coin_config['symbol']."&callback=".urlencode($callback);
        if($coin_config['payment_lifetime'] > 0) {
            $params .= "&duration=".$coin_config['payment_lifetime'];
        }

        return $this->sendCurl($api_url . '/payment/create'.$params, "POST", $api_key);
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
            $coin_config = $query->rows;
            return $coin_config[0];
        } else {
            return false;
        }
    }

    public function checkPaymentComplete($api_url, $api_key, $payment_id) {
	    $check_status_response = $this->sendCurl($api_url . '/payment/get?paymentid='.$payment_id, 'GET', $api_key);

        if ($check_status_response) {
            $response_data = json_decode($check_status_response)->data;
            $value = $response_data->value * pow(10, - $response_data->decimal);

            $payment_status = $response_data->status;
            if($payment_status == "PENDING") {
                return ['status' => "PENDING", 'code' => self::PENDING];
            } elseif ($payment_status == "DONE") {
                return ['status' => "DONE", 'code' => self::DONE, 'currency' => $response_data->currency, 'value' => $value];
            } elseif ($payment_status == 'EXPIRED_DONE') {
                return ['status' => 'EXPIRED_DONE', 'currency' => $response_data->currency, 'value' => $value];
            }
        } else {
            return ['status' => "failure"];
        }
    }

    // ----------------------------------------------------------Exception model------------------------------------------------------------
    public function addException($order_id, $currency, $amount_id, $expiration, $has_amount, $paid = 0) {
        $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_exception` (`order_id`, `currency`, `amount_id`, `expiration`, `has_amount`, `paid`) VALUES 
        ('".$order_id."', '".$currency."', '".$amount_id."', '".$expiration."', '".$has_amount."', '".$paid."')");
    }

    public function setPaidForException($order_id, $currency, $amount_id, $paid = 0) {
        $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_exception` SET `paid`=".$paid." WHERE `currency` ='".$currency."' AND `order_id`='".$order_id."' AND `amount_id`='".$amount_id."'");
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