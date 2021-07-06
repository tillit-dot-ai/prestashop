{*
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
*}

<div class="tab-pane" id="tillit-payment-info">
    <div class="table-responsive">
        <table class="table">
            <tbody>
                {if $tillitpaymentdata.tillit_order_id}
                    <tr><td><strong>{l s='Tillit Order ID' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_id}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_order_reference}
                    <tr><td><strong>{l s='Tillit Order Reference' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_reference}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_order_state}
                    <tr><td><strong>{l s='Tillit Order State' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_state}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_order_status}
                    <tr><td><strong>{l s='Tillit Order Status' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_status}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_day_on_invoice}
                    <tr><td><strong>{l s='Tillit Day On Invoice' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_day_on_invoice}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_invoice_url}
                    <tr><td><strong>{l s='Tillit Invoice Url' mod='tillit'}</strong></td> <td><a href="{$tillitpaymentdata.tillit_invoice_url}" target="_blank">{$tillitpaymentdata.tillit_invoice_url}</a></td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>


