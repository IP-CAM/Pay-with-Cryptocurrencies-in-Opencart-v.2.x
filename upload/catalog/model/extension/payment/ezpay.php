<?php
class ModelExtensionPaymentEzpay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/ezpay');

		$status = true;

		// stripe does not allow payment for 0 amount
		if($total <= 0) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'ezpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_ezpay_sort_order')
			);
		}

		return $method_data;
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

    public function createEzpayPayment($apiUrl, $apiKey, $coinId, $orderInfo, $callback) {
        $coinConfig = $this->getCoinConfigByEzPayCoinId($coinId);
        $params = '?uoid='.$orderInfo['order_id'].'&to='.$coinConfig['wallet_address'].'&value='.$orderInfo['total'].'&currency='.$orderInfo['currency_code'].'%3A'.$coinConfig['symbol'].'&duration='.$coinConfig['payment_lifetime'].'&callback='.$callback;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl . '/payment/create'.$params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
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
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function getCoinConfigByEzPayCoinId($coinId) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ezpay_coin` WHERE `ezpay_coin_id` ='".$coinId."' LIMIT 1");

        if ($query->num_rows) {
            $coinConfig = $query->rows;
            return $coinConfig[0];
        } else {
            return false;
        }
    }

}
