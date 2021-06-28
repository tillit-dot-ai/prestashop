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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tillit extends PaymentModule
{

    protected $output = '';
    protected $errors = array();

    public function __construct()
    {
        $this->name = 'tillit';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Tillit';
        $this->bootstrap = true;
        $this->module_key = '';
        $this->author_address = '';
        parent::__construct();
        $this->languages = Language::getLanguages(false);
        $this->displayName = $this->l('Tillit Payment');
        $this->description = $this->l('This module allows any merchant to accept payments with tillit payment gateway.');
        $this->merchant_id = Configuration::get('PS_TILLIT_MERACHANT_ID');
        $this->api_key = Configuration::get('PS_TILLIT_API_KEY');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook('displayAdminOrderTabLink') &&
            $this->registerHook('displayAdminOrderTabContent') &&
            $this->registerHook('displayOrderDetail') &&
            $this->installTillitSettings();
    }

    protected function installTillitSettings()
    {
        $installData = array();
        foreach ($this->languages as $language) {
            $installData['PS_TILLIT_TITLE'][(int) $language['id_lang']] = 'Business invoice 30 days';
            $installData['PS_TILLIT_SUB_TITLE'][(int) $language['id_lang']] = 'Receive the invoice via EHF and PDF';
        }
        Configuration::updateValue('PS_TILLIT_TITLE', $installData['PS_TILLIT_TITLE']);
        Configuration::updateValue('PS_TILLIT_SUB_TITLE', $installData['PS_TILLIT_SUB_TITLE']);
        Configuration::updateValue('PS_TILLIT_PAYMENT_MODE', 'stg');
        Configuration::updateValue('PS_TILLIT_MERACHANT_ID', '');
        Configuration::updateValue('PS_TILLIT_API_KEY', '');
        Configuration::updateValue('PS_TILLIT_PRODUCT_TYPE', 'product_funded');
        Configuration::updateValue('PS_TILLIT_DAY_ON_INVOICE', 14);
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_NAME', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_ID', 1);
        Configuration::updateValue('PS_TILLIT_FANILIZE_PURCHASE', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_ORDER_INTENT', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_B2B_B2C_RADIO', 1);
        return true;
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->unregisterHook('actionAdminControllerSetMedia') &&
            $this->unregisterHook('actionFrontControllerSetMedia') &&
            $this->unregisterHook('paymentOptions') &&
            $this->unregisterHook('displayPaymentReturn') &&
            $this->unregisterHook('displayAdminOrderLeft') &&
            $this->unregisterHook('displayAdminOrderTabLink') &&
            $this->unregisterHook('displayAdminOrderTabContent') &&
            $this->unregisterHook('displayOrderDetail') &&
            $this->uninstallTillitSettings();
    }

    protected function uninstallTillitSettings()
    {
        Configuration::deleteByName('PS_TILLIT_TITLE');
        Configuration::deleteByName('PS_TILLIT_SUB_TITLE');
        Configuration::deleteByName('PS_TILLIT_PAYMENT_MODE');
        Configuration::deleteByName('PS_TILLIT_MERACHANT_ID');
        Configuration::deleteByName('PS_TILLIT_API_KEY');
        Configuration::deleteByName('PS_TILLIT_PRODUCT_TYPE');
        Configuration::deleteByName('PS_TILLIT_DAY_ON_INVOICE');
        Configuration::deleteByName('PS_TILLIT_ENABLE_COMPANY_NAME');
        Configuration::deleteByName('PS_TILLIT_ENABLE_COMPANY_ID');
        Configuration::deleteByName('PS_TILLIT_FANILIZE_PURCHASE');
        Configuration::deleteByName('PS_TILLIT_ENABLE_ORDER_INTENT');
        Configuration::deleteByName('PS_TILLIT_ENABLE_B2B_B2C_RADIO');
        return true;
    }

    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitTillitForm')) == true) {
            $this->validTillitFormValues();
            if (!count($this->errors)) {
                $this->saveTillitFormValues();
            } else {
                foreach ($this->errors as $err) {
                    $this->output .= $this->displayError($err);
                }
            }
        }

        return $this->output . $this->renderTillitForm();
    }

    protected function renderTillitForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTillitForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTillitFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTillitForm()));
    }

    protected function getTillitForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Tillit Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'hint' => $this->l('Enter a title which is appear on checkout page as payment method title.'),
                        'name' => 'PS_TILLIT_TITLE',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sub title'),
                        'hint' => $this->l('Enter a sub title which is appear on checkout page as payment method sub title.'),
                        'name' => 'PS_TILLIT_SUB_TITLE',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TILLIT_PAYMENT_MODE',
                        'label' => $this->l('Payment mode'),
                        'hint' => $this->l('Choose your payment mode production, staging and development.'),
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id_option' => 'prod', 'name' => $this->l('Production')),
                                array('id_option' => 'stg', 'name' => $this->l('Staging')),
                                array('id_option' => 'dev', 'name' => $this->l('Development')),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Merchant id'),
                        'name' => 'PS_TILLIT_MERACHANT_ID',
                        'required' => true,
                        'hint' => $this->l('Enter your merchant id which is provided by tillit.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Api key'),
                        'name' => 'PS_TILLIT_API_KEY',
                        'required' => true,
                        'hint' => $this->l('Enter your api key which is provided by tillit.'),
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TILLIT_PRODUCT_TYPE',
                        'label' => $this->l('Choose your product'),
                        'hint' => $this->l('Choose your product funded invoice, merchant invoice and administered invoice depend on tillit account.'),
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id_option' => 'product_funded', 'name' => $this->l('Funded Invoice')),
                                array('id_option' => 'product_merchant', 'name' => $this->l('Merchant Invoice (coming soon)')),
                                array('id_option' => 'product_administered', 'name' => $this->l('Administered Invoice (coming soon)')),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of days on invoice'),
                        'name' => 'PS_TILLIT_DAY_ON_INVOICE',
                        'required' => true,
                        'hint' => $this->l('Enter a number of days on invoice.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate company name auto-complete'),
                        'name' => 'PS_TILLIT_ENABLE_COMPANY_NAME',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then customers to use search api to find their company names.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_COMPANY_NAME_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_COMPANY_NAME_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate company org.id auto-complete'),
                        'name' => 'PS_TILLIT_ENABLE_COMPANY_ID',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then customers to use search api to fins their company id (number) automatically.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_COMPANY_ID_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_COMPANY_ID_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Finalize purchase when order is shipped'),
                        'name' => 'PS_TILLIT_FANILIZE_PURCHASE',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then order status of shipped to be passed to tillit.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_FANILIZE_PURCHASE_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_FANILIZE_PURCHASE_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate B2C/B2B check-out radio button'),
                        'name' => 'PS_TILLIT_ENABLE_B2B_B2C_RADIO',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then allow different types of account (personal/business).'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_B2B_B2C_RADIO_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_B2B_B2C_RADIO_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        return $fields_form;
    }

    protected function getTillitFormValues()
    {
        $fields_values = array();
        foreach ($this->languages as $language) {
            $fields_values['PS_TILLIT_TITLE'][$language['id_lang']] = Tools::getValue('PS_TILLIT_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TILLIT_TITLE', (int) $language['id_lang']));
            $fields_values['PS_TILLIT_SUB_TITLE'][$language['id_lang']] = Tools::getValue('PS_TILLIT_SUB_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TILLIT_SUB_TITLE', (int) $language['id_lang']));
        }
        $fields_values['PS_TILLIT_PAYMENT_MODE'] = Tools::getValue('PS_TILLIT_PAYMENT_MODE', Configuration::get('PS_TILLIT_PAYMENT_MODE'));
        $fields_values['PS_TILLIT_MERACHANT_ID'] = Tools::getValue('PS_TILLIT_MERACHANT_ID', Configuration::get('PS_TILLIT_MERACHANT_ID'));
        $fields_values['PS_TILLIT_API_KEY'] = Tools::getValue('PS_TILLIT_API_KEY', Configuration::get('PS_TILLIT_API_KEY'));
        $fields_values['PS_TILLIT_DAY_ON_INVOICE'] = Tools::getValue('PS_TILLIT_DAY_ON_INVOICE', Configuration::get('PS_TILLIT_DAY_ON_INVOICE'));
        $fields_values['PS_TILLIT_ENABLE_COMPANY_NAME'] = Tools::getValue('PS_TILLIT_ENABLE_COMPANY_NAME', Configuration::get('PS_TILLIT_ENABLE_COMPANY_NAME'));
        $fields_values['PS_TILLIT_ENABLE_COMPANY_ID'] = Tools::getValue('PS_TILLIT_ENABLE_COMPANY_ID', Configuration::get('PS_TILLIT_ENABLE_COMPANY_ID'));
        $fields_values['PS_TILLIT_FANILIZE_PURCHASE'] = Tools::getValue('PS_TILLIT_FANILIZE_PURCHASE', Configuration::get('PS_TILLIT_FANILIZE_PURCHASE'));
        $fields_values['PS_TILLIT_ENABLE_ORDER_INTENT'] = Tools::getValue('PS_TILLIT_ENABLE_ORDER_INTENT', Configuration::get('PS_TILLIT_ENABLE_ORDER_INTENT'));
        $fields_values['PS_TILLIT_ENABLE_B2B_B2C_RADIO'] = Tools::getValue('PS_TILLIT_ENABLE_B2B_B2C_RADIO', Configuration::get('PS_TILLIT_ENABLE_B2B_B2C_RADIO'));
        return $fields_values;
    }
    
    protected function validTillitFormValues()
    {
        foreach ($this->languages as $language) {
            if (Tools::isEmpty(Tools::getValue('PS_TILLIT_TITLE_' . (int) $language['id_lang']))) {
                $this->errors[] = $this->l('Enter a title.');
            }
            if (Tools::isEmpty(Tools::getValue('PS_TILLIT_SUB_TITLE_' . (int) $language['id_lang']))) {
                $this->errors[] = $this->l('Enter a sub title.');
            }
        }
        if (Tools::isEmpty(Tools::getValue('PS_TILLIT_MERACHANT_ID'))) {
            $this->errors[] = $this->l('Enter a merchant id.');
        }
        if (Tools::isEmpty(Tools::getValue('PS_TILLIT_API_KEY'))) {
            $this->errors[] = $this->l('Enter a api key.');
        }
        if (Tools::isEmpty(Tools::getValue('PS_TILLIT_DAY_ON_INVOICE'))) {
            $this->errors[] = $this->l('Enter a number of days on invoice.');
        }
    }
    
    protected function saveTillitFormValues()
    {
        $values = array();
        foreach ($this->languages as $language) {
            $values['PS_TILLIT_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TILLIT_TITLE_' . (int) $language['id_lang']);
            $values['PS_TILLIT_SUB_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TILLIT_SUB_TITLE_' . (int) $language['id_lang']);
        }
        Configuration::updateValue('PS_TILLIT_TITLE', $values['PS_TILLIT_TITLE']);
        Configuration::updateValue('PS_TILLIT_SUB_TITLE', $values['PS_TILLIT_SUB_TITLE']);
        Configuration::updateValue('PS_TILLIT_PAYMENT_MODE', Tools::getValue('PS_TILLIT_PAYMENT_MODE'));
        Configuration::updateValue('PS_TILLIT_MERACHANT_ID', Tools::getValue('PS_TILLIT_MERACHANT_ID'));
        Configuration::updateValue('PS_TILLIT_API_KEY', Tools::getValue('PS_TILLIT_API_KEY'));
        Configuration::updateValue('PS_TILLIT_PRODUCT_TYPE', Tools::getValue('PS_TILLIT_PRODUCT_TYPE'));
        Configuration::updateValue('PS_TILLIT_DAY_ON_INVOICE', Tools::getValue('PS_TILLIT_DAY_ON_INVOICE'));
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_NAME', Tools::getValue('PS_TILLIT_ENABLE_COMPANY_NAME'));
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_ID', Tools::getValue('PS_TILLIT_ENABLE_COMPANY_ID'));
        Configuration::updateValue('PS_TILLIT_FANILIZE_PURCHASE', Tools::getValue('PS_TILLIT_FANILIZE_PURCHASE'));
        Configuration::updateValue('PS_TILLIT_ENABLE_ORDER_INTENT', Tools::getValue('PS_TILLIT_ENABLE_ORDER_INTENT'));
        Configuration::updateValue('PS_TILLIT_ENABLE_B2B_B2C_RADIO', Tools::getValue('PS_TILLIT_ENABLE_B2B_B2C_RADIO'));
        
        $this->output .= $this->displayConfirmation($this->l('Tillit settings are updated.'));
    }
    
    public function hookActionAdminControllerSetMedia()
    {
        $controller = get_class($this->context->controller);
        if($controller == 'AdminModulesController' && Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }
    
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (Tools::isEmpty($this->merchant_id) || Tools::isEmpty($this->api_key)) {
            return;
        }

        $payment_options = [
            $this->getTillitPaymentOption(),
        ];

        return $payment_options;
    }
    
    protected function getTillitPaymentOption()
    {
        $title = Configuration::get('PS_TILLIT_TITLE', $this->context->language->id);
        $subtitle = Configuration::get('PS_TILLIT_SUB_TITLE', $this->context->language->id);
        
        if (Tools::isEmpty($title)) {
            $title = $this->l('Business invoice 30 days');
        }
        if (Tools::isEmpty($subtitle)) {
            $subtitle = $this->l('Receive the invoice via EHF and PDF');
        }
        
        $this->context->smarty->assign(array(
            'subtitle' => $subtitle,
        ));
        
        $preTillitOption = new PaymentOption();
        $preTillitOption->setCallToActionText($title)
                ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
                ->setInputs(['token' => ['name' => 'token', 'type' => 'hidden', 'value' => Tools::getToken(false)]])
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . 'tillit/views/img/tillit.SVG'))
                ->setAdditionalInformation($this->context->smarty->fetch('module:tillit/views/templates/hook/paymentinfo.tpl'));
        
        return $preTillitOption;
    }
}
