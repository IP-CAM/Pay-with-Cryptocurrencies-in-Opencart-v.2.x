<?php

class ControllerExtensionPaymentEzdefi extends Controller {
    private $error = array();

    public function index() {
        $data = [];
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('extension/payment/ezdefi');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['action'] = $this->url->link('extension/payment/ezdefi/update', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_get_coin'] = $this->url->link('extension/payment/ezdefi/fetchCoin', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_validate_wallet'] = $this->url->link('extension/payment/ezdefi/checkWalletAddress', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_delete'] = $this->url->link('extension/payment/ezdefi/deleteCoinConfig', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_edit'] = $this->url->link('extension/payment/ezdefi/editCoinConfig', 'user_token=' . $this->session->data['user_token'], true);

        if($this->config->has('payment_ezdefi_gateway_api_url')){
            $data['payment_ezdefi_gateway_api_url'] = $this->config->get('payment_ezdefi_gateway_api_url');
        } else {
            $data['payment_ezdefi_gateway_api_url'] = '';
        }

        if($this->config->has('payment_ezdefi_api_key')){
            $data['payment_ezdefi_api_key'] = $this->config->get('payment_ezdefi_api_key');
        } else {
            $data['payment_ezdefi_api_key'] = '';
        }

        if($this->config->has('payment_ezdefi_order_status')){
            $data['payment_ezdefi_order_status'] = $this->config->get('payment_ezdefi_order_status');
        } else {
            $data['payment_ezdefi_order_status'] = '';
        }

        if($this->config->has('payment_ezdefi_status')){
            $data['payment_ezdefi_status'] = $this->config->get('payment_ezdefi_status');
        } else {
            $data['payment_ezdefi_status'] = '';
        }

        $data['coins_config'] = $this->model_extension_payment_ezdefi->getCoinsConfig();

        $this->document->addStyle('//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css');
        $this->document->addStyle('https://jqueryvalidation.org/files/demo/site-demos.css');
        $this->document->addStyle('view/stylesheet/ezdefi.css');

        $this->document->addScript('https://code.jquery.com/ui/1.12.1/jquery-ui.js');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js');
        $this->document->addScript('view/javascript/ezdefi/ezdefi.js');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/ezdefi', $data));
    }

    public function update() {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $dataSetting['payment_ezdefi_gateway_api_url'] = $this->request->post['payment_ezdefi_gateway_api_url'];
            $dataSetting['payment_ezdefi_api_key'] = $this->request->post['payment_ezdefi_api_key'];
            $dataSetting['payment_ezdefi_order_status'] = $this->request->post['payment_ezdefi_order_status'];

            unset($this->request->post['payment_ezdefi_gateway_api_url']);
            unset($this->request->post['payment_ezdefi_api_key']);
            unset($this->request->post['payment_ezdefi_order_status']);
            if(isset($this->request->post['payment_ezdefi_status'])) {
                $dataSetting['payment_ezdefi_status'] = $this->request->post['payment_ezdefi_status'];
                unset($this->request->post['payment_ezdefi_status']);
            }

            $this->load->model('extension/payment/ezdefi');
            $this->model_extension_payment_ezdefi->updateCoins($this->request->post);

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_ezdefi', $dataSetting);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment/ezdefi', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function validate()
    {
        $coinIds = [];
        if (!$this->user->hasPermission('modify', 'extension/payment/ezdefi')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['payment_ezdefi_gateway_api_url']) {
            $this->error['gateway_api_url'] = $this->language->get('error_gateway_api_url');
        }
        if (!$this->request->post['payment_ezdefi_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['payment_ezdefi_order_status']) {
            $this->error['order_status'] = $this->language->get('error_order_status');
        }

        $coinsConfigData = $this->request->post;
        unset($coinsConfigData['payment_ezdefi_order_status']);
        unset($coinsConfigData['payment_ezdefi_api_key']);
        unset($coinsConfigData['payment_ezdefi_gateway_api_url']);
        if(isset($coinsConfigData['payment_ezdefi_status'])) {
            unset($coinsConfigData['payment_ezdefi_status']);
        }

        if (count($coinsConfigData) > 0) {
            foreach ($coinsConfigData as $key => $coinDataData) {
                if (isset($coinDataData['coin_order']) && (trim($coinDataData['coin_order']) === '' || (!filter_var($coinDataData['coin_order'], FILTER_VALIDATE_INT) && filter_var($coinDataData['coin_order'], FILTER_VALIDATE_INT) !== 0))) {
                    $this->error['coin_order'] = $this->language->get('error_coin_order');
                }
                if (isset($coinDataData['coin_id']) && trim($coinDataData['coin_id']) === '') {
                    $this->error['coin_id'] = $this->language->get('error_coin_id');
                }
                if (isset($coinDataData['coin_symbol']) && trim($coinDataData['coin_symbol']) === '') {
                    $this->error['name'] = $this->language->get('error_name');
                }
                if (isset($coinDataData['coin_name']) && trim($coinDataData['coin_name']) === '') {
                    $this->error['full_name'] = $this->language->get('error_full_name');
                }
                if (isset($coinDataData['coin_discount']) && trim($coinDataData['coin_discount']) !== '' && ($coinDataData['coin_discount'] > 100 || $coinDataData['coin_discount'] < 0 || !filter_var($coinDataData['coin_discount'], FILTER_VALIDATE_INT) && filter_var($coinDataData['coin_discount'], FILTER_VALIDATE_INT) !== 0)) {
                    $this->error['discount'] = $this->language->get('error_discount');
                }
                if (isset($coinDataData['coin_payment_life_time']) && trim($coinDataData['coin_payment_life_time']) !== '' && !filter_var($coinDataData['coin_payment_life_time'], FILTER_VALIDATE_INT) && filter_var($coinDataData['coin_payment_life_time'], FILTER_VALIDATE_INT) !== 0) {
                    $this->error['payment_lifetime'] = $this->language->get('error_lifetime');
                }
                if (isset($coinDataData['payment_ezdefi_coin_wallet_address']) && trim($coinDataData['payment_ezdefi_coin_wallet_address']) === '') {
                    $this->error['wallet_address'] = $this->language->get('error_wallet_address');
                }
                if (isset($coinDataData['coin_safe_block_distant']) && trim($coinDataData['coin_safe_block_distant']) !== '' && !filter_var($coinDataData['coin_safe_block_distant'], FILTER_VALIDATE_INT) && filter_var($coinDataData['coin_safe_block_distant'], FILTER_VALIDATE_INT) !== 0) {
                    $this->error['safe_block_distant'] = $this->language->get('error_safe_block_distant');
                }
                if(isset($coinDataData['coin_symbol']) && isset($coinDataData['coin_name']) && isset($coinDataData['coin_wallet_address'])) {
                    $coinIds[] = $coinDataData['coin_id'];
                }
            }
            if($coinIds) {
                $this->load->model('extension/payment/ezdefi');
                if($this->model_extension_payment_ezdefi->checkUniqueCoinConfig($coinIds)['unique_coins'] == true) {
                    $this->error['unique_config_coin'] = $this->language->get('error_unique_config_coin');
                }
            }
        }

        return !$this->error;
    }

    public function deleteCoinConfig() {
        $this->load->language('extension/payment/ezdefi');

        $coinId = $this->request->post['coin_id'];
        $this->load->model('extension/payment/ezdefi');
        $deleteStatus = $this->model_extension_payment_ezdefi->deleteCoinConfigByCoinId($coinId);
        if($deleteStatus === TRUE) {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'success', 'message' =>  $this->language->get('edit_success')]]));
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' =>  $this->language->get('something_error')]]));
        }
    }

    public function editCoinConfig() {
        $this->load->language('extension/payment/ezdefi');

        $this->load->model('extension/payment/ezdefi');
        $deleteStatus = $this->model_extension_payment_ezdefi->updateCoinConfig($this->request->post);
        if($deleteStatus === TRUE) {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'success', 'message' =>  $this->language->get('edit_success')]]));
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' =>  $this->language->get('something_error')]]));
        }
    }

    public function checkWalletAddress() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');
        $this->load->model('extension/payment/ezdefi');
        return $this->model_extension_payment_ezdefi->checkWalletAddress($apiUrl, $apiKey, $this->request->get['address']);
    }

    public function install() {
        $this->load->model('extension/payment/ezdefi');
        $this->model_extension_payment_ezdefi->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/ezdefi');
        $this->model_extension_payment_ezdefi->uninstall();
    }

    public function fetchCoin() {
        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $this->load->model('extension/payment/ezdefi');
        return $this->model_extension_payment_ezdefi->getAllCoinAvailable($apiUrl,  $this->request->get['keyword']);
    }
}