<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */

class TwopaymentPaymentModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
    }

    public function postProcess()
    {
        parent::postProcess();

        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'twopayment') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            $message = $this->module->l('This payment method is not available.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $message = $this->module->l('Customer is not valid.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        //Two Create order
        $this->module->validateOrder($cart->id, Configuration::get('PS_TWO_OS_AWAITING'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null, array(), (int) $currency->id, false, $customer->secure_key);

        $paymentdata = $this->module->getTwoNewOrderData($this->module->currentOrder, $cart);

        $response = $this->module->setTwoPaymentRequest('/v1/order', $paymentdata, 'POST');

        //echo "<pre>";print_r($response);echo "</pre>";

        if (!isset($response)) {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        if (isset($response['result']) && $response['result'] === 'failure') {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $response;
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        if (isset($response['response']['code']) && ($response['response']['code'] === 401 || $response['response']['code'] === 403)) {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $this->module->l('Website is not properly configured with Two payment.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        if (isset($response['response']['code']) && $response['response']['code'] === 400) {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        $two_err = $this->module->getTwoErrorMessage($response);
        if ($two_err) {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = ($two_err != '') ? $two_err : $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        if (isset($response['response']['code']) && $response['response']['code'] >= 400) {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $this->module->l('EHF Invoice is not available for this order.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }

        if (isset($response['id']) && $response['id']) {
            $payment_data = array(
                'two_order_id' => $response['id'],
                'two_order_reference' => $response['merchant_reference'],
                'two_order_state' => $response['state'],
                'two_order_status' => $response['status'],
                'two_day_on_invoice' => $this->module->day_on_invoice,
                'two_invoice_url' => $response['invoice_url'],
            );

            $this->module->setTwoOrderPaymentData($this->module->currentOrder, $payment_data);

            Tools::redirect($response['payment_url']);
        } else {
            $this->restoreDuplicateCart($this->module->currentOrder, $customer->id);
            $this->chnageOrderStatus($this->module->currentOrder, Configuration::get('PS_TWO_OS_ERROR'));
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
    }

    protected function restoreDuplicateCart($id_order, $id_customer)
    {
        $oldCart = new Cart(Order::getCartIdStatic($id_order, $id_customer));
        $duplication = $oldCart->duplicate();
        $this->context->cookie->id_cart = $duplication['cart']->id;
        $context = $this->context;
        $context->cart = $duplication['cart'];
        CartRule::autoAddToCart($context);
        $this->context->cookie->write();
    }

    protected function chnageOrderStatus($id_order, $id_order_status)
    {
        $order = new Order((int) $id_order);
        $history = new OrderHistory();
        $history->id_order = (int) $order->id;
        if ($order->current_state != (int) $id_order_status) {
            $history->changeIdOrderState((int) $id_order_status, $order, true);
            $history->addWithemail(true);
        }
    }
}
