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
        
        $tillit_order_reference = Tools::getValue('tillit_order_reference');
        
        if (isset($tillit_order_reference) && !Tools::isEmpty($tillit_order_reference)) {
            list($id_cart, ) = explode('_', $tillit_order_reference);
            $cart = new Cart($id_cart);
            
            $cartpaymentdata = $this->module->getTillitCartPaymentData($cart->id);
            
            if ($cartpaymentdata && isset($cartpaymentdata['tillit_order_id'])) {
                
                $tillit_order_id = $cartpaymentdata['tillit_order_id'];
                $response = $this->module->setTillitPaymentRequest('/v1/order/'.$tillit_order_id.'/cancel');
                
                $message = $this->module->l('Your order is cancelled.');
                $this->errors[] = $message;
                $this->redirectWithNotifications('index.php?controller=order&step=1');
                
            } else {
               $message = $this->module->l('Something went wrong while processing your order.');
               $this->errors[] = $message;
               $this->redirectWithNotifications('index.php?controller=order&step=1'); 
            }
        } else {
            $message = $this->module->l('Something went wrong while processing your order.');
            $this->errors[] = $message;
            $this->redirectWithNotifications('index.php?controller=order&step=1');
        }
    }
}
