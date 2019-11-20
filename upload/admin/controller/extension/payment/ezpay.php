<?php

class ControllerExtensionPaymentEzpay extends Controller {
    private $error = array();

    public function index() {
        $data = [];
        $this->load->language('extension/payment/ezpay');
        $this->load->model('extension/payment/ezpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('extension/payment/ezpay/update', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_get_coin'] = $this->url->link('extension/payment/ezpay/fetchCoin', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_validate_wallet'] = $this->url->link('extension/payment/ezpay/checkWalletAddress', 'user_token=' . $this->session->data['user_token'], true);

        if($this->config->has('payment_ezpay_gateway_api_url')){
            $data['payment_ezpay_gateway_api_url'] = $this->config->get('payment_ezpay_gateway_api_url');
        } else {
            $data['payment_ezpay_gateway_api_url'] = '';
        }

        if($this->config->has('payment_ezpay_api_key')){
            $data['payment_ezpay_api_key'] = $this->config->get('payment_ezpay_api_key');
        } else {
            $data['payment_ezpay_api_key'] = '';
        }

        if($this->config->has('payment_ezpay_order_status')){
            $data['payment_ezpay_order_status'] = $this->config->get('payment_ezpay_order_status');
        } else {
            $data['payment_ezpay_order_status'] = '';
        }

        $data['coins_config'] = $this->model_extension_payment_ezpay->getCoinsConfig();

        $this->document->addStyle('//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css');
        $this->document->addStyle('https://jqueryvalidation.org/files/demo/site-demos.css');
        $this->document->addStyle('view/stylesheet/ezpay.css');

        $this->document->addScript('https://code.jquery.com/ui/1.12.1/jquery-ui.js');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/ezpay', $data));
    }

    public function update() {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');

            $dataSetting['payment_ezpay_gateway_api_url'] = $this->request->post['payment_ezpay_gateway_api_url'];
            $dataSetting['payment_ezpay_api_key'] = $this->request->post['payment_ezpay_api_key'];
            $dataSetting['payment_ezpay_order_status'] = $this->request->post['payment_ezpay_order_status'];
            $dataSetting['payment_ezpay_status'] = $this->request->post['enable'];
            unset($this->request->post['payment_ezpay_gateway_api_url']);
            unset($this->request->post['payment_ezpay_api_key']);
            unset($this->request->post['payment_ezpay_order_status']);
            unset($this->request->post['enable']);

            $this->load->model('extension/payment/ezpay');
            $this->model_extension_payment_ezpay->updateCoin($this->request->post);

            $this->model_setting_setting->editSetting('payment_ezpay', $dataSetting);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function validate()
    {
        $coinIds = [];

        if (!$this->user->hasPermission('modify', 'extension/payment/ezpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['payment_ezpay_gateway_api_url']) {
            $this->error['gateway_api_url'] = $this->language->get('error_gateway_api_url');
        }
        if (!$this->request->post['payment_ezpay_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['payment_ezpay_order_status']) {
            $this->error['order_status'] = $this->language->get('error_order_status');
        }

        $coinsConfigData = $this->request->post;
        unset($coinsConfigData['payment_ezpay_order_status']);
        unset($coinsConfigData['payment_ezpay_api_key']);
        unset($coinsConfigData['payment_ezpay_gateway_api_url']);
        unset($coinsConfigData['enable']);

        if (count($coinsConfigData) > 0) {
            foreach ($coinsConfigData as $key => $coinDataData) {
                if (isset($coinDataData['coin-symbol']) && !$coinDataData['coin-symbol']) {
                    $this->error['name'] = $this->language->get('error_name');
                }
                if (isset($coinDataData['coin-name']) && !$coinDataData['coin-name']) {
                    $this->error['full_name'] = $this->language->get('error_full_name');
                }
                if (isset($coinDataData['coin-discount']) && ($coinDataData['coin-discount'] > 100 || $coinDataData['coin-discount'] < 0) && $coinDataData['coin-discount']) {
                    $this->error['discount'] = $this->language->get('error_discount');
                }

                if (isset($coinDataData['coin-payment-life-time']) && !filter_var($coinDataData['coin-payment-life-time'], FILTER_VALIDATE_INT) && $coinDataData['coin-payment-life-time']) {
                    $this->error['payment_lifetime'] = $this->language->get('error_lifetime');
                }
//            if (!isset($this->request->post['payment_ezpay_coin_wallet_address'])) {
//                $this->error['wallet_address'] = $this->language->get('error_wallet_address');
//            }
                if (isset($coinDataData['coin-safe-block-distant']) && !filter_var($coinDataData['coin-safe-block-distant'], FILTER_VALIDATE_INT) && $coinDataData['coin-safe-block-distant']) {
                    $this->error['safe_block_distant'] = $this->language->get('error_safe_block_distant');
                }

                if(isset($coinDataData['coin-symbol']) && isset($coinDataData['coin-name']) && isset($coinDataData['coin-wallet-address'])) {
                    $coinIds[] = $coinDataData['coin-id'];
                }
            }
            if($coinIds) {
                $this->load->model('extension/payment/ezpay');
                if($this->model_extension_payment_ezpay->checkUniqueCoinConfig($coinIds)['unique_coins'] == true) {
                    $this->error['unique_config_coin'] = $this->language->get('error_unique_config_coin');
                }
            }

        }

        var_dump($this->error);

        return !$this->error;
    }


    public function checkWalletAddress() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezpay_gateway_api_url');
        $apiKey = $this->config->get('payment_ezpay_api_key');
        $this->load->model('extension/payment/ezpay');
        return $this->model_extension_payment_ezpay->checkWalletAddress($apiUrl, $apiKey, $this->request->get['address']);
    }


    public function install() {
        $this->load->model('extension/payment/ezpay');
        $this->model_extension_payment_ezpay->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/ezpay');
        $this->model_extension_payment_ezpay->uninstall();
    }

    public function fetchCoin() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezpay_gateway_api_url');
        $this->load->model('extension/payment/ezpay');
        return $this->model_extension_payment_ezpay->getAllCoinAvailable($apiUrl,  $this->request->get['keyword']);
    }
}