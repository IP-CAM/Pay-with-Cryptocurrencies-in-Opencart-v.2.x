<?php

class ControllerExtensionPaymentEzdefi extends Controller
{
    const DONE                    = 2;
    const PENDING                 = 0;
    const LIMIT_EXCEPTION_IN_PAGE = 10;

    private $error = array();

    public function index()
    {
        $data = $this->load->language('extension/payment/ezdefi');
        $this->load->model('extension/payment/ezdefi');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['action']                           = $this->url->link('extension/payment/ezdefi/update', 'token=' . $this->session->data['token'], true);
        $data['url_validate_api_key']             = $this->url->link('extension/payment/ezdefi/checkApiKey', 'token=' . $this->session->data['token'], true);
        $data['url_validate_public_key']          = $this->url->link('extension/payment/ezdefi/checkPublicKey', 'token=' . $this->session->data['token'], true);
        $data['url_delete_exception']             = $this->url->link('extension/payment/ezdefi/deleteException', 'token=' . $this->session->data['token'], true);
        $data['url_search_exceptions']            = $this->url->link('extension/payment/ezdefi/searchExceptions', 'token=' . $this->session->data['token'], true);
        $data['url_get_order_pending']            = $this->url->link('extension/payment/ezdefi/getAllOrderPending', 'token=' . $this->session->data['token'], true);
        $data['url_confirm_order']                = $this->url->link('extension/payment/ezdefi/confirmOrder', 'token=' . $this->session->data['token'], true);
        $data['url_assign_order']                 = $this->url->link('extension/payment/ezdefi/assignOrder', 'token=' . $this->session->data['token'], true);
        $data['url_revert_exception']             = $this->url->link('extension/payment/ezdefi/revertException', 'token=' . $this->session->data['token'], true);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/stripe', 'token=' . $this->session->data['token'], true)
        );


        if ($this->config->has('ezdefi_status')) {
            $data['ezdefi_status'] = $this->config->get('ezdefi_status');
        } else {
            $data['ezdefi_status'] = '';
        }

        if ($this->config->has('ezdefi_gateway_api_url')) {
            $data['ezdefi_gateway_api_url'] = $this->config->get('ezdefi_gateway_api_url');
        } else {
            $data['ezdefi_gateway_api_url'] = '';
        }

        if ($this->config->has('ezdefi_api_key')) {
            $data['ezdefi_api_key'] = $this->config->get('ezdefi_api_key');
        } else {
            $data['ezdefi_api_key'] = '';
        }

        if ($this->config->has('ezdefi_public_key')) {
            $data['ezdefi_public_key'] = $this->config->get('ezdefi_public_key');
        } else {
            $data['ezdefi_public_key'] = '';
        }

        $data['coins'] = $this->model_extension_payment_ezdefi->getCurrencies();

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
        $this->document->addScript('view/javascript/ezdefi/ezdefi-log.js');
        $this->document->addScript('view/javascript/ezdefi/ezdefi-exception-history.js');
        $this->document->addScript('view/javascript/ezdefi/ezdefi-exception.js');

        // API login
        $this->load->model('user/api');

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/ezdefi', $data));
    }

    public function update()
    {
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');
            $this->load->model('extension/payment/ezdefi');

            $data_setting['ezdefi_gateway_api_url'] = $this->request->post['ezdefi_gateway_api_url'];
            $data_setting['ezdefi_api_key']         = $this->request->post['ezdefi_api_key'];
            $data_setting['ezdefi_public_key']      = $this->request->post['ezdefi_public_key'];
            $data_setting['ezdefi_status']          = $this->request->post['ezdefi_status'];

            $this->model_setting_setting->editSetting('ezdefi', $data_setting);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->model_extension_payment_ezdefi->updateCallbackUrl(HTTP_CATALOG . '?route=extension/payment/ezdefi/callbackConfirmOrder');
        }
        $this->response->redirect($this->url->link('extension/payment/ezdefi', 'token=' . $this->session->data['token'], true));
    }

    public function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/ezdefi')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['ezdefi_gateway_api_url']) {
            $this->error['gateway_api_url'] = $this->language->get('error_gateway_api_url');
        }
        if (!$this->request->post['ezdefi_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['ezdefi_public_key']) {
            $this->error['public_key'] = $this->language->get('error_public_key');
        }
        return !$this->error;
    }

    public function checkApiKey()
    {
        $this->load->model('setting/setting');
        $api_url = $this->request->get['gateway_url'];
        $api_key = $this->request->get['ezdefi_api_key'];

        $this->load->model('extension/payment/ezdefi');

        return $this->response->setOutput($this->model_extension_payment_ezdefi->checkApiKey($api_url, $api_key));
    }

    public function checkPublicKey()
    {
        $this->load->model('setting/setting');
        $public_key = $this->request->get['ezdefi_public_key'];
        $api_url    = $this->request->get['gateway_url'];
        $api_key    = $this->request->get['api_key'];

        $this->load->model('extension/payment/ezdefi');

        return $this->response->setOutput($this->model_extension_payment_ezdefi->checkPublicKey($public_key, $api_url, $api_key));
    }

    public function install()
    {
        $this->load->language('extension/payment/ezdefi');

        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting('ezdefi', [
            'ezdefi_gateway_api_url'   => $this->language->get('config_gateway_api_url_default'),
        ]);
        $this->model_setting_setting->editSetting('ezdefi_cron', ['ezdefi_cron_last_time_delete' => time()]);

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
        $section          = isset($this->request->get['section']) ? $this->request->get['section'] : '';
        $total_exceptions = null;
        if($section == 1) {
            $exceptions = $this->model_extension_payment_ezdefi->searchException($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, self::LIMIT_EXCEPTION_IN_PAGE);
            $total_exceptions = $this->model_extension_payment_ezdefi->getTotalException($keyword_amount, $keyword_order_id, $keyword_email, $currency);
        } elseif($section == 2) {
            $exceptions = $this->model_extension_payment_ezdefi->searchExceptionHistories($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, self::LIMIT_EXCEPTION_IN_PAGE);
            $total_exceptions = $this->model_extension_payment_ezdefi->getTotalExceptionHistories($keyword_amount, $keyword_order_id, $keyword_email, $currency);
        } elseif($section == 3) {
            $exceptions = $this->model_extension_payment_ezdefi->searchLogs($keyword_amount, $keyword_order_id, $keyword_email, $currency, $page, self::LIMIT_EXCEPTION_IN_PAGE);
            $total_exceptions = $this->model_extension_payment_ezdefi->getTotalLog($keyword_amount, $keyword_order_id, $keyword_email, $currency);
        }

        $result           = ['exceptions' => $exceptions, 'total_exceptions' => $total_exceptions];
        return $this->response->setOutput(json_encode($result));
    }

    public function confirmOrder()
    {
        $this->load->model('extension/payment/ezdefi');

        $exception_id     = isset($this->request->post['exception_id']) ? $this->request->post['exception_id'] : '';
        $exception = $this->model_extension_payment_ezdefi->getExceptionById($exception_id);

        $this->model_extension_payment_ezdefi->updateException(['id'=>$exception['id']], ['order_assigned' => $exception['order_id']]);
        $this->model_extension_payment_ezdefi->updateException(['order_id'=>$exception['order_id']], ['confirmed' => 1]);

        $this->model_extension_payment_ezdefi->setProcessingForOrder($exception['order_id']);

        return $this->response->setOutput(json_encode(['status' => 'success']));
    }

    public function assignOrder() {
        $this->load->model('extension/payment/ezdefi');

        $exception_id     = isset($this->request->post['exception_id']) ? $this->request->post['exception_id'] : '';
        $order_id_to_assign = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : '';
        $exception = $this->model_extension_payment_ezdefi->getExceptionById($exception_id);

        $this->model_extension_payment_ezdefi->updateException(['id'=>$exception['id']], ['order_assigned' => $order_id_to_assign, 'confirmed' => 1]);
        if($exception['order_id']  && $order_id_to_assign  != $exception['order_id']) {
            $this->model_extension_payment_ezdefi->setPendingForOrder($exception['order_id']);
        }
        $this->model_extension_payment_ezdefi->setProcessingForOrder($order_id_to_assign);

        $this->model_extension_payment_ezdefi->updateException(['order_id'=>$order_id_to_assign], ['confirmed' => 2]);

        return $this->response->setOutput(json_encode(['status' => 'success']));
    }

    public function revertException()
    {
        $this->load->model('extension/payment/ezdefi');
        $exception_id = isset($this->request->post['exception_id']) ? $this->request->post['exception_id'] : '';
        $exception = $this->model_extension_payment_ezdefi->getExceptionById($exception_id);

        $this->model_extension_payment_ezdefi->updateException(['id'=>$exception['id']], ['order_assigned' => "NULL"]);

        if($exception['order_id'] && $exception['order_assigned'] != $exception['order_id']) {
            $this->model_extension_payment_ezdefi->setProcessingForOrder($exception['order_id']);
        }
        $this->model_extension_payment_ezdefi->setPendingForOrder($exception['order_assigned']);

        if(!$exception['explorer_url']) {
            $this->model_extension_payment_ezdefi->updateException(['order_id' => $exception['order_id']], ['confirmed' => 0]);
        } else {
            $this->model_extension_payment_ezdefi->updateException(['id' => $exception['id'], 'order_id'=>$exception['order_assigned']], ['confirmed' => 0]);
        }

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