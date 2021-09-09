{*
 * 2021 Tillit
 * @author Tillit
 * @copyright Tillit Team
 * @license Tillit Commercial License
 *}

<section>
    <p>{$subtitle}</p>
    {if $enable_order_intent}
        {if $payment_enable}
            <p class="alert alert-success">{$message nofilter}</p>
        {else}
            <p class="alert alert-danger">{$message nofilter}</p>
        {/if}
    {/if}
</section>
{if $enable_order_intent}
    {literal}
        <script type="text/javascript">
            var payment_enable = "{/literal}{$payment_enable}{literal}";
            var payments = document.getElementsByName('payment-option');
            for (var i = 0, length = payments.length; i < length; i++) {
                var payment = payments[i].getAttribute('data-module-name');
                if (payment == "tillit") {
                    var dataID = payments[i].getAttribute('id');
                    if (payment_enable == '1') {
                        document.getElementById(dataID).checked = true;
                    } else {
                        document.getElementById(dataID + "-container").classList.add("tillit-payment-option");
                        document.getElementById(dataID).disabled = true;
                    }
                    document.getElementById(dataID + "-additional-information").classList.add("tillit-additional-information");
                }
            }
        </script>
    {/literal}
{/if}
