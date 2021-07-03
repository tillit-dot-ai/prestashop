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
        $this->api_key = Configuration::get('PS_TILLIT_MERACHANT_API_KEY');
        $this->payment_mode = Configuration::get('PS_TILLIT_PAYMENT_MODE');
        $this->enable_company_name = Configuration::get('PS_TILLIT_ENABLE_COMPANY_NAME');
        $this->enable_company_id = Configuration::get('PS_TILLIT_ENABLE_COMPANY_ID');
        $this->enable_order_intent = Configuration::get('PS_TILLIT_ENABLE_ORDER_INTENT');
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
            $this->installTillitSettings() &&
            $this->installTillitTables();
    }

    protected function installTillitSettings()
    {
        $installData = array();
        foreach ($this->languages as $language) {
            $installData['PS_TILLIT_TITLE'][(int) $language['id_lang']] = 'Business invoice 30 days';
            $installData['PS_TILLIT_SUB_TITLE'][(int) $language['id_lang']] = 'Receive the invoice via EHF and PDF';
        }
        Configuration::updateValue('PS_TILLIT_TAB_VALUE', 1);
        Configuration::updateValue('PS_TILLIT_TITLE', $installData['PS_TILLIT_TITLE']);
        Configuration::updateValue('PS_TILLIT_SUB_TITLE', $installData['PS_TILLIT_SUB_TITLE']);
        Configuration::updateValue('PS_TILLIT_PAYMENT_MODE', 'stg');
        Configuration::updateValue('PS_TILLIT_MERACHANT_ID', '');
        Configuration::updateValue('PS_TILLIT_MERACHANT_API_KEY', '');
        Configuration::updateValue('PS_TILLIT_PRODUCT_TYPE', 'product_funded');
        Configuration::updateValue('PS_TILLIT_DAY_ON_INVOICE', 14);
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_NAME', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_ID', 1);
        Configuration::updateValue('PS_TILLIT_FANILIZE_PURCHASE', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_ORDER_INTENT', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_B2B_B2C', 1);
        Configuration::updateValue('PS_TILLIT_ENABLE_BUYER_REFUND', 1);
        return true;
    }

    protected function installTillitTables()
    {
        $sql = array();
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `account_type` VARCHAR(255) NULL';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `companyid` VARCHAR(255) NULL';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `department` VARCHAR(255) NULL';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` ADD COLUMN `project` VARCHAR(255) NULL';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        } return true;
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
            $this->uninstallTillitSettings() &&
            $this->uninstallTillitTables();
    }

    protected function uninstallTillitTables()
    {
        $sql = array();
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` DROP COLUMN `account_type`';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` DROP COLUMN `companyid`';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` DROP COLUMN `department`';
        $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'address` DROP COLUMN `project`';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        } return true;
    }

    protected function uninstallTillitSettings()
    {
        Configuration::deleteByName('PS_TILLIT_TAB_VALUE');
        Configuration::deleteByName('PS_TILLIT_TITLE');
        Configuration::deleteByName('PS_TILLIT_SUB_TITLE');
        Configuration::deleteByName('PS_TILLIT_PAYMENT_MODE');
        Configuration::deleteByName('PS_TILLIT_MERACHANT_ID');
        Configuration::deleteByName('PS_TILLIT_MERACHANT_API_KEY');
        Configuration::deleteByName('PS_TILLIT_MERACHANT_LOGO');
        Configuration::deleteByName('PS_TILLIT_PRODUCT_TYPE');
        Configuration::deleteByName('PS_TILLIT_DAY_ON_INVOICE');
        Configuration::deleteByName('PS_TILLIT_ENABLE_COMPANY_NAME');
        Configuration::deleteByName('PS_TILLIT_ENABLE_COMPANY_ID');
        Configuration::deleteByName('PS_TILLIT_FANILIZE_PURCHASE');
        Configuration::deleteByName('PS_TILLIT_ENABLE_ORDER_INTENT');
        Configuration::deleteByName('PS_TILLIT_ENABLE_B2B_B2C');
        Configuration::deleteByName('PS_TILLIT_ENABLE_BUYER_REFUND');
        return true;
    }

    public function getContent()
    {
        if (((bool) Tools::isSubmit('deleteLogo')) == true) {
            Configuration::updateValue('PS_TILLIT_TAB_VALUE', 1);
            $file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . Configuration::get('PS_TILLIT_MERACHANT_LOGO');
            if (file_exists($file_name) && unlink($file_name)) {
                Configuration::updateValue('PS_TILLIT_MERACHANT_LOGO', '');
                $this->sendTillitLogoToMerchant();
                $this->output .= $this->displayConfirmation($this->l('General settings are updated.'));
            }
        }
        if (((bool) Tools::isSubmit('submitTillitGeneralForm')) == true) {
            Configuration::updateValue('PS_TILLIT_TAB_VALUE', 1);
            $this->validTillitGeneralFormValues();
            if (!count($this->errors)) {
                $this->saveTillitGeneralFormValues();
            } else {
                foreach ($this->errors as $err) {
                    $this->output .= $this->displayError($err);
                }
            }
        }

        if (((bool) Tools::isSubmit('submitTillitOtherForm')) == true) {
            Configuration::updateValue('PS_TILLIT_TAB_VALUE', 2);
            $this->saveTillitOtherFormValues();
        }

        $this->context->smarty->assign(
            array(
                'renderTillitGeneralForm' => $this->renderTillitGeneralForm(),
                'renderTillitOtherForm' => $this->renderTillitOtherForm(),
                'tillittabvalue' => Configuration::get('PS_TILLIT_TAB_VALUE'),
            )
        );

        $this->output .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
        return $this->output;
    }

    protected function renderTillitGeneralForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTillitGeneralForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTillitGeneralFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTillitGeneralForm()));
    }

    protected function getTillitGeneralForm()
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
                        'type' => 'text',
                        'label' => $this->l('Merchant id'),
                        'name' => 'PS_TILLIT_MERACHANT_ID',
                        'required' => true,
                        'hint' => $this->l('Enter your merchant id which is provided by tillit.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Api key'),
                        'name' => 'PS_TILLIT_MERACHANT_API_KEY',
                        'required' => true,
                        'hint' => $this->l('Enter your api key which is provided by tillit.'),
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Logo'),
                        'name' => 'PS_TILLIT_MERACHANT_LOGO',
                        'hint' => $this->l('Upload your merchant logo.'),
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
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        return $fields_form;
    }

    protected function getTillitGeneralFormValues()
    {
        $fields_values = array();
        foreach ($this->languages as $language) {
            $fields_values['PS_TILLIT_TITLE'][$language['id_lang']] = Tools::getValue('PS_TILLIT_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TILLIT_TITLE', (int) $language['id_lang']));
            $fields_values['PS_TILLIT_SUB_TITLE'][$language['id_lang']] = Tools::getValue('PS_TILLIT_SUB_TITLE_' . (int) $language['id_lang'], Configuration::get('PS_TILLIT_SUB_TITLE', (int) $language['id_lang']));
        }
        $fields_values['PS_TILLIT_MERACHANT_ID'] = Tools::getValue('PS_TILLIT_MERACHANT_ID', Configuration::get('PS_TILLIT_MERACHANT_ID'));
        $fields_values['PS_TILLIT_MERACHANT_API_KEY'] = Tools::getValue('PS_TILLIT_MERACHANT_API_KEY', Configuration::get('PS_TILLIT_MERACHANT_API_KEY'));
        $fields_values['PS_TILLIT_MERACHANT_LOGO'] = Tools::getValue('PS_TILLIT_MERACHANT_LOGO', Configuration::get('PS_TILLIT_MERACHANT_LOGO'));
        $fields_values['PS_TILLIT_DAY_ON_INVOICE'] = Tools::getValue('PS_TILLIT_DAY_ON_INVOICE', Configuration::get('PS_TILLIT_DAY_ON_INVOICE'));
        return $fields_values;
    }

    protected function validTillitGeneralFormValues()
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
        if (Tools::isEmpty(Tools::getValue('PS_TILLIT_MERACHANT_API_KEY'))) {
            $this->errors[] = $this->l('Enter a api key.');
        }
        if (Tools::isEmpty(Tools::getValue('PS_TILLIT_DAY_ON_INVOICE'))) {
            $this->errors[] = $this->l('Enter a number of days on invoice.');
        }
    }

    protected function saveTillitGeneralFormValues()
    {
        $imagefile = "";
        $update_images_values = false;
        if (isset($_FILES['PS_TILLIT_MERACHANT_LOGO']) && isset($_FILES['PS_TILLIT_MERACHANT_LOGO']['tmp_name']) && !empty($_FILES['PS_TILLIT_MERACHANT_LOGO']['tmp_name'])) {
            if ($error = ImageManager::validateUpload($_FILES['PS_TILLIT_MERACHANT_LOGO'], 4000000)) {
                return $error;
            } else {
                $ext = Tools::substr($_FILES['PS_TILLIT_MERACHANT_LOGO']['name'], Tools::substr($_FILES['PS_TILLIT_MERACHANT_LOGO']['name'], '.') + 1);
                $file_name = md5($_FILES['PS_TILLIT_MERACHANT_LOGO']['name']) . '.' . $ext;

                if (!move_uploaded_file($_FILES['PS_TILLIT_MERACHANT_LOGO']['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $file_name)) {
                    return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                } else {
                    if (Configuration::get('PS_TILLIT_MERACHANT_LOGO') != $file_name) {
                        @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . Configuration::get('PS_TILLIT_MERACHANT_LOGO'));
                    }
                    $imagefile = $file_name;
                }
            }

            $update_images_values = true;
        }

        if ($update_images_values) {
            Configuration::updateValue('PS_TILLIT_MERACHANT_LOGO', $imagefile);
            $this->sendTillitLogoToMerchant();
        }

        $values = array();
        foreach ($this->languages as $language) {
            $values['PS_TILLIT_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TILLIT_TITLE_' . (int) $language['id_lang']);
            $values['PS_TILLIT_SUB_TITLE'][(int) $language['id_lang']] = Tools::getValue('PS_TILLIT_SUB_TITLE_' . (int) $language['id_lang']);
        }
        Configuration::updateValue('PS_TILLIT_TITLE', $values['PS_TILLIT_TITLE']);
        Configuration::updateValue('PS_TILLIT_SUB_TITLE', $values['PS_TILLIT_SUB_TITLE']);
        Configuration::updateValue('PS_TILLIT_MERACHANT_ID', Tools::getValue('PS_TILLIT_MERACHANT_ID'));
        Configuration::updateValue('PS_TILLIT_MERACHANT_API_KEY', Tools::getValue('PS_TILLIT_MERACHANT_API_KEY'));
        Configuration::updateValue('PS_TILLIT_PRODUCT_TYPE', Tools::getValue('PS_TILLIT_PRODUCT_TYPE'));
        Configuration::updateValue('PS_TILLIT_DAY_ON_INVOICE', Tools::getValue('PS_TILLIT_DAY_ON_INVOICE'));

        $this->output .= $this->displayConfirmation($this->l('General settings are updated.'));
    }

    protected function renderTillitOtherForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitTillitOtherForm';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getTillitOtherFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getTillitOtherForm()));
    }

    protected function getTillitOtherForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Other Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
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
                        'label' => $this->l('Pre-approve the buyer during checkout and disable tillit if the buyer is declined'),
                        'name' => 'PS_TILLIT_ENABLE_ORDER_INTENT',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then pre-approve the buyer during checkout and disable tillit if the buyer is declined.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_ORDER_INTENT_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_ORDER_INTENT_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate B2C/B2B Options chekout page'),
                        'name' => 'PS_TILLIT_ENABLE_B2B_B2C',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then allow different types of account (personal/business).'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_B2B_B2C_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_B2B_B2C_OFF',
                                'value' => 0,
                                'label' => $this->l('No')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Initiate payment to buyer on refund'),
                        'name' => 'PS_TILLIT_ENABLE_BUYER_REFUND',
                        'is_bool' => true,
                        'hint' => $this->l('If you choose YES then allow to initiate payment buyer on refund.'),
                        'required' => true,
                        'values' => array(
                            array(
                                'id' => 'PS_TILLIT_ENABLE_BUYER_REFUND_ON',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'PS_TILLIT_ENABLE_BUYER_REFUND_OFF',
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

    protected function getTillitOtherFormValues()
    {
        $fields_values = array();
        $fields_values['PS_TILLIT_PAYMENT_MODE'] = Tools::getValue('PS_TILLIT_PAYMENT_MODE', Configuration::get('PS_TILLIT_PAYMENT_MODE'));
        $fields_values['PS_TILLIT_ENABLE_COMPANY_NAME'] = Tools::getValue('PS_TILLIT_ENABLE_COMPANY_NAME', Configuration::get('PS_TILLIT_ENABLE_COMPANY_NAME'));
        $fields_values['PS_TILLIT_ENABLE_COMPANY_ID'] = Tools::getValue('PS_TILLIT_ENABLE_COMPANY_ID', Configuration::get('PS_TILLIT_ENABLE_COMPANY_ID'));
        $fields_values['PS_TILLIT_FANILIZE_PURCHASE'] = Tools::getValue('PS_TILLIT_FANILIZE_PURCHASE', Configuration::get('PS_TILLIT_FANILIZE_PURCHASE'));
        $fields_values['PS_TILLIT_ENABLE_ORDER_INTENT'] = Tools::getValue('PS_TILLIT_ENABLE_ORDER_INTENT', Configuration::get('PS_TILLIT_ENABLE_ORDER_INTENT'));
        $fields_values['PS_TILLIT_ENABLE_B2B_B2C'] = Tools::getValue('PS_TILLIT_ENABLE_B2B_B2C', Configuration::get('PS_TILLIT_ENABLE_B2B_B2C'));
        $fields_values['PS_TILLIT_ENABLE_BUYER_REFUND'] = Tools::getValue('PS_TILLIT_ENABLE_BUYER_REFUND', Configuration::get('PS_TILLIT_ENABLE_BUYER_REFUND'));
        return $fields_values;
    }

    protected function saveTillitOtherFormValues()
    {
        Configuration::updateValue('PS_TILLIT_PAYMENT_MODE', Tools::getValue('PS_TILLIT_PAYMENT_MODE'));
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_NAME', Tools::getValue('PS_TILLIT_ENABLE_COMPANY_NAME'));
        Configuration::updateValue('PS_TILLIT_ENABLE_COMPANY_ID', Tools::getValue('PS_TILLIT_ENABLE_COMPANY_ID'));
        Configuration::updateValue('PS_TILLIT_FANILIZE_PURCHASE', Tools::getValue('PS_TILLIT_FANILIZE_PURCHASE'));
        Configuration::updateValue('PS_TILLIT_ENABLE_ORDER_INTENT', Tools::getValue('PS_TILLIT_ENABLE_ORDER_INTENT'));
        Configuration::updateValue('PS_TILLIT_ENABLE_B2B_B2C', Tools::getValue('PS_TILLIT_ENABLE_B2B_B2C'));
        Configuration::updateValue('PS_TILLIT_ENABLE_BUYER_REFUND', Tools::getValue('PS_TILLIT_ENABLE_BUYER_REFUND'));

        $this->output .= $this->displayConfirmation($this->l('Other settings are updated.'));
    }

    public function hookActionFrontControllerSetMedia()
    {
        Media::addJsDef(array('tillit' => array(
                'tillit_search_host' => $this->getTillitSearchHostUrl(),
                'tillit_checkout_host' => $this->getTillitCheckoutHostUrl(),
                'company_name_search' => $this->enable_company_name,
                'company_id_search' => $this->enable_company_id,
                'merchant_id' => $this->merchant_id,
        )));
        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->registerStylesheet('tillit-select2', 'modules/tillit/views/css/select2.min.css', array('priority' => 200, 'media' => 'all'));
        $this->context->controller->registerJavascript('tillit-select2', 'modules/tillit/views/js/select2.min.js', array('priority' => 200, 'attribute' => 'async'));
        $this->context->controller->registerJavascript('tillit-script', 'modules/tillit/views/js/tillit.js', array('priority' => 200, 'attribute' => 'async'));
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (Tools::isEmpty($this->merchant_id) || Tools::isEmpty($this->api_key)) {
            return;
        }

        //check Pre-approve buyer for enable payment method
        if ($this->enable_order_intent) {
            $approval_buyer = $this->getTillitApprovalBuyer();
            if (!$approval_buyer) {
                return;
            }
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

    protected function sendTillitLogoToMerchant()
    {
        $image_logo = Configuration::get('PS_TILLIT_MERACHANT_LOGO');
        if ($image_logo && file_exists(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'views/img' . DIRECTORY_SEPARATOR . $image_logo)) {
            $logo_path = $this->context->link->protocol_content . Tools::getMediaServer($image_logo) . $this->_path . 'views/img/' . $image_logo;
            $this->setTillitPaymentRequest("/v1/merchant/" . $this->merchant_id . "/update", [
                'merchant_id' => $this->merchant_id,
                'logo_path' => $logo_path
            ]);
        } else {
            $this->setTillitPaymentRequest("/v1/merchant/" . $this->merchant_id . "/update", [
                'merchant_id' => $this->merchant_id,
                'logo_path' => ''
            ]);
        }
    }

    protected function getTillitApprovalBuyer()
    {
        $cart = $this->context->cart;
        $cutomer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $address = new Address(intval($cart->id_address_invoice));
        
        if ($address->account_type == 'personal') {
            return false;
        }
        
        $data = $this->setTillitPaymentRequest("/v1/order_intent", [
            'gross_amount' => strval($this->getTillitRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
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
            'merchant_id' => $this->merchant_id,
            'line_items' => array(
                array(
                    'name' => 'Cart',
                    'description' => '',
                    'gross_amount' => strval($this->getTillitRoundAmount($cart->getOrderTotal(true, Cart::BOTH))),
                    'net_amount' => strval($this->getTillitRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
                    'discount_amount' => strval($this->getTillitRoundAmount($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS))),
                    'tax_amount' => strval($this->getTillitRoundAmount($cart->getOrderTotal(true, Cart::BOTH) - $cart->getOrderTotal(false, Cart::BOTH))),
                    'tax_class_name' => 'VAT ' . Tools::ps_round($cart->getAverageProductsTaxRate() * 100) . '%',
                    'tax_rate' => strval($cart->getAverageProductsTaxRate() * 100),
                    'unit_price' => strval($this->getTillitRoundAmount($cart->getOrderTotal(false, Cart::BOTH))),
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
        ]);
        
        if(isset($data['approved']) && $data['approved']) {
            return true;
        } 
        return false;
    }

    public function getTillitRoundAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    protected function getTillitProductItems($cart)
    {
        $items = [];
        $line_items = $cart->getProducts(true);
        foreach ($line_items as $line_item) {
            $image = Image::getCover($line_item['id_product']);
            $imagePath = $this->context->link->getImageLink($line_item['link_rewrite'], $image['id_image'], 'home_default');
            $product = array(
                'name' => $line_item['name'],
                'description' => substr($line_item['description_short'], 0, 255),
                'gross_amount' => $line_item['total_wt'],
                'net_amount' => $line_item['total'],
                'discount_amount' => $line_item['reduction'],
                'tax_amount' => '',
                'tax_class_name' => $line_item['tax_name'],
                'tax_rate' => $line_item['rate'],
                'unit_price' => $line_item['price_without_reduction'],
                'quantity' => $line_item['cart_quantity'],
                'quantity_unit' => 'item',
                'image_url' => $imagePath,
                'product_page_url' => $this->context->link->getProductLink($line_item['id_product']),
                'type' => 'PHYSICAL',
                'details' => [
                    'barcodes' => [
                        [
                            'type' => 'SKU',
                            'id' => $line_item['ean13']
                        ]
                    ]
                ]
            );

            $items[] = $product;
        }
        echo "<pre>";
        print_r($cart);
        echo "<pre>";
        die();
    }

    protected function getTillitSearchHostUrl()
    {
        return 'https://search-api-demo-j6whfmualq-lz.a.run.app';
    }

    protected function getTillitCheckoutHostUrl()
    {
        return $this->payment_mode == 'prod' ? 'https://api.tillit.ai' : ($this->payment_mode == 'dev' ? 'http://huynguyen.hopto.org:8084' : 'https://staging.api.tillit.ai');
    }

    protected function setTillitPaymentRequest($endpoint, $payload = [], $method = 'POST')
    {
        if ($method == "POST" || $method == "PUT") {
            $url = sprintf('%s%s', $this->getTillitCheckoutHostUrl(), $endpoint);
            $params = empty($payload) ? '' : json_encode($payload);
            $headers = [
                'Content-Type: application/json; charset=utf-8',
                'Tillit-Merchant-Id:' . $this->merchant_id,
                'Authorization:' . sprintf('Basic %s', base64_encode(
                        $this->merchant_id . ':' . $this->api_key
                ))
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            //curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $response = curl_exec($ch);
            $response = json_decode($response, true);
            $info = curl_getinfo($ch);
            curl_close($ch);
        }
        return $response;
    }
}
