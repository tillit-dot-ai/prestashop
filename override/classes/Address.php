<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
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
