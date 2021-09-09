{*
 * 2021 Tillit
 * @author Tillit
 * @copyright Tillit Team
 * @license Tillit Commercial License
 *}

<div id="tillit-payment-info" class="box">
    <h4>{l s='Tillit Payment Info' mod='tillit'}</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <tbody>
                {if $tillitpaymentdata.tillit_order_id}
                    <tr><td><strong>{l s='Tillit Order ID' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_id}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_order_reference}
                    <tr><td><strong>{l s='Tillit Order Reference' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_reference}</td></tr>
                {/if}
                {* {if $tillitpaymentdata.tillit_order_state}
                    <tr><td><strong>{l s='Tillit Order State' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_state}</td></tr>
                {/if}
                {if $tillitpaymentdata.tillit_order_status}
                    <tr><td><strong>{l s='Tillit Order Status' mod='tillit'}</strong></td> <td>{$tillitpaymentdata.tillit_order_status}</td></tr>
                {/if} *}
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

