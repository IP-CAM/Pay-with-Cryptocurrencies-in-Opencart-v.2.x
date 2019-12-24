
<?php
class ControllerExtensionPaymentEzdefi extends Controller {
    const PENDING = 0;
    const DONE = 2;
    const NOT_PAID = 0;
    const PAID_IN_TIME = 1;
    const PAID_OUT_TIME = 2;

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
        $data['coins_config'] = $this->model_extension_payment_ezdefi->getCoinsConfigWithPrice($order);
        $data['enable_simple_pay'] = $this->config->get('payment_ezdefi_enable_simple_pay');
        $data['enable_escrow_pay'] = $this->config->get('payment_ezdefi_enable_escrow_pay');

        $data['url_check_order_complete'] = $this->url->link('extension/payment/ezdefi/checkOrderComplete', '', true);
        $data['url_create_simple_payment'] = $this->url->link('extension/payment/ezdefi/createSimplePayment', '', true);
        $data['url_create_escrow_payment'] = $this->url->link('extension/payment/ezdefi/createEscrowPayment', '', true);

        return $this->load->view('extension/payment/ezdefi', $data);
    }

    public function createSimplePayment() {
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('setting/setting');
        $enable_simple_pay = $this->config->get('payment_ezdefi_enable_simple_pay');
        $callback = $this->url->link('extension/payment/ezdefi/callbackConfirmOrder', '', true);
        $coin_id = $this->request->get['coin_id'];

        if ($enable_simple_pay) {
            $this->load->model('extension/payment/ezdefi');
            $payment_info = $this->model_extension_payment_ezdefi->createPaymentSimple($coin_id, $callback);
            if ($payment_info) {
                return $this->response->setOutput($payment_info);
            } else {
                return $this->response->setOutput(json_encode(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_cant_create_payment_with_amount')]]));
            }
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_enable_simple_pay')]]));
        }
    }

    public function createEscrowPayment() {
        $this->load->language('extension/payment/ezdefi');
        $this->load->model('setting/setting');
        $enable_escrow_pay = $this->config->get('payment_ezdefi_enable_escrow_pay');
        $callback = $this->url->link('extension/payment/ezdefi/callbackConfirmOrder', '', true);
        $coin_id = $this->request->get['coin_id'];
        if ($enable_escrow_pay) {
            $this->load->model('extension/payment/ezdefi');
            $payment_info = $this->model_extension_payment_ezdefi->createPaymentEscrow($coin_id, $callback);
            if ($payment_info) {
                return $this->response->setOutput($payment_info);
            } else {
                return $this->response->setOutput(json_encode(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_cant_create_payment')]]));
            }
        } else {
            return $this->response->setOutput(json_encode(['data' => ['status'=> 'failure', 'message'=>$this->language->get('error_enable_escrow_pay')]]));
        }
    }

    public function callbackConfirmOrder() {
        $this->load->model('extension/payment/ezdefi');

        if (isset($this->request->get['paymentid'])) {
            $payment = $this->model_extension_payment_ezdefi->checkPaymentComplete($this->request->get['paymentid']);
            $uoid = $payment['uoid'];
            $order_id = explode( '-', $uoid)[0];
            $has_amount_id = explode( '-', $uoid)[1];
            if($payment['status'] == 'DONE') {
                $this->load->model('checkout/order');
                $message = 'Payment ID: '. $this->request->get['paymentid'] .', Status: '.$payment['status'].' Has amountId:'. ($has_amount_id ? 'true' : 'false');
                if ($has_amount_id == 1) {
                    $this->model_extension_payment_ezdefi->setPaidForException($payment['_id'],  self::PAID_IN_TIME, $payment['explorer_url']);
                } else {
                    $this->model_extension_payment_ezdefi->deleteExceptionByOrderId($order_id);
                }
                $this->model_checkout_order->addOrderHistory($order_id, $payment['code'],  $message, false);
            }
            if($payment['status'] == 'EXPIRED_DONE') {
                $this->model_extension_payment_ezdefi->setPaidForException($payment['_id'], self::PAID_OUT_TIME, $payment['explorer_url']);
            }
        } elseif (isset($this->request->get['explorerUrl']) && isset($this->request->get['id'])) {
            $transaction_id =  $this->request->get['id'];
            $explorer_url = $this->request->get['explorerUrl'];

            $this->model_extension_payment_ezdefi->checkTransaction($transaction_id, $explorer_url);
        }

        return;
    }

    public function checkOrderComplete() {
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_record = $this->model_checkout_order->getOrder($order_id);
        if($order_record['order_status_id'] == self::PENDING) {
            $response = [
                'data' => [
                    'status'=>'PENDING',
                ]
            ];
        } elseif($order_record['order_status_id'] == self::DONE) {
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