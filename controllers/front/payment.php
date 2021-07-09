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

class TillitPaymentModuleFrontController extends ModuleFrontController
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
            if ($module['name'] == 'tillit') {
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
        
        //Tillit Create order
        $paymentdata = $this->module->getTillitNewOrderData($cart);
        
        $response = $this->module->setTillitPaymentRequest('/v1/order', $paymentdata, 'POST');
        
        if(!isset($response)) {
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        if(isset($response['result']) && $response['result'] === 'failure') {
            $message = $response;
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        if(isset($response['response']['code']) && ($response['response']['code'] === 401 || $response['response']['code'] === 403)) {
            $message = $this->module->l('Website is not properly configured with Tillit payment.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        if(isset($response['response']['code']) &&  $response['response']['code'] === 400) {
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        $tillit_err = $this->module->getTillitErrorMessage($response);
        if ($tillit_err) {
            $message = ($tillit_err != '') ? $tillit_err : $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        if(isset($response['response']['code']) &&  $response['response']['code'] >= 400) {
            $message = $this->module->l('EHF Invoice is not available for this order.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
        
        if(isset($response['id']) && $response['id']) {
            $payment_data = array(
                'tillit_order_id' => $response['id'],
                'tillit_order_reference' => $response['merchant_reference'],
                'tillit_order_state' => $response['state'],
                'tillit_order_status' => $response['status'],
                'tillit_day_on_invoice' => $this->module->day_on_invoice,
                'tillit_invoice_url' => $response['tillit_urls']['invoice_url'],
            );
            
            $this->module->validateOrder($cart->id, Configuration::get('PS_TILLIT_OS_AWAITING'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null, array(), (int) $currency->id, false, $customer->secure_key);
            
            $this->module->setTillitOrderPaymentData($this->module->currentOrder, $payment_data);
            
            Tools::redirect($response['tillit_urls']['verify_order_url']);
        } else {
            $message = $this->module->l('Something went wrong please contact store owner.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order');
        }
    }
}
