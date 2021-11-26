<?php
/**
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_1($object)
{
    Configuration::updateValue('PS_twopayment_DEV_MODE', 'https://staging.api.tillit.ai');
    return true;
}
