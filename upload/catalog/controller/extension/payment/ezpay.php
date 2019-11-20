
<?php
class ControllerExtensionPaymentEzpay extends Controller {

    public function index() {

        $this->load->language('extension/payment/stripe');

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

        // get order billing country
        $this->load->model('localisation/country');
        $country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

        $this->document->addStyle('view/stylesheet/ezpay.css');


        $data['url_get_create_payment'] = $this->url->link('extension/payment/ezpay/createPayment', '', true);

        $this->load->model('extension/payment/ezpay');
        $data['coins_config'] = $this->model_extension_payment_ezpay->getCoinsConfig();

        return $this->load->view('extension/payment/ezpay', $data);
    }


    public function createPayment() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezpay_gateway_api_url');
        $apiKey = $this->config->get('payment_ezpay_api_key');

        $callback = $this->url->link('extension/payment/ezpay/callbackConfirmOrder', '', true);
        $coinId = $this->request->get['coin_id'];

        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->load->model('extension/payment/ezpay');
        $this->model_extension_payment_ezpay->createEzpayPayment($apiUrl, $apiKey, $coinId, $orderInfo, $callback);
    }


    public function callbackConfirmOrder() {

    }

}