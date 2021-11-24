<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */

class TillitCancelModuleFrontController extends ModuleFrontController
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

            $this->restoreDuplicateCart($order->id, $order->id_customer);
            $this->chnageOrderStatus($order->id, Configuration::get('PS_TILLIT_OS_CANCELED'));

            $orderpaymentdata = $this->module->getTillitOrderPaymentData($id_order);
            if ($orderpaymentdata && isset($orderpaymentdata['tillit_order_id'])) {
                $tillit_order_id = $orderpaymentdata['tillit_order_id'];
                
                $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_id . '/cancel', [], 'POST');
                if (!isset($response)) {
                    $message = sprintf($this->module->l('Could not update status to cancelled, please check with Two admin for id %s'), $tillit_order_id);
                    $this->errors[] = $message;
                    $this->redirectWithNotifications('index.php?controller=order');
                }

                $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_id, [], 'GET');
                if (isset($response['state']) && $response['state'] == 'CANCELLED') {
                    $payment_data = array(
                        'tillit_order_id' => $response['id'],
                        'tillit_order_reference' => $response['merchant_reference'],
                        'tillit_order_state' => $response['state'],
                        'tillit_order_status' => $response['status'],
                        'tillit_day_on_invoice' => $this->module->day_on_invoice,
                        'tillit_invoice_url' => $response['invoice_url'],
                    );
                    $this->module->setTillitOrderPaymentData($order->id, $payment_data);
                }
            }
            $message = $this->module->l('Your order is cancelled.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        } else {
            $message = $this->module->l('Unable to find the requested order please contact store owner.');
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
