<?php

class ControllerExtensionPaymentEzdefi extends Controller
{
    const PENDING       = 0;
    const DONE          = 2;
    const NOT_PAID      = 0;
    const PAID_IN_TIME  = 1;
    const PAID_OUT_TIME = 2;

    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');
        $data = $this->load->language('extension/payment/ezdefi');

        $order           = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $website_data    = $this->model_extension_payment_ezdefi->getWebsiteData();
        $coin_with_price = $this->model_extension_payment_ezdefi->getCoinsWithPrice($website_data->coins, $order['total'], $order['currency_code']);

        $data['store_url']                 = HTTPS_SERVER;
        $data['order_id']                  = $this->session->data['order_id'];
        $data['origin_value']              = (float)$order['total'];
        $data['origin_currency']           = $order['currency_code'];

        //$coins = [$coin_with_price[0]];
        $data['coins']                     = json_decode(json_encode($coin_with_price), true);
        $data['enable_simple_pay']         = $website_data->website->payAnyWallet;
        $data['enable_escrow_pay']         = $website_data->website->payEzdefiWallet;
        $data['url_check_order_complete']  = $this->url->link('extension/payment/ezdefi/checkOrderComplete', '', true);
        $data['url_create_simple_payment'] = $this->url->link('extension/payment/ezdefi/createSimplePayment', '', true);
        $data['url_create_escrow_payment'] = $this->url->link('extension/payment/ezdefi/createEscrowPayment', '', true);

        return $this->load->view('extension/payment/ezdefi', $data);
    }

    public function createSimplePayment()
    {
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');
        $this->load->model('checkout/order');

        $website_data      = $this->model_extension_payment_ezdefi->getWebsiteData();
        if(!$website_data){
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
        }

        $order_info        = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $enable_simple_pay = $website_data->website->payAnyWallet;
        $callback          = $this->url->link('extension/payment/ezdefi/callbackConfirmOrder', '', true);
        $coin_id           = $this->request->get['coin_id'];
        $coin              = $this->model_extension_payment_ezdefi->getCurrency($coin_id, json_decode(json_encode($website_data->coins), true));
        $amount            = $this->model_extension_payment_ezdefi->getExchange($order_info['currency_code'], $coin['token']['symbol']) * $order_info['total'] * (100 - $coin['discount']) / 100;
        $value             = $this->model_extension_payment_ezdefi->convertExponentialToFloat($amount, $coin['decimal']);

        if ($enable_simple_pay) {
            $params       = [
                'uoid'     => $order_info['order_id'] . '-1',
                'amountId' => true,
                'coinId'   => $coin['_id'],
                'value'    => $value,
                'to'       => $coin['walletAddress'],
                'currency' => $coin['token']['symbol'] . ':' . $coin['token']['symbol'],
                'safedist' => $coin['blockConfirmation'],
                'duration' => $coin['expiration'] * 60,
                'callback' => $callback
            ];
            $payment_data = $this->model_extension_payment_ezdefi->createPayment($params);
            if ($payment_data) {
                $this->model_extension_payment_ezdefi->addException($order_info['order_id'], strtoupper($coin['token']['symbol']), $payment_data->value * pow(10, - $payment_data->decimal), $coin['expiration'], 1, null, "NULL", $payment_data->_id);

                return $this->response->setOutput(json_encode(['data' => $payment_data]));
            } else {
                return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
            }
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
        }
    }

    public function createEscrowPayment()
    {
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');
        $this->load->model('checkout/order');

        $website_data      = $this->model_extension_payment_ezdefi->getWebsiteData();
        if(!$website_data){
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
        }
        $order_info        = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $enable_simple_pay = $website_data->website->payEzdefiWallet;
        $callback          = $this->url->link('extension/payment/ezdefi/callbackConfirmOrder', '', true);
        $coin_id           = $this->request->get['coin_id'];
        $coin              = $this->model_extension_payment_ezdefi->getCurrency($coin_id, json_decode(json_encode($website_data->coins), true));
        $value             = $this->model_extension_payment_ezdefi->convertExponentialToFloat($order_info['total'] * (100 - $coin['discount']) / 100);

        if ($enable_simple_pay) {
            $params       = [
                'uoid'     => $order_info['order_id'] . '-0',
                'coinId'   => $coin['_id'],
                'value'    => $value,
                'to'       => $coin['walletAddress'],
                'currency' => $order_info['currency_code'] . ':' . $coin['token']['symbol'],
                'safedist' => $coin['blockConfirmation'],
                'duration' => $coin['expiration'] * 60,
                'callback' => $callback
            ];
            $payment_data = $this->model_extension_payment_ezdefi->createPayment($params);
            if ($payment_data) {
                $crypto_value = $payment_data->value * pow(10, - $payment_data->decimal);
                $this->model_extension_payment_ezdefi->addException($order_info['order_id'], strtoupper($coin['token']['symbol']), $crypto_value, $coin['expiration'], 0, null, "NULL", $payment_data->_id);
                return $this->response->setOutput(json_encode(['data' => $payment_data]));
            } else {
                return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
            }
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('error_cant_create_payment')]]));
        }
    }

    public function callbackConfirmOrder()
    {
        $this->load->model('extension/payment/ezdefi');

        if (isset($this->request->get['paymentid'])) {
            $payment       = $this->model_extension_payment_ezdefi->checkPaymentComplete($this->request->get['paymentid']);
            $uoid          = $payment['uoid'];
            $order_id      = explode('-', $uoid)[0];
            $has_amount_id = explode('-', $uoid)[1];
            if ($payment['status'] == 'DONE') {
                $this->load->model('checkout/order');
                $message = 'Payment ID: ' . $this->request->get['paymentid'] . ', Status: ' . $payment['status'] . ' Has amountId:' . ($has_amount_id ? 'true' : 'false');
                if ($has_amount_id == 1) {
                    $this->model_extension_payment_ezdefi->deleteExceptionByOrderId($order_id, $payment['_id']);
                    $this->model_extension_payment_ezdefi->setPaidForException($payment['_id'], self::PAID_IN_TIME, $payment['explorer_url']);
                } else {
                    $this->model_extension_payment_ezdefi->deleteExceptionByOrderId($order_id);
                }
                $this->model_checkout_order->addOrderHistory($order_id, $payment['code'], $message, false);
            }
            if ($payment['status'] == 'EXPIRED_DONE') {
                $this->model_extension_payment_ezdefi->deleteExceptionByOrderId($order_id, $payment['_id']);
                $this->model_extension_payment_ezdefi->setPaidForException($payment['_id'], self::PAID_OUT_TIME, $payment['explorer_url']);
            }
        } elseif (isset($this->request->get['explorerUrl']) && isset($this->request->get['id'])) {
            $transaction_id = $this->request->get['id'];
            $explorer_url   = $this->request->get['explorerUrl'];

            $this->model_extension_payment_ezdefi->checkTransaction($transaction_id, $explorer_url);
        }

        return;
    }

    public function checkOrderComplete()
    {
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_record = $this->model_checkout_order->getOrder($order_id);
        if ($order_record['order_status_id'] == self::PENDING) {
            $response = [
                'data' => [
                    'status' => 'PENDING',
                ]
            ];
        } elseif ($order_record['order_status_id'] == self::DONE) {
            $response = [
                'data' => [
                    'status'       => 'DONE',
                    'url_redirect' => $this->url->link('checkout/success', '', true)
                ]
            ];
        }

        return $this->response->setOutput(json_encode($response));
    }

}