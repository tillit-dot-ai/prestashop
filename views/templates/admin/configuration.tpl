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

<div class="row">
    <div id="tillit-tabs" class="col-lg-2 col-md-3">
        <div class="list-group">
            <a class="list-group-item {if $tillittabvalue == 1}active{/if}" href="#general-settings" aria-controls="general-settings" role="tab" data-toggle="tab">{l s='General Settings' mod='tillit'}</a>
            <a class="list-group-item {if $tillittabvalue == 2}active{/if}" href="#other-settings" aria-controls="other-settings" role="tab" data-toggle="tab">{l s='Other Settings' mod='tillit'}</a>
            <a class="list-group-item {if $tillittabvalue == 3}active{/if}" href="#order-status-settings" aria-controls="order-status-settings" role="tab" data-toggle="tab">{l s='Order Status Settings' mod='tillit'}</a>
        </div>
    </div>
    <div class="col-lg-10 col-md-9">
        <div class="tab-content">
            <div id="general-settings" role="tabpanel" class="tab-pane {if $tillittabvalue == 1}active{/if}">
                {$renderTillitGeneralForm nofilter}
            </div>
            <div id="other-settings" role="tabpanel" class="tab-pane {if $tillittabvalue == 2}active{/if}">
                {$renderTillitOtherForm nofilter}
            </div>
            <div id="order-status-settings" role="tabpanel" class="tab-pane {if $tillittabvalue == 3}active{/if}">
                {$renderTillitOrderStatusForm nofilter}
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
{literal}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#tillit-tabs a').click(function () {
                $('#tillit-tabs a').removeClass('active');
                $(this).addClass('active');
            });
            
            $('#PS_TILLIT_PRODUCT_TYPE option[value="MERCHANT_INVOICE"]').prop('disabled',true);
            $('#PS_TILLIT_PRODUCT_TYPE option[value="ADMINISTERED_INVOICE"]').prop('disabled',true);
        });
    </script>
{/literal}