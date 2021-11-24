{*
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
 */
*}

<div class="row">
    <div id="tillit-tabs" class="col-lg-2 col-md-3">
        <div class="list-group">
            <a class="list-group-item {if $tillittabvalue == 1}active{/if}" href="#general-settings" aria-controls="general-settings" role="tab" data-toggle="tab">{l s='General Settings' mod='two_payment'}</a>
            <a class="list-group-item {if $tillittabvalue == 2}active{/if}" href="#other-settings" aria-controls="other-settings" role="tab" data-toggle="tab">{l s='Other Settings' mod='two_payment'}</a>
            <a class="list-group-item {if $tillittabvalue == 3}active{/if}" href="#order-status-settings" aria-controls="order-status-settings" role="tab" data-toggle="tab">{l s='Order Status Settings' mod='two_payment'}</a>
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
        });
    </script>
{/literal}