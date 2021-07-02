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

class Address extends AddressCore
{
    public $account_type;
    public $companyid;
    public $department;
    public $project;
    
    public function __construct($id_address = null, $id_lang = null)
    {
        self::$definition['fields']['account_type'] = array('type' => self::TYPE_STRING);
        self::$definition['fields']['companyid'] = array('type' => self::TYPE_STRING);
        self::$definition['fields']['department'] = array('type' => self::TYPE_STRING);
        self::$definition['fields']['project'] = array('type' => self::TYPE_STRING);
		parent::__construct($id_address, $id_lang);
    }
}
