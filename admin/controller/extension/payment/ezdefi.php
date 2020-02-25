<?php

class ControllerExtensionPaymentEzdefi extends Controller
{
    const DONE                    = 2;
    const PENDING                 = 0;
    const LIMIT_EXCEPTION_IN_PAGE = 10;

    private $error = array();

    public function index()
    {
        $data = [];
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('extension/payment/ezdefi');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['action']                           = $this->url->link('extension/payment/ezdefi/update', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_validate_api_key']             = $this->url->link('extension/payment/ezdefi/checkApiKey', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_validate_public_key']          = $this->url->link('extension/payment/ezdefi/checkPublicKey', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_delete_exception']             = $this->url->link('extension/payment/ezdefi/deleteException', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_delete_exception_by_order_id'] = $this->url->link('extension/payment/ezdefi/deleteExceptionByOrderId', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_search_exceptions']            = $this->url->link('extension/payment/ezdefi/searchExceptions', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_get_order_pending']            = $this->url->link('extension/payment/ezdefi/getAllOrderPending', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_revert_order_exception']       = $this->url->link('extension/payment/ezdefi/revertOrderException', 'user_token=' . $this->session->data['user_token'], true);

        if ($this->config->has('payment_ezdefi_status')) {
            $data['payment_ezdefi_status'] = $this->config->get('payment_ezdefi_status');
        } else {
            $data['payment_ezdefi_status'] = '';
        }

        if ($this->config->has('payment_ezdefi_gateway_api_url')) {
            $data['payment_ezdefi_gateway_api_url'] = $this->config->get('payment_ezdefi_gateway_api_url');
        } else {
            $data['payment_ezdefi_gateway_api_url'] = '';
        }

        if ($this->config->has('payment_ezdefi_api_key')) {
            $data['payment_ezdefi_api_key'] = $this->config->get('payment_ezdefi_api_key');
        } else {
            $data['payment_ezdefi_api_key'] = '';
        }

        if ($this->config->has('payment_ezdefi_public_key')) {
            $data['payment_ezdefi_public_key'] = $this->config->get('payment_ezdefi_public_key');
        } else {
            $data['payment_ezdefi_public_key'] = '';
        }

        $this->document->addStyle('view/javascript/jquery/jquery-ui/jquery-ui.css');
        $this->document->addStyle('view/stylesheet/ezdefi/jquery-validation.min.css');
        $this->document->addStyle('view/stylesheet/ezdefi/pagination.min.css');
        $this->document->addStyle('view/stylesheet/ezdefi/select2.min.css');
        $this->document->addStyle('view/stylesheet/ezdefi/ezdefi.css');

        $this->document->addScript('view/javascript/jquery/jquery-ui/jquery-ui.js');
        $this->document->addScript('view/javascript/ezdefi/select2.min.js');
        $this->document->addScript('view/javascript/ezdefi/jquery.validate.min.js');
        $this->document->addScript('view/javascript/ezdefi/pagination.min.js');
        $this->document->addScript('view/javascript/ezdefi/ezdefi.js');
        $this->document->addScript('view/javascript/ezdefi/ezdefi-exception.js');

        // API login
        $this->load->model('user/api');

        $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

        if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
            $session = new Session($this->config->get('session_engine'), $this->registry);
            $session->start();
            $this->model_user_api->deleteApiSessionBySessonId($session->getId());
            $this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
            $session->data['api_id'] = $api_info['api_id'];
            $api_token               = $session->getId();
        } else {
            $api_token = '';
        }
        $data['url_add_order_history'] = HTTPS_CATALOG . 'index.php?route=api/order/history&api_token=' . $api_token;

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/ezdefi', $data));
    }

    public function update()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $data_setting['payment_ezdefi_gateway_api_url'] = $this->request->post['payment_ezdefi_gateway_api_url'];
            $data_setting['payment_ezdefi_api_key']         = $this->request->post['payment_ezdefi_api_key'];
            $data_setting['payment_ezdefi_public_key']      = $this->request->post['payment_ezdefi_public_key'];
            $data_setting['payment_ezdefi_status']          = $this->request->post['payment_ezdefi_status'];

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_ezdefi', $data_setting);

            $this->session->data['success'] = $this->language->get('text_success');
        }
        $this->response->redirect($this->url->link('extension/payment/ezdefi', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/ezdefi')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['payment_ezdefi_gateway_api_url']) {
            $this->error['gateway_api_url'] = $this->language->get('error_gateway_api_url');
        }
        if (!$this->request->post['payment_ezdefi_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['payment_ezdefi_public_key']) {
            $this->error['public_key'] = $this->language->get('error_public_key');
        }
        return !$this->error;
    }

    public function checkApiKey()
    {
        $this->load->model('setting/setting');
        $api_url = $this->request->get['gateway_url'];
        $api_key = $this->request->get['payment_ezdefi_api_key'];

        $this->load->model('extension/payment/ezdefi');

        return $this->response->setOutput($this->model_extension_payment_ezdefi->checkApiKey($api_url, $api_key));
    }

    public function checkPublicKey()
    {
        $this->load->model('setting/setting');
        $public_key = $this->request->get['payment_ezdefi_public_key'];
        $api_url    = $this->request->get['gateway_url'];
        $api_key    = $this->request->get['api_key'];

        $this->load->model('extension/payment/ezdefi');

        return $this->response->setOutput($this->model_extension_payment_ezdefi->checkPublicKey($public_key, $api_url, $api_key));
    }

    public function install()
    {
        $this->load->language('extension/payment/ezdefi');

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('payment_ezdefi', ['payment_ezdefi_gateway_api_url' => $this->language->get('config_gateway_api_url_default'), 'payment_ezdefi_first_time_config' => 1]);

        $this->load->model('extension/payment/ezdefi');
        $this->model_extension_payment_ezdefi->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/ezdefi');
        $this->model_extension_payment_ezdefi->uninstall();
    }


    public function deleteException()
    {
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/ezdefi') && isset($this->request->post['exception_id'])) {
            $this->load->model('extension/payment/ezdefi');
            $this->load->language('extension/payment/ezdefi');

            $exception_id = $this->request->post['exception_id'];
            $this->model_extension_payment_ezdefi->deleteExceptionById($exception_id);
            return $this->response->setOutput(json_encode(['data' => ['status' => 'success', 'message' => $this->language->get('text_success')]]));
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('something_error')]]));
        }
    }

    public function deleteExceptionByOrderId()
    {
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/ezdefi') && isset($this->request->post['order_id'])) {
            $this->load->model('extension/payment/ezdefi');
            $this->load->language('extension/payment/ezdefi');

            $order_id = $this->request->post['order_id'];
            $this->model_extension_payment_ezdefi->deleteExceptionByOrderId($order_id);
            return $this->response->setOutput(json_encode(['data' => ['status' => 'success', 'message' => $this->language->get('text_success')]]));
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status' => 'failure', 'message' => $this->language->get('something_error')]]));
        }
    }

    public function searchExceptions()
    {
        $this->load->model('extension/payment/ezdefi');
        $currency         = isset($this->request->get['currency']) ? $this->request->get['currency'] : '';
        $page             = isset($this->request->get['pageNumber']) ? $this->request->get['pageNumber'] : 1;
        $keyword_amount   = isset($this->request->get['amount']) ? $this->request->get['amount'] : '';
        $keyword_order_id = isset($this->request->get['order_id']) ? $this->request->get['order_id'] : '';
        $keyword_email    = isset($this->request->get['email']) ? $this->request->get['email'] : '';

        $exceptions       = $this->model_extension_payment_ezdefi->searchExceptions($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, self::LIMIT_EXCEPTION_IN_PAGE);
        $total_exceptions = $this->model_extension_payment_ezdefi->getTotalException($keyword_amount, $keyword_order_id, $keyword_email, $currency);
        $result           = ['exceptions' => $exceptions, 'total_exceptions' => $total_exceptions];

        return $this->response->setOutput(json_encode($result));
    }

    public function revertOrderException()
    {
        $this->load->model('extension/payment/ezdefi');
        $exception_id = isset($this->request->post['exception_id']) ? $this->request->post['exception_id'] : '';
        $this->model_extension_payment_ezdefi->revertOrderException($exception_id);

        return $this->response->setOutput(json_encode(['status' => 'success']));
    }

    public function getAllOrderPending()
    {
        $this->load->model('extension/payment/ezdefi');
        $keyword = isset($this->request->get['keyword']) ? $this->request->get['keyword'] : '';
        $page    = isset($this->request->get['page']) ? $this->request->get['page'] : 1;

        $orders = $this->model_extension_payment_ezdefi->searchOrderPending($keyword, $page);
        return $this->response->setOutput(json_encode(['data' => $orders, 'status' => 'success']));
    }
}