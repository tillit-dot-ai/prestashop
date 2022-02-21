<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Twopayment extends PaymentModule
{

    protected $output = '';
    protected $errors = array();

    public function __construct()
    {
        $this->name = 'twopayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.2';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Two';
        $this->bootstrap = true;
        $this->module_key = '0dff0a98ae080e510d4e23d22abcfe9c';
        $this->author_address = '';
        parent::__construct();
        $this->languages = Language::getLanguages(false);
        $this->displayName = $this->l('Two - BNPL for businesses');
        $this->description = $this->l('This module allows any merchant to accept payments with Two payment gateway.');
        $this->merchant_short_name = Configuration::get('PS_TWO_MERACHANT_SHORT_NAME');
        $this->api_key = Configuration::get('PS_TWO_MERACHANT_API_KEY');
        $this->payment_mode = Configuration::get('PS_TWO_PAYMENT_MODE');
        $this->enable_company_name = Configuration::get('PS_TWO_ENABLE_COMPANY_NAME');
        $this->enable_company_id = Configuration::get('PS_TWO_ENABLE_COMPANY_ID');
        $this->product_type = Configuration::get('PS_TWO_PRODUCT_TYPE');
        $this->enable_order_intent = Configuration::get('PS_TWO_ENABLE_ORDER_INTENT');
        $this->day_on_invoice = Configuration::get('PS_TWO_DAY_ON_INVOICE');
        $this->finalize_purchase_shipping = Configuration::get('PS_TWO_FANILIZE_PURCHASE');
        $this->enable_buyer_refund = Configuration::get('PS_TWO_ENABLE_BUYER_REFUND');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook('displayAdminOrderTabLink') &&
            $this->registerHook('displayAdminOrderTabContent') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('actionOrderEdited') &&
            $this->registerHook('actionAdminOrdersTrackingNumberUpdate') &&
            $this->installTwoSettings() &&
            $this->createTwoOrderState() &&
            $this->createTwoTables();
    }

    protected function installTwoSettings()
    {
        $installData = array();
        foreach ($this->languages as $language) {
            $installData['PS_TWO_TITLE'][(int) $language['id_lang']] = 'Business invoice 30 days';
            $installData['PS_TWO_SUB_TITLE'][(int) $language['id_lang']] = 'Receive the invoice via EHF and PDF';
        }
        Configuration::updateValue('PS_TWO_TAB_VALUE', 1);
        Configuration::updateValue('PS_TWO_TITLE', $installData['PS_TWO_TITLE']);
        Configuration::updateValue('PS_TWO_SUB_TITLE', $installData['PS_TWO_SUB_TITLE']);
        Configuration::updateValue('PS_TWO_PAYMENT_MODE', 'test');
        Configuration::updateValue('PS_TWO_PAYMENT_DEV_MODE', 'https://staging.api.two.inc');
        Configuration::updateValue('PS_TWO_MERACHANT_SHORT_NAME', '');
        Configuration::updateValue('PS_TWO_MERACHANT_API_KEY', '');
        Configuration::updateValue('PS_TWO_PRODUCT_TYPE', 'FUNDED_INVOICE');
        Configuration::updateValue('PS_TWO_DAY_ON_INVOICE', 14);
        Configuration::updateValue('PS_TWO_ENABLE_COMPANY_NAME', 1);
        Configuration::updateValue('PS_TWO_ENABLE_COMPANY_ID', 1);
        Configuration::updateValue('PS_TWO_FANILIZE_PURCHASE', 1);
        Configuration::updateValue('PS_TWO_ENABLE_ORDER_INTENT', 1);
        Configuration::updateValue('PS_TWO_ENABLE_BUYER_REFUND', 1);
        Configuration::updateValue('PS_TWO_OS_PREPARATION', Configuration::get('PS_OS_PREPARATION'));
        Configuration::updateValue('PS_TWO_OS_SHIPPING', Configuration::get('PS_OS_SHIPPING'));
        Configuration::updateValue('PS_TWO_OS_DELIVERED', Configuration::get('PS_OS_DELIVERED'));
        Configuration::updateValue('PS_TWO_OS_ERROR', Configuration::get('PS_OS_ERROR'));
        Configuration::updateValue('PS_TWO_OS_CANCELED', Configuration::get('PS_OS_CANCELED'));
        Configuration::updateValue('PS_TWO_OS_REFUND', Configuration::get('PS_OS_REFUND'));
        return true;
    }

    protected function createTwoOrderState()
    {
        if (!Configuration::get('PS_TWO_OS_AWAITING')) {
            $orderStateObj = new OrderState();
            $orderStateObj->send_email = 0;
            $orderStateObj->module_name = $this->name;
            $orderStateObj->invoice = 0;
            $orderStateObj->color = '#4169E1';
            $orderStateObj->logable = 1;
            $orderStateObj->shipped = 0;
            $orderStateObj->unremovable = 1;
            $orderStateObj->delivery = 0;
            $orderStateObj->hidden = 0;
            $orderStateObj->paid = 0;
            $orderStateObj->pdf_delivery = 0;
            $orderStateObj->pdf_invoice = 0;
            $orderStateObj->deleted = 0;
            foreach ($this->languages as $language) {
                $orderStateObj->name[$language['id_lang']] = 'Awaiting two payment';
            }
            if ($orderStateObj->add()) {
                Configuration::updateValue('PS_TWO_OS_AWAITING', (int) $orderStateObj->id);
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    protected function createTwoTables()
    {
        $sql = array();
        $columns = Db::getInstance()->executeS('DESCRIBE `' . _DB_PREFIX_ . 'address`');
        $fileds = array();
        foreach ($columns as $column) {
            $fileds[] = $column['Field'];
        }

        if (!in_array('account_type', $fileds)) {
            $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `account_type` VARCHAR(255)';
        }
        if (!in_array('companyid', $fileds)) {
            $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `companyid` VARCHAR(255)';
        }
        if (!in_array('department', $fileds)) {
            $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `department` VARCHAR(255)';
        }
        if (!in_array('project', $fileds)) {
            $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `project` VARCHAR(255)';
        }

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'twopayment` (
            `id_two` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` INT( 11 ) UNSIGNED,
            `two_order_id` TEXT NULL,
            `two_order_reference` TEXT NULL,
            `two_order_state` TEXT NULL,
            `two_order_status` TEXT NULL,
            `two_day_on_invoice` TEXT NULL,
            `two_invoice_url` TEXT NULL,
            PRIMARY KEY  (`id_two`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }
        return true;
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->unregisterHook('actionAdminControllerSetMedia') &&
            $this->unregisterHook('actionFrontControllerSetMedia') &&
            $this->unregisterHook('actionOrderStatusUpdate') &&
            $this->unregisterHook('paymentOptions') &&
            $this->unregisterHook('displayPaymentReturn') &&
            $this->unregisterHook('displayAdminOrderLeft') &&
            $this->unregisterHook('displayAdminOrderTabLink') &&
            $this->unregisterHook('displayAdminOrderTabContent') &&
            $this->unregisterHook('displayOrderDetail') &&
            $this->unregisterHook('actionOrderSlipAdd') &&
            $this->unregisterHook('actionOrderEdited') &&
            $this->unregisterHook('actionAdminOrdersTrackingNumberUpdate') &&
            $this->uninstallTwoSettings() &&
            $this->deleteTwoTables();
    }

    protected function uninstallTwoSettings()
    {
        Configuration::deleteByName('PS_TWO_TAB_VALUE');
        Configuration::deleteByName('PS_TWO_TITLE');
        Configuration::deleteByName('PS_TWO_SUB_TITLE');
        Configuration::deleteByName('PS_TWO_PAYMENT_MODE');
        Configuration::deleteByName('PS_TWO_MERACHANT_SHORT_NAME');
        Configuration::deleteByName('PS_TWO_MERACHANT_API_KEY');
        Configuration::deleteByName('PS_TWO_MERACHANT_LOGO');
        Configuration::deleteByName('PS_TWO_PRODUCT_TYPE');
        Configuration::deleteByName('PS_TWO_DAY_ON_INVOICE');
        Configuration::deleteByName('PS_TWO_ENABLE_COMPANY_NAME');
        Configuration::deleteByName('PS_TWO_ENABLE_COMPANY_ID');
        Configuration::deleteByName('PS_TWO_FANILIZE_PURCHASE');
        Configuration::deleteByName('PS_TWO_ENABLE_ORDER_INTENT');
        Configuration::deleteByName('PS_TWO_ENABLE_BUYER_REFUND');
        return true;
    }

    protected function deleteTwoTables()
    {
        $sql = array();
        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }
        return true;
    }

    public function getContent()
    {
        if (((bool) Tools::isSubmit('deleteLogo')) == true) {
            Configuration::updateValue('PS_TWO_TAB_VALUE', 1);
            $file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . Configuration::get('PS_TWO_MERACHANT_LOGO');
            if (file_exists($file_name) && unlink($file_name)) {
                Configuration::updateValue('PS_TWO_MERACHANT_LOGO', '');
                $this->sendTwoLogoToMerchant();
                $this->output .= $this->displayConfirmation($this->l('General settings are updated.'));
            }
        }
        if (((bool) Tools::isSubmit('submitTwoGeneralForm')) == true) {
            Configuration::updateValue('PS_TWO_TAB_VALUE', 1);
            $this->validTwoGeneralFormValues();
            if (!count($this->errors)) {
                $this->saveTwoGeneralFormValues();
            } else {
                foreach ($this->errors as $err) {
                    $this->output .= $this->displayError($err);
                }
            }
        }

        if (((bool) Tools::isSubmit('submitTwoOtherForm')) == true) {
            Configuration::updateValue('PS_TWO_TAB_VALUE', 2);
            $this->validTwoOtherFormValues();
            if (!count($this->errors)) {
                $this->saveTwoOtherFormValues();
            } else {
                foreach ($this->errors as $err) {
                    $this->output .= $this->displayError($err);
                }
            }
        }

        if (((bool) Tools::isSubmit('submitTwoOrderStatusForm')) == true) {
            Configuration::updateValue('PS_TWO_TAB_VALUE', 3);
            $this->saveTwoOrderStatusFormValues();
        }

        $this->context->smarty->assign(
            array(
                'renderTwoGeneralForm' => $this->renderTwoGeneralForm(),
                'renderTwoOtherForm' => $this->renderTwoOtherForm(),
                'renderTwoOrderStatusForm' => $this->renderTwoOrderStatusForm(),
                'twotabvalue' => Configuration::get('PS_TWO_TAB_VALUE'),
            )
        );

        $this->output .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
        return $this->output;
    }

    protected function renderTwoGeneralForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTwoGeneralForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTwoGeneralFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTwoGeneralForm()));
    }

    protected function getTwoGeneralForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'desc' => $this->l('Enter a title which is appear on checkout page as payment method title.'),
                        'name' => 'PS_TWO_TITLE',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sub title'),
                        'desc' => $this->l('Enter a sub title which is appear on checkout page as payment method sub title.'),
                        'name' => 'PS_TWO_SUB_TITLE',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Merchant short name'),
                        'name' => 'PS_TWO_MERACHANT_SHORT_NAME',
                        'required' => true,
                        'desc' => $this->l('Enter your merchant short name which is provided by Two.'),
                    ),
                    array(
                        'type' => 'password',
                        'label' => $this->l('Api key'),
                        'name' => 'PS_TWO_MERACHANT_API_KEY',
                        'required' => true,
                        'desc' => $this->l('Enter your api key which is provided by Two.'),
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Logo'),
                        'name' => 'PS_TWO_MERACHANT_LOGO',
                        'desc' => $this->l('Upload your merchant logo.'),
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_PRODUCT_TYPE',
                        'label' => $this->l('Choose your product'),
                        'desc' => $this->l('Choose your product funded invoice, merchant invoice and administered invoice depend on Two account.'),
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id_option' => 'FUNDED_INVOICE', 'name' => $this->l('Funded Invoice')),
                                array('id_option' => 'DIRECT_INVOICE', 'name' => $this->l('Direct Invoice')),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of days on invoice'),
                        'name' => 'PS_TWO_DAY_ON_INVOICE',
                        'required' => true,
                        'desc' => $this->l('Enter a number of days on invoice.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        return $fields_form;
    }

    protected function getTwoGeneralFormValues()
    {
        $fields_values = array();
        foreach ($this->languages as $language) {
            $fields_values['PS_TWO_TITLE'][$language['id_lang']] = Tools::getValue('PS_TWO_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TWO_TITLE', (int) $language['id_lang']));
            $fields_values['PS_TWO_SUB_TITLE'][$language['id_lang']] = Tools::getValue('PS_TWO_SUB_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TWO_SUB_TITLE', (int) $language['id_lang']));
        }
        $fields_values['PS_TWO_MERACHANT_SHORT_NAME'] = Tools::getValue('PS_TWO_MERACHANT_SHORT_NAME', Configuration::get('PS_TWO_MERACHANT_SHORT_NAME'));
        $fields_values['PS_TWO_MERACHANT_API_KEY'] = Tools::getValue('PS_TWO_MERACHANT_API_KEY', Configuration::get('PS_TWO_MERACHANT_API_KEY'));
        $fields_values['PS_TWO_MERACHANT_LOGO'] = Tools::getValue('PS_TWO_MERACHANT_LOGO', Configuration::get('PS_TWO_MERACHANT_LOGO'));
        $fields_values['PS_TWO_PRODUCT_TYPE'] = Tools::getValue('PS_TWO_PRODUCT_TYPE', Configuration::get('PS_TWO_PRODUCT_TYPE'));
        $fields_values['PS_TWO_DAY_ON_INVOICE'] = Tools::getValue('PS_TWO_DAY_ON_INVOICE', Configuration::get('PS_TWO_DAY_ON_INVOICE'));
        return $fields_values;
    }

    protected function validTwoGeneralFormValues()
    {
        foreach ($this->languages as $language) {
            if (Tools::isEmpty(Tools::getValue('PS_TWO_TITLE_' . (int) $language['id_lang']))) {
                $this->errors[] = $this->l('Enter a title.');
            }
            if (Tools::isEmpty(Tools::getValue('PS_TWO_SUB_TITLE_' . (int) $language['id_lang']))) {
                $this->errors[] = $this->l('Enter a sub title.');
            }
        }
        if (Tools::isEmpty(Tools::getValue('PS_TWO_MERACHANT_SHORT_NAME'))) {
            $this->errors[] = $this->l('Enter a merchant short name.');
        }
        if (Tools::isEmpty(Tools::getValue('PS_TWO_MERACHANT_API_KEY'))) {
            $this->errors[] = $this->l('Enter a api key.');
        }
        if (Tools::isEmpty(Tools::getValue('PS_TWO_DAY_ON_INVOICE'))) {
            $this->errors[] = $this->l('Enter a number of days on invoice.');
        }
    }

    protected function saveTwoGeneralFormValues()
    {
        $imagefile = "";
        $update_images_values = false;
        if (isset($_FILES['PS_TWO_MERACHANT_LOGO']) && isset($_FILES['PS_TWO_MERACHANT_LOGO']['tmp_name']) && !empty($_FILES['PS_TWO_MERACHANT_LOGO']['tmp_name'])) {
            if ($error = ImageManager::validateUpload($_FILES['PS_TWO_MERACHANT_LOGO'], 4000000)) {
                return $error;
            } else {
                $ext = Tools::substr($_FILES['PS_TWO_MERACHANT_LOGO']['name'], Tools::substr($_FILES['PS_TWO_MERACHANT_LOGO']['name'], '.') + 1);
                $file_name = md5($_FILES['PS_TWO_MERACHANT_LOGO']['name']) . '.' . $ext;

                if (!move_uploaded_file($_FILES['PS_TWO_MERACHANT_LOGO']['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $file_name)) {
                    return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                } else {
                    if (Configuration::get('PS_TWO_MERACHANT_LOGO') != $file_name) {
                        @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . Configuration::get('PS_TWO_MERACHANT_LOGO'));
                    }
                    $imagefile = $file_name;
                }
            }

            $update_images_values = true;
        }

        if ($update_images_values) {
            Configuration::updateValue('PS_TWO_MERACHANT_LOGO', $imagefile);
            $this->sendTwoLogoToMerchant();
        }

        $values = array();
        foreach ($this->languages as $language) {
            $values['PS_TWO_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TWO_TITLE_' . (int) $language['id_lang']);
            $values['PS_TWO_SUB_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TWO_SUB_TITLE_' . (int) $language['id_lang']);
        }
        Configuration::updateValue('PS_TWO_TITLE', $values['PS_TWO_TITLE']);
        Configuration::updateValue('PS_TWO_SUB_TITLE', $values['PS_TWO_SUB_TITLE']);
        Configuration::updateValue('PS_TWO_MERACHANT_SHORT_NAME', trim(Tools::getValue('PS_TWO_MERACHANT_SHORT_NAME')));
        Configuration::updateValue('PS_TWO_MERACHANT_API_KEY', trim(Tools::getValue('PS_TWO_MERACHANT_API_KEY')));
        Configuration::updateValue('PS_TWO_PRODUCT_TYPE', Tools::getValue('PS_TWO_PRODUCT_TYPE'));
        Configuration::updateValue('PS_TWO_DAY_ON_INVOICE', Tools::getValue('PS_TWO_DAY_ON_INVOICE'));

        $this->output .= $this->displayConfirmation($this->l('General settings are updated.'));
    }

    protected function renderTwoOtherForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTwoOtherForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTwoOtherFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTwoOtherForm()));
    }

    protected function getTwoOtherForm()
    {
        if ($this->isTwoCheckoutDevelopment()) {
            $payment_mode = array(
                'type' => 'text',
                'name' => 'PS_TWO_PAYMENT_DEV_MODE',
                'label' => $this->l('Two test server'),
                'desc' => $this->l('Enter your stagiing development url.'),
                'required' => true,
            );
        } else {
            $payment_mode = array(
                'type' => 'select',
                'name' => 'PS_TWO_PAYMENT_MODE',
                'label' => $this->l('Payment mode'),
                'desc' => $this->l('Choose your payment mode production and test.'),
                'required' => true,
                'options' => array(
                    'query' => array(
                        array('id_option' => 'prod', 'name' => $this->l('Production')),
                        array('id_option' => 'test', 'name' => $this->l('Test')),
                    ),
                    'id' => 'id_option',
                    'name' => 'name'
                )
            );
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Other Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    $payment_mode,
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate company name auto-complete'),
                        'name' => 'PS_TWO_ENABLE_COMPANY_NAME',
                        'is_bool' => true,
                        'desc' => $this->l('If you choose YES then customers to use search api to find their company names.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TWO_ENABLE_COMPANY_NAME_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TWO_ENABLE_COMPANY_NAME_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate company org.id auto-complete'),
                        'name' => 'PS_TWO_ENABLE_COMPANY_ID',
                        'is_bool' => true,
                        'desc' => $this->l('If you choose YES then customers to use search api to fins their company id (number) automatically.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TWO_ENABLE_COMPANY_ID_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TWO_ENABLE_COMPANY_ID_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Finalize purchase when order is shipped'),
                        'name' => 'PS_TWO_FANILIZE_PURCHASE',
                        'is_bool' => true,
                        'desc' => $this->l('If you choose YES then order status of shipped to be passed to Two.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TWO_FANILIZE_PURCHASE_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TWO_FANILIZE_PURCHASE_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Pre-approve the buyer during checkout and disable two if the buyer is declined'),
                        'name' => 'PS_TWO_ENABLE_ORDER_INTENT',
                        'is_bool' => true,
                        'desc' => $this->l('If you choose YES then pre-approve the buyer during checkout and disable two if the buyer is declined.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TWO_ENABLE_ORDER_INTENT_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TWO_ENABLE_ORDER_INTENT_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Initiate payment to buyer on refund'),
                        'name' => 'PS_TWO_ENABLE_BUYER_REFUND',
                        'is_bool' => true,
                        'desc' => $this->l('If you choose YES then allow to initiate payment buyer on refund.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TWO_ENABLE_BUYER_REFUND_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TWO_ENABLE_BUYER_REFUND_OFF',
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

    protected function getTwoOtherFormValues()
    {
        $fields_values = array();
        if ($this->isTwoCheckoutDevelopment()) {
            $fields_values['PS_TWO_PAYMENT_DEV_MODE'] = Tools::getValue('PS_TWO_PAYMENT_DEV_MODE', Configuration::get('PS_TWO_PAYMENT_DEV_MODE'));
        } else {
            $fields_values['PS_TWO_PAYMENT_MODE'] = Tools::getValue('PS_TWO_PAYMENT_MODE', Configuration::get('PS_TWO_PAYMENT_MODE'));
        }
        $fields_values['PS_TWO_ENABLE_COMPANY_NAME'] = Tools::getValue('PS_TWO_ENABLE_COMPANY_NAME', Configuration::get('PS_TWO_ENABLE_COMPANY_NAME'));
        $fields_values['PS_TWO_ENABLE_COMPANY_ID'] = Tools::getValue('PS_TWO_ENABLE_COMPANY_ID', Configuration::get('PS_TWO_ENABLE_COMPANY_ID'));
        $fields_values['PS_TWO_FANILIZE_PURCHASE'] = Tools::getValue('PS_TWO_FANILIZE_PURCHASE', Configuration::get('PS_TWO_FANILIZE_PURCHASE'));
        $fields_values['PS_TWO_ENABLE_ORDER_INTENT'] = Tools::getValue('PS_TWO_ENABLE_ORDER_INTENT', Configuration::get('PS_TWO_ENABLE_ORDER_INTENT'));
        $fields_values['PS_TWO_ENABLE_B2B_B2C'] = Tools::getValue('PS_TWO_ENABLE_B2B_B2C', Configuration::get('PS_TWO_ENABLE_B2B_B2C'));
        $fields_values['PS_TWO_ENABLE_BUYER_REFUND'] = Tools::getValue('PS_TWO_ENABLE_BUYER_REFUND', Configuration::get('PS_TWO_ENABLE_BUYER_REFUND'));
        return $fields_values;
    }

    protected function validTwoOtherFormValues()
    {
        if ($this->isTwoCheckoutDevelopment()) {
            if (Tools::isEmpty(Tools::getValue('PS_TWO_PAYMENT_DEV_MODE'))) {
                $this->errors[] = $this->l('Enter a two test server url.');
            } elseif (!Validate::isUrl(Tools::getValue('PS_TWO_PAYMENT_DEV_MODE'))) {
                $this->errors[] = $this->l('Enter a valid two test server url.');
            }
        }
    }

    protected function saveTwoOtherFormValues()
    {
        if ($this->isTwoCheckoutDevelopment()) {
            Configuration::updateValue('PS_TWO_PAYMENT_DEV_MODE', Tools::getValue('PS_TWO_PAYMENT_DEV_MODE'));
        } else {
            Configuration::updateValue('PS_TWO_PAYMENT_MODE', Tools::getValue('PS_TWO_PAYMENT_MODE'));
        }
        Configuration::updateValue('PS_TWO_ENABLE_COMPANY_NAME', Tools::getValue('PS_TWO_ENABLE_COMPANY_NAME'));
        Configuration::updateValue('PS_TWO_ENABLE_COMPANY_ID', Tools::getValue('PS_TWO_ENABLE_COMPANY_ID'));
        Configuration::updateValue('PS_TWO_FANILIZE_PURCHASE', Tools::getValue('PS_TWO_FANILIZE_PURCHASE'));
        Configuration::updateValue('PS_TWO_ENABLE_ORDER_INTENT', Tools::getValue('PS_TWO_ENABLE_ORDER_INTENT'));
        Configuration::updateValue('PS_TWO_ENABLE_B2B_B2C', Tools::getValue('PS_TWO_ENABLE_B2B_B2C'));
        Configuration::updateValue('PS_TWO_ENABLE_BUYER_REFUND', Tools::getValue('PS_TWO_ENABLE_BUYER_REFUND'));

        $this->output .= $this->displayConfirmation($this->l('Other settings are updated.'));
    }

    protected function renderTwoOrderStatusForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTwoOrderStatusForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTwoOrderStatusFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTwoOrderStatusForm()));
    }

    protected function getTwoOrderStatusForm()
    {
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Order Status Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_AWAITING',
                        'label' => $this->l('Order status when order is unverify'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_PREPARATION',
                        'label' => $this->l('Order status when order is verify'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_SHIPPING',
                        'label' => $this->l('Order status when order is shipped'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_DELIVERED',
                        'label' => $this->l('Order status when order is delivered'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_ERROR',
                        'label' => $this->l('Order status when order is payment error'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_CANCELED',
                        'label' => $this->l('Order status when order is canceled'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'PS_TWO_OS_REFUND',
                        'label' => $this->l('Order status when order is refunded'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        return $fields_form;
    }

    protected function getTwoOrderStatusFormValues()
    {
        $fields_values = array();
        $fields_values['PS_TWO_OS_AWAITING'] = Tools::getValue('PS_TWO_OS_AWAITING', Configuration::get('PS_TWO_OS_AWAITING'));
        $fields_values['PS_TWO_OS_PREPARATION'] = Tools::getValue('PS_TWO_OS_PREPARATION', Configuration::get('PS_TWO_OS_PREPARATION'));
        $fields_values['PS_TWO_OS_SHIPPING'] = Tools::getValue('PS_TWO_OS_SHIPPING', Configuration::get('PS_TWO_OS_SHIPPING'));
        $fields_values['PS_TWO_OS_DELIVERED'] = Tools::getValue('PS_TWO_OS_DELIVERED', Configuration::get('PS_TWO_OS_DELIVERED'));
        $fields_values['PS_TWO_OS_ERROR'] = Tools::getValue('PS_TWO_OS_ERROR', Configuration::get('PS_TWO_OS_ERROR'));
        $fields_values['PS_TWO_OS_CANCELED'] = Tools::getValue('PS_TWO_OS_CANCELED', Configuration::get('PS_TWO_OS_CANCELED'));
        $fields_values['PS_TWO_OS_REFUND'] = Tools::getValue('PS_TWO_OS_REFUND', Configuration::get('PS_TWO_OS_REFUND'));
        return $fields_values;
    }

    protected function saveTwoOrderStatusFormValues()
    {
        Configuration::updateValue('PS_TWO_OS_AWAITING', Tools::getValue('PS_TWO_OS_AWAITING'));
        Configuration::updateValue('PS_TWO_OS_PREPARATION', Tools::getValue('PS_TWO_OS_PREPARATION'));
        Configuration::updateValue('PS_TWO_OS_SHIPPING', Tools::getValue('PS_TWO_OS_SHIPPING'));
        Configuration::updateValue('PS_TWO_OS_DELIVERED', Tools::getValue('PS_TWO_OS_DELIVERED'));
        Configuration::updateValue('PS_TWO_OS_ERROR', Tools::getValue('PS_TWO_OS_ERROR'));
        Configuration::updateValue('PS_TWO_OS_CANCELED', Tools::getValue('PS_TWO_OS_CANCELED'));
        Configuration::updateValue('PS_TWO_OS_REFUND', Tools::getValue('PS_TWO_OS_REFUND'));

        $this->output .= $this->displayConfirmation($this->l('Order status settings are updated.'));
    }

    public function hookActionOrderEdited($params)
    {
        $order = $params['order'];
        $cart = new Cart($order->id_cart);
        $payment = $order->getOrderPaymentCollection();
        if (isset($payment[0])) {
            $payment[0]->amount = $cart->getOrderTotal(true, Cart::BOTH);
            $payment[0]->save();
        }

        if ($order->module == $this->name) {
            $orderpaymentdata = $this->getTwoOrderPaymentData($order->id);
            if ($orderpaymentdata && isset($orderpaymentdata['two_order_id'])) {
                $two_order_id = $orderpaymentdata['two_order_id'];
                $paymentdata = $this->getTwoUpdateOrderData($order, $orderpaymentdata);
                $this->setTwoPaymentRequest('/v1/order/' . $two_order_id, $paymentdata, 'PUT');
            }
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $id_order = $params['id_order'];
        $order = new Order((int) $id_order);
        $new_order_status = $params['newOrderStatus'];
        if ($order->module == $this->name) {
            $orderpaymentdata = $this->getTwoOrderPaymentData($id_order);
            if ($orderpaymentdata && isset($orderpaymentdata['two_order_id'])) {
                $two_order_id = $orderpaymentdata['two_order_id'];

                if ($new_order_status->id == Configuration::get('PS_TWO_OS_CANCELED')) {
                    $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/cancel', [], 'POST');
                    $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id, [], 'GET');
                    if (isset($response['id']) && $response['id']) {
                        $payment_data = array(
                            'two_order_id' => $response['id'],
                            'two_order_reference' => $response['merchant_reference'],
                            'two_order_state' => $response['state'],
                            'two_order_status' => $response['status'],
                            'two_day_on_invoice' => $this->day_on_invoice,
                            'two_invoice_url' => $response['invoice_url'],
                        );
                        $this->setTwoOrderPaymentData($id_order, $payment_data);
                    }
                } else if ($new_order_status->id == Configuration::get('PS_TWO_OS_DELIVERED')) {
                    $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/delivered', [], 'POST');
                    $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id, [], 'GET');
                    if (isset($response['id']) && $response['id']) {
                        $payment_data = array(
                            'two_order_id' => $response['id'],
                            'two_order_reference' => $response['merchant_reference'],
                            'two_order_state' => $response['state'],
                            'two_order_status' => $response['status'],
                            'two_day_on_invoice' => $this->day_on_invoice,
                            'two_invoice_url' => $response['invoice_url'],
                        );
                        $this->setTwoOrderPaymentData($id_order, $payment_data);
                    }
                } else if (($new_order_status->id == Configuration::get('PS_TWO_OS_SHIPPING')) && $this->finalize_purchase_shipping) {
                    if((int)$this->context->country->id == 23){
                        $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/fulfilled?lang=nb_NO', [], 'POST', true);
                    } else {
                        $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/fulfilled?lang=en_US', [], 'POST', true);
                    }

                    if (isset($response['id']) && $response['id']) {
                        $payment_data = array(
                            'two_order_id' => $response['id'],
                            'two_order_reference' => $response['merchant_reference'],
                            'two_order_state' => $response['state'],
                            'two_order_status' => $response['status'],
                            'two_day_on_invoice' => $this->day_on_invoice,
                            'two_invoice_url' => $response['invoice_url'],
                        );
                        $this->setTwoOrderPaymentData($id_order, $payment_data);
                    }
                } else if (($new_order_status->id == Configuration::get('PS_TWO_OS_REFUND')) && $this->enable_buyer_refund) {
                    $paymentdata = $this->getTwoNewRefundData($order);
                    
                    if((int)$this->context->country->id == 23){
                        $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/refund?lang=nb_NO', $paymentdata, 'POST', true);
                    } else {
                        $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id . '/refund?lang=en_US', $paymentdata, 'POST', true);
                    }
                    
                    if (isset($response['id']) && $response['id']) {
                        $response = $this->setTwoPaymentRequest('/v1/order/' . $two_order_id,[],'GET');
                        $payment_data = array(
                            'two_order_id' => $response['id'],
                            'two_order_reference' => $response['merchant_reference'],
                            'two_order_state' => $response['state'],
                            'two_order_status' => $response['status'],
                            'two_day_on_invoice' => $this->day_on_invoice,
                            'two_invoice_url' => $response['invoice_url'],
                        );
                        $this->setTwoOrderPaymentData($id_order, $payment_data);
                    }
                }
            }
        }
    }

    public function hookActionFrontControllerSetMedia()
    {
        $countries = Country::getCountries($this->context->language->id, false, false, false);
        $param_countries = array();
        foreach ($countries as $country) {
            $param_countries[$country['id_country']] = Tools::strtolower($country['iso_code']);
        }
        Media::addJsDef(array('twopayment' => array(
                'search_empty_text' => $this->l('No result found'),
                'checkout_host' => $this->getTwoCheckoutHostUrl(),
                'company_name_search' => $this->enable_company_name,
                'company_id_search' => $this->enable_company_id,
                'client' => 'PS',
                'client_version' => $this->version,
                'countries' => $param_countries,
        )));
        $this->context->controller->addJqueryUI('ui.autocomplete');
        $this->context->controller->registerStylesheet('two-intl-tel-css', 'modules/twopayment/views/css/intlTelInput.css', array('priority' => 200, 'media' => 'all'));
        $this->context->controller->registerStylesheet('two-css', 'modules/twopayment/views/css/two.css', array('priority' => 200, 'media' => 'all'));
        $this->context->controller->registerJavascript('two-intl-tel-script', 'modules/twopayment/views/js/intlTelInput.min.js', array('priority' => 200, 'attribute' => 'async'));
        $this->context->controller->registerJavascript('two-script', 'modules/twopayment/views/js/twopayment.js', array('priority' => 200, 'attribute' => 'async'));
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (Tools::isEmpty($this->merchant_short_name) || Tools::isEmpty($this->api_key)) {
            return;
        }

        $payment_options = [
            $this->getTwoPaymentOption(),
        ];

        return $payment_options;
    }

    protected function getTwoPaymentOption()
    {
        $title = Configuration::get('PS_TWO_TITLE', $this->context->language->id);
        $subtitle = Configuration::get('PS_TWO_SUB_TITLE', $this->context->language->id);

        if (Tools::isEmpty($title)) {
            $title = $this->l('Business invoice 30 days');
        }
        if (Tools::isEmpty($subtitle)) {
            $subtitle = $this->l('Receive the invoice via EHF and PDF');
        }

        //check Pre-approve buyer for enable payment method
        if ($this->enable_order_intent) {
            $approval_data = $this->getTwoApprovalBuyer();

            $this->context->smarty->assign(array(
                'subtitle' => $subtitle,
                'enable_order_intent' => true,
                'payment_enable' => $approval_data['approval'],
                'message' => $approval_data['message'],
            ));
        } else {
            $this->context->smarty->assign(array(
                'subtitle' => $subtitle,
                'enable_order_intent' => false,
                'payment_enable' => false,
                'message' => '',
            ));
        }

        $preTwoOption = new PaymentOption();
        $preTwoOption->setModuleName($this->name)
            ->setCallToActionText($title)
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
            ->setInputs(['token' => ['name' => 'token', 'type' => 'hidden', 'value' => Tools::getToken(false)]])
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . 'twopayment/views/img/two.png'))
            ->setAdditionalInformation($this->context->smarty->fetch('module:twopayment/views/templates/hook/paymentinfo.tpl'));

        return $preTwoOption;
    }

    public function sendTwoLogoToMerchant()
    {
        $image_logo = Configuration::get('PS_TWO_MERACHANT_LOGO');
        if ($image_logo && file_exists(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $image_logo)) {
            $logo_path = $this->context->link->protocol_content . Tools::getMediaServer($image_logo) . $this->_path . 'views/img/' . $image_logo;
            $this->setTwoPaymentRequest("/v1/merchant/update", [
                'merchant_short_name' => $this->merchant_short_name,
                'logo_path' => $logo_path
                ], 'POST');
        } else {
            $this->setTwoPaymentRequest("/v1/merchant/update", [
                'merchant_short_name' => $this->merchant_short_name,
                'logo_path' => ''
                ], 'POST');
        }
    }

    public function getTwoApprovalBuyer()
    {
        $cart = $this->context->cart;
        $cutomer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $address = new Address($cart->id_address_invoice);

        if ($address->account_type == 'personal') {
            $data = array(
                'approval' => false,
                'message' => $this->l('Enter company name to pay on invoice.'),
            );
        } else {
            $paymentdata = $this->getTwoIntentOrderData($cart, $cutomer, $currency, $address);
            $response = $this->setTwoPaymentRequest("/v1/order_intent", $paymentdata, 'POST');

            $two_err = $this->getTwoErrorMessage($response);

            if ($two_err) {
                if ($this->checkTwoStartsWithString($two_err, '1 validation error for CreateOrderIntentRequestSchema: buyer -> company -> organization_number')) {
                    $error = $this->l('Your Complanay organization number is not valid. Please check your address.');
                } else if ($this->checkTwoStartsWithString($two_err, '1 validation error for CreateOrderIntentRequestSchema: buyer -> representative -> phone_number')) {
                    $error = $this->l('Phone number is invalid');
                } else if ($this->checkTwoStartsWithString($two_err, 'Minimum Payment using Two')) {
                    $error = $this->l('Minimum Payment using Two is 200 NOK');
                } else if ($this->checkTwoStartsWithString($two_err, 'Maximum Payment using Two')) {
                    $error = $this->l('Maximum Payment using Two is 250,000 NOK');
                } else {
                    $error = $two_err;
                }
                $data = array(
                    'approval' => false,
                    'message' => $error,
                );
            } else {
                $data = array(
                    'approval' => true,
                    'message' => sprintf($this->l('By completing the purchase, you verify that you have the legal right to purchase on behalf of %s'), '<strong>' . $address->company . '</strong>'),
                );
            }
        }

        return $data;
    }

    public function getTwoIntentOrderData($cart, $cutomer, $currency, $address)
    {
        $request_data = array(
            'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
            'buyer' => array(
                'company' => array(
                    'company_name' => $address->company,
                    'country_prefix' => Country::getIsoById($address->id_country),
                    'organization_number' => $address->companyid,
                    'website' => '',
                ),
                'representative' => array(
                    'email' => $cutomer->email,
                    'first_name' => $cutomer->firstname,
                    'last_name' => $cutomer->lastname,
                    'phone_number' => $address->phone,
                ),
            ),
            'currency' => $currency->iso_code,
            'merchant_short_name' => $this->merchant_short_name,
            'invoice_type' => $this->product_type,
            'line_items' => array(
                array(
                    'name' => 'Cart',
                    'description' => '',
                    'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
                    'net_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
                    'discount_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS))),
                    'tax_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH) - $cart->getOrderTotal(false, Cart::BOTH))),
                    'tax_class_name' => 'VAT ' . Tools::ps_round($cart->getAverageProductsTaxRate() * 100) . '%',
                    'tax_rate' => (string)($cart->getAverageProductsTaxRate() * 100),
                    'unit_price' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
                    'quantity' => 1,
                    'quantity_unit' => 'item',
                    'image_url' => '',
                    'product_page_url' => '',
                    'type' => 'PHYSICAL',
                    'details' => array(
                        'brand' => '',
                        'categories' => [],
                        'barcodes' => [],
                    ),
                )
            ),
        );

        return $request_data;
    }

    public function getTwoNewOrderData($id_order, $cart)
    {
        $order_reference = round(microtime(1) * 1000);
        $cutomer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $invoice_address = new Address($cart->id_address_invoice);
        $delivery_address = new Address($cart->id_address_delivery);
        $carrier_name = '';
        $tracking_number = '';
        $carrier = new Carrier($cart->id_carrier, $cart->id_lang);
        if (Validate::isLoadedObject($carrier)) {
            $carrier_name = $carrier->name;
        }

        $request_data = array(
            'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
            'net_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
            'currency' => $currency->iso_code,
            'discount_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS))),
            'discount_rate' => '0',
            'invoice_type' => $this->product_type,
            'tax_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH) - $cart->getOrderTotal(false, Cart::BOTH))),
            'tax_rate' => (string)($cart->getAverageProductsTaxRate()),
            'buyer' => array(
                'company' => array(
                    'company_name' => $invoice_address->company,
                    'country_prefix' => Country::getIsoById($invoice_address->id_country),
                    'organization_number' => $invoice_address->companyid,
                    'website' => '',
                ),
                'representative' => array(
                    'email' => $cutomer->email,
                    'first_name' => $cutomer->firstname,
                    'last_name' => $cutomer->lastname,
                    'phone_number' => $invoice_address->phone,
                ),
            ),
            'buyer_department' => $invoice_address->department,
            'buyer_project' => $invoice_address->project,
            'merchant_additional_info' => '',
            'merchant_order_id' => (string)($id_order),
            'merchant_reference' => (string)($order_reference),
            'merchant_urls' => array(
                'merchant_confirmation_url' => $this->context->link->getModuleLink($this->name, 'confirmation', array('id_order' => $id_order), true),
                'merchant_cancel_order_url' => $this->context->link->getModuleLink($this->name, 'cancel', array('id_order' => $id_order), true),
                'merchant_edit_order_url' => '',
                'merchant_order_verification_failed_url' => '',
                'merchant_invoice_url' => '',
                'merchant_shipping_document_url' => ''
            ),
            'billing_address' => array(
                'city' => $invoice_address->city,
                'country' => Country::getIsoById($invoice_address->id_country),
                'organization_name' => $invoice_address->company,
                'postal_code' => $invoice_address->postcode,
                'region' => $invoice_address->id_state ? State::getNameById($invoice_address->id_state) : "",
                'street_address' => $invoice_address->address1 . (isset($invoice_address->address2) ? $invoice_address->address2 : "")
            ),
            'shipping_address' => array(
                'city' => $delivery_address->city,
                'country' => Country::getIsoById($delivery_address->id_country),
                'organization_name' => $delivery_address->company,
                'postal_code' => $delivery_address->postcode,
                'region' => $delivery_address->id_state ? State::getNameById($delivery_address->id_state) : "",
                'street_address' => $delivery_address->address1 . (isset($delivery_address->address2) ? $delivery_address->address2 : "")
            ),
            'shipping_details' => array(
                'carrier_name' => $carrier_name,
                'tracking_number' => $tracking_number,
                'expected_delivery_date' => date('Y-m-d', strtotime('+ 7 days'))
            ),
            'recurring' => false,
            'order_note' => '',
            'line_items' => $this->getTwoProductItems($cart),
        );

        return $request_data;
    }

    public function getTwoUpdateOrderData($order, $orderpaymentdata)
    {
        $cart = new Cart($order->id_cart);
        $currency = new Currency($cart->id_currency);
        $invoice_address = new Address($cart->id_address_invoice);
        $delivery_address = new Address($cart->id_address_delivery);
        $carrier_name = '';
        $tracking_number = '';
        $carrier = new Carrier($cart->id_carrier, $cart->id_lang);
        if (Validate::isLoadedObject($carrier)) {
            $carrier_name = $carrier->name;
        }

        $request_data = array(
            'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
            'net_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
            'currency' => $currency->iso_code,
            'discount_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS))),
            'discount_rate' => '0',
            'invoice_type' => $this->product_type,
            'tax_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH) - $cart->getOrderTotal(false, Cart::BOTH))),
            'tax_rate' => (string)($cart->getAverageProductsTaxRate()),
            'buyer_department' => $invoice_address->department,
            'buyer_project' => $invoice_address->project,
            'merchant_additional_info' => '',
            'merchant_reference' => (string)($orderpaymentdata['two_order_reference']),
            'billing_address' => array(
                'city' => $invoice_address->city,
                'country' => Country::getIsoById($invoice_address->id_country),
                'organization_name' => $invoice_address->company,
                'postal_code' => $invoice_address->postcode,
                'region' => $invoice_address->id_state ? State::getNameById($invoice_address->id_state) : "",
                'street_address' => $invoice_address->address1 . (isset($invoice_address->address2) ? $invoice_address->address2 : "")
            ),
            'shipping_address' => array(
                'city' => $delivery_address->city,
                'country' => Country::getIsoById($delivery_address->id_country),
                'organization_name' => $delivery_address->company,
                'postal_code' => $delivery_address->postcode,
                'region' => $delivery_address->id_state ? State::getNameById($delivery_address->id_state) : "",
                'street_address' => $delivery_address->address1 . (isset($delivery_address->address2) ? $delivery_address->address2 : "")
            ),
            'shipping_details' => array(
                'carrier_name' => $carrier_name,
                'tracking_number' => $tracking_number,
                'expected_delivery_date' => date('Y-m-d', strtotime('+ 7 days'))
            ),
            'recurring' => false,
            'order_note' => '',
            'line_items' => $this->getTwoProductItems($cart),
        );

        return $request_data;
    }

    public function getTwoNewRefundData($order)
    {
        $cart = new Cart($order->id_cart);
        $currency = new Currency($cart->id_currency);

        $request_data = [
            'amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
            'currency' => $currency->iso_code,
            'initiate_payment_to_buyer' => $this->enable_buyer_refund === '1',
            'line_items' => $this->getTwoProductItems($cart),
        ];

        return $request_data;
    }

    public function getTwoProductItems($cart)
    {
        $items = [];
        $carrier = new Carrier($cart->id_carrier, $cart->id_lang);
        $line_items = $cart->getProducts(true);
        foreach ($line_items as $line_item) {
            $categories = Product::getProductCategoriesFull($line_item['id_product'], $cart->id_lang);
            $image = Image::getCover($line_item['id_product']);
            $imagePath = $this->context->link->getImageLink($line_item['link_rewrite'], $image['id_image'], ImageType::getFormattedName('home'));
            $product = array(
                'name' => $line_item['name'],
                'description' => Tools::substr(strip_tags($line_item['description_short']), 0, 255),
                'gross_amount' => (string)($this->getTwoRoundAmount($line_item['total_wt'])),
                'net_amount' => (string)($this->getTwoRoundAmount($line_item['total'])),
                'discount_amount' => (string)($this->getTwoRoundAmount($line_item['reduction'])),
                'tax_amount' => (string)($this->getTwoRoundAmount($line_item['total_wt'] - $line_item['total'])),
                'tax_class_name' => 'VAT ' . $line_item['rate'] . '%',
                'tax_rate' => (string)($this->getTwoRoundAmount($line_item['rate'] / 100)),
                'unit_price' => (string)($this->getTwoRoundAmount($line_item['price_wt'])),
                'quantity' => $line_item['cart_quantity'],
                'quantity_unit' => 'item',
                'image_url' => $imagePath,
                'product_page_url' => $this->context->link->getProductLink($line_item['id_product']),
                'type' => 'PHYSICAL',
                'details' => array(
                    'brand' => $line_item['manufacturer_name'],
                    'barcodes' => array(
                        array(
                            'type' => 'SKU',
                            'value' => $line_item['ean13']
                        ),
                        array(
                            'type' => 'UPC',
                            'value' => $line_item['upc']
                        ),
                    ),
                ),
            );
            $product['details']['categories'] = [];
            if ($categories) {
                foreach ($categories as $category) {
                    $product['details']['categories'][] = $category['name'];
                }
            }

            $items[] = $product;
        }

        if (Validate::isLoadedObject($carrier) && $cart->getOrderTotal(true, Cart::ONLY_SHIPPING) > 0) {
            $tax_rate = $carrier->getTaxesRate(new Address((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
            $shipping_line = array(
                'name' => 'Shipping - ' . $carrier->name,
                'description' => '',
                'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_SHIPPING))),
                'net_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::ONLY_SHIPPING))),
                'discount_amount' => '0',
                'tax_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_SHIPPING) - $cart->getOrderTotal(false, Cart::ONLY_SHIPPING))),
                'tax_class_name' => 'VAT ' . $tax_rate . '%',
                'tax_rate' => (string)($cart->getAverageProductsTaxRate()),
                'unit_price' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::ONLY_SHIPPING))),
                'quantity' => 1,
                'quantity_unit' => 'sc', // shipment charge
                'image_url' => '',
                'product_page_url' => '',
                'type' => 'SHIPPING_FEE'
            );

            $items[] = $shipping_line;
        }

        if ($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS) > 0) {
            $tax_rate = ($cart->getAverageProductsTaxRate() * 100);
            $discount_line = array(
                'name' => 'Discount',
                'description' => '',
                'gross_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS))),
                'net_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS))),
                'discount_amount' => '0',
                'tax_amount' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS) - $cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS))),
                'tax_class_name' => 'VAT ' . $tax_rate . '%',
                'tax_rate' => (string)($cart->getAverageProductsTaxRate()),
                'unit_price' => (string)($this->getTwoRoundAmount($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS))),
                'quantity' => 1,
                'quantity_unit' => 'item',
                'image_url' => '',
                'product_page_url' => '',
                'type' => 'PHYSICAL'
            );

            $items[] = $discount_line;
        }

        return $items;
    }

    public function getTwoRoundAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    public function getTwoCheckoutHostUrl()
    {
        $two_checkout_url = 'https://api.tillit.ai';
        if ($this->isTwoCheckoutDevelopment()) {
            $two_checkout_url = Configuration::get('PS_TWO_PAYMENT_DEV_MODE');
        } else if ($this->payment_mode == 'test') {
            $two_checkout_url = 'https://test.api.tillit.ai';
        }
        return $two_checkout_url;
    }

    public function isTwoCheckoutDevelopment()
    {
        $hostname = str_replace(array('http://', 'https://'), '', $_SERVER['SERVER_NAME']);

        if (in_array($hostname, array('tillit.ps.local', 'localhost')) || Tools::substr($hostname, -8) === '.two.inc') {
            return true;
        }

        return false;
    }

    public function setTwoPaymentRequest($endpoint, $payload = [], $method = 'POST', $lang = false)
    {
        if ($method == "POST" || $method == "PUT") {
            $url = sprintf('%s%s', $this->getTwoCheckoutHostUrl(), $endpoint);
            if($lang){
                $url = $url . '&client=PS&client_v=' . $this->version;
            } else {
                $url = $url . '?client=PS&client_v=' . $this->version;
            }
            $params = empty($payload) ? '' : json_encode($payload);
            $headers = [
                'Content-Type: application/json; charset=utf-8',
                'X-API-Key:' . $this->api_key,
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $response = curl_exec($ch);
            $response = json_decode($response, true);
            curl_getinfo($ch);
            curl_close($ch);
        } else {
            $url = sprintf('%s%s', $this->getTwoCheckoutHostUrl(), $endpoint);
            $url = $url . '?client=PS&client_v=' . $this->version;
            $headers = [
                'Content-Type: application/json; charset=utf-8',
                'X-API-Key:' . $this->api_key,
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $response = curl_exec($ch);
            $response = json_decode($response, true);
            curl_getinfo($ch);
            curl_close($ch);
        }

        return $response;
    }

    public function checkTwoStartsWithString($string, $startString)
    {
        $len = Tools::strlen($startString);
        return (Tools::substr($string, 0, $len) === $startString);
    }

    public function getTwoErrorMessage($body)
    {
        if (!$body) {
            return $this->l('Something went wrong please contact store owner.');
        }

        if (isset($body['response']['code']) && $body['response'] && $body['response']['code'] && $body['response']['code'] >= 400) {
            return sprintf($this->l('Two response code %d'), $body['response']['code']);
        }

        if (is_string($body)) {
            return $body;
        }

        if (isset($body['error_details']) && $body['error_details']) {
            return $body['error_details'];
        }

        if (isset($body['error_code']) && $body['error_code']) {
            return $body['error_message'];
        }
    }

    public function setTwoOrderPaymentData($id_order, $payment_data)
    {
        $result = $this->getTwoOrderPaymentData($id_order);
        if ($result) {
            $data = array(
                'id_order' => pSQL($id_order),
                'two_order_id' => pSQL($payment_data['two_order_id']),
                'two_order_reference' => pSQL($payment_data['two_order_reference']),
                'two_order_state' => pSQL($payment_data['two_order_state']),
                'two_order_status' => pSQL($payment_data['two_order_status']),
                'two_day_on_invoice' => pSQL($payment_data['two_day_on_invoice']),
                'two_invoice_url' => pSQL($payment_data['two_invoice_url']),
            );
            Db::getInstance()->update('twopayment', $data, 'id_order = ' . (int) $id_order);
        } else {
            $data = array(
                'id_order' => pSQL($id_order),
                'two_order_id' => pSQL($payment_data['two_order_id']),
                'two_order_reference' => pSQL($payment_data['two_order_reference']),
                'two_order_state' => pSQL($payment_data['two_order_state']),
                'two_order_status' => pSQL($payment_data['two_order_status']),
                'two_day_on_invoice' => pSQL($payment_data['two_day_on_invoice']),
                'two_invoice_url' => pSQL($payment_data['two_invoice_url']),
            );
            Db::getInstance()->insert('twopayment', $data);
        }
    }

    public function getTwoNextOrderID()
    {
        $id_order = Db::getInstance()->getValue('SELECT o.id_order FROM `' . _DB_PREFIX_ . 'orders` o' . Shop::addSqlAssociation('orders', 'o') . ' ORDER BY o.id_order DESC', false);
        return $id_order + 1;
    }

    public function getTwoOrderPaymentData($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'twopayment WHERE id_order = ' . (int) $id_order;
        $result = Db::getInstance()->getRow($sql);
        return $result;
    }

    public function hookDisplayPaymentReturn($params)
    {
        $id_order = $params['order']->id;
        $twopaymentdata = $this->getTwoOrderPaymentData($id_order);
        if ($twopaymentdata) {
            $this->context->smarty->assign(array(
                'twopaymentdata' => $twopaymentdata,
            ));
            return $this->context->smarty->fetch('module:twopayment/views/templates/hook/displayPaymentReturn.tpl');
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        $id_order = $params['order']->id;
        $twopaymentdata = $this->getTwoOrderPaymentData($id_order);
        if ($twopaymentdata) {
            $this->context->smarty->assign(array(
                'twopaymentdata' => $twopaymentdata,
            ));
            return $this->context->smarty->fetch('module:twopayment/views/templates/hook/displayOrderDetail.tpl');
        }
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        $id_order = $params['id_order'];
        $twopaymentdata = $this->getTwoOrderPaymentData($id_order);
        if ($twopaymentdata) {
            $this->context->smarty->assign(array(
                'twopaymentdata' => $twopaymentdata,
            ));
            return $this->context->smarty->fetch('module:twopayment/views/templates/hook/displayAdminOrderLeft.tpl');
        }
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        $id_order = $params['id_order'];
        $twopaymentdata = $this->getTwoOrderPaymentData($id_order);
        if ($twopaymentdata) {
            return $this->context->smarty->fetch('module:twopayment/views/templates/hook/displayAdminOrderTabLink.tpl');
        }
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        $id_order = $params['id_order'];
        $twopaymentdata = $this->getTwoOrderPaymentData($id_order);

        if ($twopaymentdata) {
            $this->context->smarty->assign(array(
                'twopaymentdata' => $twopaymentdata,
            ));
            return $this->context->smarty->fetch('module:twopayment/views/templates/hook/displayAdminOrderTabContent.tpl');
        }
    }
}
