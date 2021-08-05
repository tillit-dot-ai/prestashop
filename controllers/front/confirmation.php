<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class TillitConfirmationModuleFrontController extends ModuleFrontController
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
            $cart = new Cart($order->id_cart);
            $customer = new Customer($order->id_customer);
            
            $orderpaymentdata = $this->module->getTillitOrderPaymentData($id_order);
            if ($orderpaymentdata && isset($orderpaymentdata['tillit_order_id'])) {
                $tillit_order_id = $orderpaymentdata['tillit_order_id'];
                
                $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_id, [], 'GET');
                $tillit_err = $this->module->getTillitErrorMessage($response);
                if ($tillit_err) {
                    $this->restoreDuplicateCart($order->id, $customer->id);
                    $this->chnageOrderStatus($order->id, Configuration::get('PS_TILLIT_OS_ERROR'));
                    $message = ($tillit_err != '') ? $tillit_err : $this->module->l('Unable to retrieve the order payment information please contact store owner.');
                    $this->errors[] = $message;
                    $this->redirectWithNotifications('index.php?controller=order');
                }

                if (isset($response['state']) && $response['state'] == 'VERIFIED') {
                    $payment_data = array(
                        'tillit_order_id' => $response['id'],
                        'tillit_order_reference' => $response['merchant_reference'],
                        'tillit_order_state' => $response['state'],
                        'tillit_order_status' => $response['status'],
                        'tillit_day_on_invoice' => $this->module->day_on_invoice,
                        'tillit_invoice_url' => $response['tillit_urls']['invoice_url'],
                    );
                    $this->module->setTillitOrderPaymentData($order->id, $payment_data);
                }
            }
            $this->chnageOrderStatus($order->id, Configuration::get('PS_TILLIT_OS_PREPARATION'));
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
