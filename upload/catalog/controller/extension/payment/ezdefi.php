
<?php
class ControllerExtensionPaymentEzdefi extends Controller {
    const PENDING = 0;
    const DONE = 2;

    public function index() {
        $this->load->model('checkout/order');
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/ezdefi');
        $this->load->language('extension/payment/ezdefi');

        $data['store_url'] = HTTPS_SERVER;

        $data['order_id'] = $this->session->data['order_id'];
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data['origin_value'] = (float)$order['total'];
        $data['origin_currency'] = $order['currency_code'];
        $data['coins_config'] = $this->model_extension_payment_ezdefi->getCoinsConfig();
        $data['enable_simple_pay'] = $this->config->get('payment_ezdefi_enable_simple_pay');
        $data['enable_escrow_pay'] = $this->config->get('payment_ezdefi_enable_escrow_pay');

        $data['url_check_order_complete'] = $this->url->link('extension/payment/ezdefi/checkOrderComplete', '', true);
        $data['url_create_simple_payment'] = $this->url->link('extension/payment/ezdefi/createSimplePayment', '', true);
        $data['url_create_escrow_payment'] = $this->url->link('extension/payment/ezdefi/createEscrowPayment', '', true);

        return $this->load->view('extension/payment/ezdefi', $data);
    }

    public function createSimplePayment() {
        $this->load->model('setting/setting');
        $enableSimplePay = $this->config->get('payment_ezdefi_enable_simple_pay');
        $callback = 'http://c1a673a7.ngrok.io/opencart/upload/index.php?route=extension/payment/ezdefi/callbackConfirmOrder';
        $coinId = $this->request->get['coin_id'];

        if ($enableSimplePay) {
            $this->load->model('extension/payment/ezdefi');
            $paymentInfo = $this->model_extension_payment_ezdefi->createPaymentSimple($coinId, $callback);
            if ($paymentInfo) {
                return $this->response->setOutput($paymentInfo);
            } else {
                return $this->response->setOutput(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_cant_create_payment')]]);
            }
        } else {
            return $this->response->setOutput(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_enable_simple_pay')]]);
        }
    }

    public function createEscrowPayment() {
        $this->load->model('setting/setting');
        $enableEscrowPay = $this->config->get('payment_ezdefi_enable_escrow_pay');
        $callback = 'http://c1a673a7.ngrok.io/opencart/upload/index.php?route=extension/payment/ezdefi/callbackConfirmOrder';
        $coinId = $this->request->get['coin_id'];
        if ($enableEscrowPay) {
            $this->load->model('extension/payment/ezdefi');
            $paymentInfo = $this->model_extension_payment_ezdefi->createPaymentEscrow($coinId, $callback);
            if ($paymentInfo) {
                return $this->response->setOutput($paymentInfo);
            } else {
                return $this->response->setOutput(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_cant_create_payment')]]);
            }
        } else {
            return $this->response->setOutput(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_enable_escrow_pay')]]);
        }
    }

    public function callbackConfirmOrder() {
        $uoidInfoArr = explode("-",$this->request->get['uoid']);
        $orderId = $uoidInfoArr[0];
        $hasAmountId = $uoidInfoArr[1];

        $this->load->model('setting/setting');
        $apiUrl = $this->config->get('payment_ezdefi_gateway_api_url');
        $apiKey = $this->config->get('payment_ezdefi_api_key');

        $this->load->model('extension/payment/ezdefi');
        $payment = $this->model_extension_payment_ezdefi->checkPaymentComplete($apiUrl, $apiKey, $this->request->get['paymentid'], $hasAmountId);

        if($payment['status'] == 'DONE') {
            $this->load->model('checkout/order');
            $message = 'Payment Intent ID: '. $this->request->get['paymentid'] .', Status: '.$payment['status'].' Has amountId:'. $hasAmountId ? "true" : 'false';
            $this->model_checkout_order->addOrderHistory($orderId, $payment['code'], $message, false);
        }
        if($payment['status'] == 'EXPIRED_DONE') {
            $this->model_extension_payment_ezdefi->setPaidForException($orderId, $payment['currency'], $payment['value'], $hasAmountId);
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