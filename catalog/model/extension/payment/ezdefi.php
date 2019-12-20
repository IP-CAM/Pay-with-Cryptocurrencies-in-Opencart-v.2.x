<?php
class ModelExtensionPaymentEzdefi extends Model {
    const PENDING = 0;
    const DONE = 2;
    const HAS_AMOUNT = 1;
    const NO_AMOUNT = 0;
    const MAX_AMOUNT_DECIMAL = 14;
    const MIN_SECOND_REUSE = 10;

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
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezdefi_coin` WHERE 1 ORDER BY `order` ASC");
        $coins = $query->rows;
        $symbols = '';
        foreach ($coins as $coin) {
            $symbols .= $symbols === '' ? $coin['symbol'] : ','.$coin['symbol'];
        }
        $exchanges_response = $this->sendCurl('/token/exchanges?amount='.$order['total'].'&from='.$order['currency_code'].'&to='.$symbols, 'GET');

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
        $coin_config = $this->getCoinConfigByEzdefiCoinId($coinId);
        //create param
        $price = $order_info['total'] - ($order_info['total'] * $coin_config['discount']/100);             // get discount price for this order
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coin_config['payment_lifetime']);
        $exchange_rate = $this->sendCurl("/token/exchange/".$order_info['currency_code']."%3A".$coin_config['symbol'], 'GET');
        $this->addException($order_info['order_id'], strtoupper($coin_config['symbol']), $price * json_decode($exchange_rate)->data, $expiration, self::NO_AMOUNT);
        $params = "?uoid=".$order_info['order_id']."-0&to=".$coin_config['wallet_address']."&value=".$price."&currency=".$order_info['currency_code']."%3A".$coin_config['symbol']."&callback=".urlencode($callback);
        if($coin_config['payment_lifetime'] > 0) {
            $params .= "&duration=".$coin_config['payment_lifetime'];
        }
        // Send api to create payment in gateway
        return $this->sendCurl( '/payment/create'.$params, "POST");
    }

    public function createPaymentSimple($coinId, $callback) {
        // get Order Info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        // get config
        $this->load->model('setting/setting');
        $coin_config = $this->getCoinConfigByEzdefiCoinId($coinId);
        // create params
        $origin_value = $order_info['total'] - ($order_info['total'] * $coin_config['discount']/100);                       // get discount price for this order
        $exchange_rate = $this->sendCurl("/token/exchange/".$order_info['currency_code']."%3A".$coin_config['symbol'], 'GET');
        //create amount id
        $amount = $origin_value * json_decode($exchange_rate)->data;
        $expiration = date('Y-m-d h:i:sa',strtotime(date('Y-m-d h:i:sa')) + $coin_config['payment_lifetime']);
        $amount_id = $this->createAmountId($coin_config['symbol'], $amount, $coin_config['payment_lifetime'], $coin_config['decimal'], $this->config->get('payment_ezdefi_variation'));
        if($amount_id) {
            $this->addException($order_info['order_id'], strtoupper($coin_config['symbol']), $amount_id, $expiration, self::HAS_AMOUNT);
            $params = "?amountId=true&uoid=".$order_info['order_id']."-1&to=".$coin_config['wallet_address']."&value=".$amount_id."&currency=".$coin_config['symbol']."%3A".$coin_config['symbol']."&callback=".urlencode($callback);
            if($coin_config['payment_lifetime'] > 0) {
                $params .= "&duration=".$coin_config['payment_lifetime'];
            }
            return $this->sendCurl( '/payment/create'.$params, "POST");
        } else {
            return false;
        }
    }

    public function createAmountId($currency, $amount, $expiration, $decimal, $variation) {
	    $oldAmount = $this->db->query("SELECT `tag_amount`, `id`
                                            from `".DB_PREFIX."ezdefi_amount` 
                                            where `currency`='".$currency."' 
                                                AND `amount`='".$amount."' 
                                                AND `expiration` < DATE_SUB(NOW(), INTERVAL ".self::MIN_SECOND_REUSE." SECOND) 
                                            order by `temp` 
                                            LIMIT 1;");
	    if ($oldAmount->row) {
            $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_amount` SET `expiration`= DATE_ADD(NOW(), INTERVAL ".$expiration." SECOND)  WHERE `id`=".$oldAmount->row['id']);
            return $oldAmount->row['tag_amount'];
        } else {
            $this->db->query("START TRANSACTION;");
            $this->db->query("INSERT INTO `".DB_PREFIX."ezdefi_amount` (`temp`, `amount`, `tag_amount`, `expiration`, `currency`)
                            SELECT (case when(MIN(t1.temp + 1) is null) then 0 else MIN(t1.temp + 1) end) as `temp`, " .$amount." as `amount`, ".$amount." + (CASE WHEN(MIN(t1.temp + 1) is NULL) THEN 0 WHEN(MIN(t1.temp+1)%2 = 0) then MIN(t1.temp+1)/2 else -(MIN(t1.temp+1)+1)/2 end) * pow(10, -".$decimal.") as `tag_amount`, DATE_ADD(NOW(), INTERVAL ".$expiration." SECOND) as `expiration`, '".$currency. "' as `currency`
                            FROM `".DB_PREFIX."ezdefi_amount` t1
                            LEFT JOIN `".DB_PREFIX."ezdefi_amount` t2 ON t1.temp + 1 = t2.temp and t1.amount = t2.amount
                            WHERE t2.temp IS NULL
                                AND t1.amount = ROUND(" .$amount.", ".self::MAX_AMOUNT_DECIMAL.");");
            $amount_id = $this->db->query("select tag_amount from `".DB_PREFIX."ezdefi_amount` where `currency` = '" .$currency."' AND `amount`=ROUND(" .$amount.", ".self::MAX_AMOUNT_DECIMAL.") ORDER BY id DESC LIMIT 1;");
            $this->db->query("COMMIT;");
            $variationValue = abs($amount_id->row['tag_amount'] - $amount);

            if ($variationValue > ($amount * (float)$variation) / 100 ) {
                return false;
            }
            return $amount_id->row['tag_amount'];
        }
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

    public function checkPaymentComplete($payment_id) {
	    $check_status_response = $this->sendCurl( '/payment/get?paymentid='.$payment_id, 'GET');

        if ($check_status_response) {
            $response_data = json_decode($check_status_response)->data;
            $value = $response_data->value * pow(10, - $response_data->decimal);

            if($response_data->status == "PENDING") {
                return ['status' => "PENDING", 'code' => self::PENDING];
            } elseif ($response_data->status == "DONE") {
                return ['status' => "DONE", 'code' => self::DONE, 'uoid'=> $response_data->uoid, 'currency' => $response_data->currency, 'value' => $value, 'explorer_url' => $response_data->explorer->tx . $response_data->transactionHash];
            } elseif ($response_data->status == 'EXPIRED_DONE') {
                return ['status' => 'EXPIRED_DONE', 'uoid'=> $response_data->uoid, 'currency' => $response_data->currency, 'value' => $value, 'explorer_url' => $response_data->explorer->tx . $response_data->transactionHash];
            }
        } else {
            return ['status' => "failure"];
        }
    }

    // ----------------------------------------------------------Exception model------------------------------------------------------------
    public function addException($order_id, $currency, $amount_id, $expiration, $has_amount, $paid = 0, $explorer_url = null, $unknown_tx_explorer_url = null) {
        $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_exception` (`order_id`, `currency`, `amount_id`, `expiration`, `has_amount`, `paid`, `explorer_url`, `unknown_tx_explorer_url`) VALUES 
        ('".$order_id."', '".$currency."', '".$amount_id."', '".$expiration."', '".$has_amount."', '".$paid."', '".$explorer_url."', '".$unknown_tx_explorer_url."')");
    }

    public function setPaidForException($order_id, $currency, $amount_id, $paid = 0, $has_amount, $explorer_url = null) {
        $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_exception` SET `paid`=".$paid.", `explorer_url`='".$this->db->escape($explorer_url)."' 
            WHERE `currency` ='".$currency."' AND `order_id`='".$order_id."' AND `amount_id`=".$amount_id." AND `has_amount`=".$has_amount."");
    }

    public function checkTransaction($transaction_id, $explorer_url) {
        $transaction_response = $this->sendCurl( '/transaction/get?id=' . $transaction_id, 'GET');
        $transaction_data = json_decode($transaction_response)->data;

        $value_response = $transaction_data->value * pow(10, -$transaction_data->decimal);

        if ($transaction_data->status === 'ACCEPTED' && $transaction_data->explorerUrl === $explorer_url) {
            $this->addException(null, $transaction_data->currency, $value_response, null, 1, 3, null, $transaction_data->explorerUrl);
        }
        return;
	}

    // --------------------------------------------------------End exception model-----------------------------------------------------------
    public function sendCurl($api, $method) {
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');

        $api_url = $this->config->get('payment_ezdefi_gateway_api_url');
        $api_key = $this->config->get('payment_ezdefi_api_key');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url. $api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['accept: application/xml', 'api-key: '.$api_key],
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