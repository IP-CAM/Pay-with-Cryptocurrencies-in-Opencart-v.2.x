<?php
class ModelExtensionPaymentEzdefi extends Model {
    const PENDING = 0;
    const DONE = 2;
    const HAS_AMOUNT = 1;
    const NO_AMOUNT = 0;
    const MAX_AMOUNT_DECIMAL = 30;
    const MIN_SECOND_REUSE = 10;
    const DEFAULT_DECIMAL_LIST_COIN = 12;

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/ezdefi');
		$status = true;

		//var_dump($total);die;

		if($total <= 0) {
			$status = false;
		}



		$method_data = array();
		if ($status) {
            $method_data = array(
				'code'       => 'ezdefi',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('ezdefi_sort_order')
			);
		}
		return $method_data;
	}

    public function getCoinsWithPrice($currencies, $price, $originCurrency) {
        $symbols = '';
        foreach ($currencies as $key => $currency) {
            $symbols .= $symbols === '' ? $currency->token->symbol : ','.$currency->token->symbol;
        }

        $exchangesResponse = $this->sendCurl('/token/exchanges?amount='.$price.'&from='.$originCurrency.'&to='.$symbols, 'GET');

        if($exchangesResponse) {
            $exchangesData = json_decode($exchangesResponse)->data;
            foreach ($exchangesData as $currencyExchange) {
                foreach ($currencies as $key => $currency) {
                    if ($currency->token->symbol == $currencyExchange->token) {
                        $price = $currencyExchange->amount * ((100 - $currency->discount) / 100);
                        $currencies[$key]->token->price = $this->convertExponentialToFloat($price);
                    }
                }
            }
        }
        return $currencies;
    }

    public function convertExponentialToFloat($amount, $decimal = null) {
	    if($decimal) {
            $value = sprintf('%.'.$decimal.'f',$amount);
        }
	    else {
            $value = sprintf('%.10f',$amount);
        }
        $afterDot = explode('.', $value)[1];
        $lengthToCut = 0;
        for($i = strlen($afterDot) -1; $i >=0; $i--) {
            if($afterDot[$i] === '0') {
                $lengthToCut++;
            } else {
                break;
            }
        }
        $value = substr($value, 0, strlen($value) - $lengthToCut);
        if ($value [strlen($value ) - 1] === '.') {
            $value  = substr($value , 0, -1);
        }
        return $value;
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
                return ['status' => "DONE", 'code' => self::DONE, 'uoid'=> $response_data->uoid, 'currency' => $response_data->currency, 'value' => $value, 'explorer_url' => $response_data->explorer->tx . $response_data->transactionHash, '_id' => $response_data->_id];
            } elseif ($response_data->status == 'EXPIRED_DONE') {
                return ['status' => 'EXPIRED_DONE', 'uoid'=> $response_data->uoid, 'currency' => $response_data->currency, 'value' => $value, 'explorer_url' => $response_data->explorer->tx  . $response_data->transactionHash, '_id' => $response_data->_id];
            }
        } else {
            return ['status' => "failure"];
        }
    }

    // ----------------------------------------------------------Exception model------------------------------------------------------------
    public function addException($order_id, $currency, $amount_id, $expiration, $has_amount, $paid = 0, $explorer_url = "NULL", $payment_id = null) {
        if(!$expiration) $expiration = 0;
        $this->db->query("INSERT INTO `". DB_PREFIX . "ezdefi_exception` (`payment_id`, `order_id`, `currency`, `amount_id`, `expiration`, `has_amount`, `paid`, `explorer_url`) VALUES 
        ('".$payment_id."','".$order_id."', '".$currency."', '".$amount_id."', DATE_ADD(NOW(), INTERVAL ".$expiration." SECOND), '".$has_amount."', '".$paid."', ".$explorer_url.")");
    }

    public function setPaidForException($payment_id, $paid = 0, $explorer_url = null) {
        $this->db->query("UPDATE `". DB_PREFIX . "ezdefi_exception` SET `paid`=".$paid.", `explorer_url`='".$this->db->escape($explorer_url)."'
            WHERE `payment_id` = '".$payment_id."'");
    }

    public function checkTransaction($transaction_id, $explorer_url) {
        $transaction_response = $this->sendCurl( '/transaction/get?id=' . $transaction_id, 'GET');
        $transaction_data = json_decode($transaction_response)->data;

        $value_response = $transaction_data->value * pow(10, -$transaction_data->decimal);

        if ($transaction_data->status === 'ACCEPTED') {
            $this->addException(null, $transaction_data->currency, $value_response, null, 1, 3, $transaction_data->explorerUrl);
        }
        return;
	}

	public function deleteExceptionByOrderId($order_id, $payment_id = null) {
	    $sql = "DELETE FROM `".DB_PREFIX."ezdefi_exception` WHERE `order_id`=".$order_id;

	    if($payment_id) {
            $sql .= " AND `payment_id` <> '".$payment_id."'";
        }

        $this->db->query($sql);
    }

    // --------------------------------------------------------End exception model-----------------------------------------------------------



    // -----------------------------------------------------------Curl--------------------------------------------------------------------
    public function createPayment($param) {
        return $this->sendCurl('/payment/create', 'POST', $param);
    }

    public function getWebsiteData () {
        $public_key = $api_key = $this->config->get('ezdefi_public_key');
        $website_data = $this->sendCurl('/website/' . $public_key, 'GET');
        return json_decode($website_data)->data;
    }

    public function getCurrency($id, $coins = null) {
	    if(!$coins) {
            $coins = json_decode(json_encode($this->getWebsiteData()->coins), true);
        }

        $currencyKey = array_search($id, array_column($coins, '_id'));
        return $coins[$currencyKey];
    }

    public function getCurrencies() {
        $coins = json_decode(json_encode($this->getWebsiteData()->coins), true);
        return $coins;
    }

    public function getExchange($originCurrency, $currency) {
        $exchangeRate = $this->sendCurl("/token/exchange/".$originCurrency."%3A".$currency, 'GET');

        if ($exchangeRate) {
            return json_decode($exchangeRate)->data;
        }
    }

    public function sendCurl($api, $method, $params = []) {
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');

        $api_url = $this->config->get('ezdefi_gateway_api_url');
        $api_key = $this->config->get('ezdefi_api_key');

        $curl = curl_init();

        if(!empty($params)) {
            $url =  $api_url.$api.'?'. http_build_query($params);
        } else {
            $url = $api_url.$api;
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_SSL_VERIFYPEER => false,
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