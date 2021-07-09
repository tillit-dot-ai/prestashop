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

<section>
    <p>{$subtitle}</p>
    {if $payment_enable}
        <p class="alert alert-success">{$message nofilter}</p>
    {else}
        <p class="alert alert-danger">{$message nofilter}</p>
    {/if}
</section>

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
