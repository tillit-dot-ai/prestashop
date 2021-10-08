<?php
/**
 * 2021 Tillit
 * @author Tillit
 * @copyright Tillit Team
 * @license Tillit Commercial License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_1($object)
{
    Configuration::updateValue('PS_TILLIT_PAYMENT_DEV_MODE', 'https://staging.api.tillit.ai');
    return true;
}
