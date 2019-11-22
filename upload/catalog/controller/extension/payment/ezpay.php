
<?php
class ControllerExtensionPaymentEzpay extends Controller {
    const PENDING = 0;
    const DONE = 2;

    public function index() {
        $this->load->language('extension/payment/ezpay');

        if ($this->request->server['HTTPS']) {
            $data['store_url'] = HTTPS_SERVER;
        } else {
            $data['store_url'] = HTTP_SERVER;
        }

        if($this->config->get('payment_stripe_environment') == 'live') {
            $data['payment_stripe_public_key'] = $this->config->get('payment_stripe_live_public_key');
            $data['test_mode'] = false;
        } else {
            $data['payment_stripe_public_key'] = $this->config->get('payment_stripe_test_public_key');
            $data['test_mode'] = true;
        }

        // get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data['order_id'] = $this->session->data['order_id'];

        $data['url_create_payment'] = $this->url->link('extension/payment/ezpay/createPayment', '', true);
        $data['url_check_order_complete'] = $this->url->link('extension/payment/ezpay/checkOrderComplete', '', true);

        $this->load->model('extension/payment/ezpay');
        $data['coins_config'] = $this->model_extension_payment_ezpay->getCoinsConfig();

        return $this->load->view('extension/payment/ezpay', $data);
    }


    public function createPayment() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezpay_gateway_api_url');
        $apiKey = $this->config->get('payment_ezpay_api_key');

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

//                $callback = $this->url->link('extension/payment/ezpay/callbackConfirmOrder', '', true);
        $callback = 'http://bc6547e2.ngrok.io/opencart/upload/index.php?route=extension/payment/ezpay/callbackConfirmOrder';
        $coinId = $this->request->get['coin_id'];

        $this->load->model('extension/payment/ezpay');
        $paymentInfo = $this->model_extension_payment_ezpay->createEzpayPayment($apiUrl, $apiKey, $coinId, $orderInfo, $callback);
        return $this->response->setOutput($paymentInfo);
    }

    public function callbackConfirmOrder() {
        $orderId = $this->request->get['uoid'];

        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezpay_gateway_api_url');
        $apiKey = $this->config->get('payment_ezpay_api_key');

        $this->load->model('extension/payment/ezpay');
        $paymentStatus = $this->model_extension_payment_ezpay->checkPaymentComplete($apiUrl, $apiKey, $this->request->get['paymentid']);

        if($paymentStatus['status'] == 'DONE') {
            $this->load->model('checkout/order');
            $message = 'Payment Intent ID: '. $this->request->get['paymentid'] .', Status: '.$paymentStatus['status'];
            $this->model_checkout_order->addOrderHistory($orderId, $paymentStatus['code'], $message, false);
        }
        return;
    }

    public function checkOrderComplete() {
        $orderId = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $orderRecord = $this->model_checkout_order->getOrder($orderId);
        if($orderRecord['order_status_id'] == self::PENDING) {
            $response = [
                'data' => [
                    'status'=>'PENDING',
                ]
            ];
        } elseif($orderRecord['order_status_id'] == self::DONE) {
            $response = [
                'data' => [
                    'status'=>'DONE',
                    'url_redirect' => $this->url->link('checkout/success', '', true)
                ]
            ];
        }
        return $this->response->setOutput(json_encode($response));
    }

}