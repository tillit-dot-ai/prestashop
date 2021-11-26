{*
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 *}

<div class="tab-pane" id="two-payment-info">
    <div class="table-responsive">
        <table class="table">
            <tbody>
                {if $twopaymentdata.two_order_id}
                    <tr><td><strong>{l s='Two Order ID' mod='two_payment'}</strong></td> <td>{$twopaymentdata.two_order_id}</td></tr>
                {/if}
                {if $twopaymentdata.two_order_reference}
                    <tr><td><strong>{l s='Two Order Reference' mod='two_payment'}</strong></td> <td>{$twopaymentdata.two_order_reference}</td></tr>
                {/if}
                {if $twopaymentdata.two_day_on_invoice}
                    <tr><td><strong>{l s='Two Day On Invoice' mod='two_payment'}</strong></td> <td>{$twopaymentdata.two_day_on_invoice}</td></tr>
                {/if}
                {if $twopaymentdata.two_invoice_url}
                    <tr><td><strong>{l s='Two Invoice Url' mod='two_payment'}</strong></td> <td><a href="{$twopaymentdata.two_invoice_url}" target="_blank">{$twopaymentdata.two_invoice_url}</a></td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>


