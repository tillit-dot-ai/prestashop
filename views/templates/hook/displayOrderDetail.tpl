{*
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */
 *}

<div id="tillit-payment-info" class="box">
  <h4>{l s='Two Payment Info' mod='two_payment'}</h4>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <tbody>
        {if $tillitpaymentdata.tillit_order_id}
          <tr>
            <td><strong>{l s='Two Order ID' mod='two_payment'}</strong></td>
            <td>{$tillitpaymentdata.tillit_order_id}</td>
          </tr>
        {/if}
        {if $tillitpaymentdata.tillit_order_reference}
          <tr>
            <td><strong>{l s='Two Order Reference' mod='two_payment'}</strong></td>
            <td>{$tillitpaymentdata.tillit_order_reference}</td>
          </tr>
        {/if}
        {if $tillitpaymentdata.tillit_day_on_invoice}
          <tr>
            <td><strong>{l s='Two Day On Invoice' mod='two_payment'}</strong></td>
            <td>{$tillitpaymentdata.tillit_day_on_invoice}</td>
          </tr>
        {/if}
        {if $tillitpaymentdata.tillit_invoice_url}
          <tr>
            <td><strong>{l s='Two Invoice Url' mod='two_payment'}</strong></td>
            <td><a href="{$tillitpaymentdata.tillit_invoice_url}"
                target="_blank">{$tillitpaymentdata.tillit_invoice_url}</a></td>
          </tr>
        {/if}
      </tbody>
    </table>
  </div>
</div>