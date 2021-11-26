<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */

class TwopaymentConfirmationModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
    }

    public function postProcess()
    {
        parent::postProcess();

        $id_order = Tools::getValue('id_order');

        if (isset($id_order) && !Tools::isEmpty($id_order)) {
            $order = new Order((int) $id_order);
            $customer = new Customer($order->id_customer);
            
            $orderpaymentdata = $this->module->getTwoOrderPaymentData($id_order);
            if ($orderpaymentdata && isset($orderpaymentdata['two_order_id'])) {
                $two_order_id = $orderpaymentdata['two_order_id'];
                
                $response = $this->module->setTwoPaymentRequest('/v1/order/' . $two_order_id, [], 'GET');
                $two_err = $this->module->getTwoErrorMessage($response);
                if ($two_err) {
                    $this->restoreDuplicateCart($order->id, $customer->id);
                    $this->chnageOrderStatus($order->id, Configuration::get('PS_TWO_OS_ERROR'));
                    $message = ($two_err != '') ? $two_err : $this->module->l('Unable to retrieve the order payment information please contact store owner.');
                    $this->errors[] = $message;
                    $this->redirectWithNotifications('index.php?controller=order');
                }

                if (isset($response['state']) && $response['state'] == 'VERIFIED') {
                    $payment_data = array(
                        'two_order_id' => $response['id'],
                        'two_order_reference' => $response['merchant_reference'],
                        'two_order_state' => $response['state'],
                        'two_order_status' => $response['status'],
                        'two_day_on_invoice' => $this->module->day_on_invoice,
                        'two_invoice_url' => $response['invoice_url'],
                    );
                    $this->module->setTwoOrderPaymentData($order->id, $payment_data);
                }
            }
            $this->chnageOrderStatus($order->id, Configuration::get('PS_TWO_OS_PREPARATION'));
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key);
        } else {
            $message = $this->module->l('Unable to find the requested order please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order&step=1');
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
