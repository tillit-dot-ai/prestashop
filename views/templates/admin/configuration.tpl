{*
 * @author Plugin Developer from Two <jgang@two.inc> <support@two.inc>
 * @copyright Since 2021 Two Team
 * @license Two Commercial License
*}

<div class="row">
    <div id="two-tabs" class="col-lg-2 col-md-3">
        <div class="list-group">
            <a class="list-group-item {if $twotabvalue == 1}active{/if}" href="#general-settings" aria-controls="general-settings" role="tab" data-toggle="tab">{l s='General Settings' mod='twopayment'}</a>
            <a class="list-group-item {if $twotabvalue == 2}active{/if}" href="#other-settings" aria-controls="other-settings" role="tab" data-toggle="tab">{l s='Other Settings' mod='twopayment'}</a>
            <a class="list-group-item {if $twotabvalue == 3}active{/if}" href="#order-status-settings" aria-controls="order-status-settings" role="tab" data-toggle="tab">{l s='Order Status Settings' mod='twopayment'}</a>
            <a class="list-group-item {if $twotabvalue == 4}active{/if}" href="#information" aria-controls="information" role="tab" data-toggle="tab">{l s='Information' mod='twopayment'}</a>
        </div>
    </div>
    <div class="col-lg-10 col-md-9">
        <div class="tab-content">
            <div id="general-settings" role="tabpanel" class="tab-pane {if $twotabvalue == 1}active{/if}">
                {$renderTwoGeneralForm nofilter}
            </div>
            <div id="other-settings" role="tabpanel" class="tab-pane {if $twotabvalue == 2}active{/if}">
                {$renderTwoOtherForm nofilter}
            </div>
            <div id="order-status-settings" role="tabpanel" class="tab-pane {if $twotabvalue == 3}active{/if}">
                {$renderTwoOrderStatusForm nofilter}
            </div>
            <div id="information" role="tabpanel" class="tab-pane {if $twotabvalue == 4}active{/if}">
                <div class="panel" id="fieldset_22_1">                            
                    <div class="panel-heading"><i class="icon icon-tags"></i> Information</div>
                    <ul class="nav-pills nav-stacked">
                        <li>Version: 1.2.1</li>
                        <li><a href="https://docs.two.inc/developer-portal/plugins/prestashop" target="_blank"> User Guide</a></li>
                        <li><a href="https://www.two.inc/faq" target="_blank"> FAQs</a></li>
                        <li><a href="https://github.com/tillit-dot-ai/prestashop" target="_blank"> Changelog</a></li>
                        <li><a href="https://github.com/tillit-dot-ai/prestashop/releases" target="_blank"> Check Latest Version</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
{literal}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#two-tabs a').click(function () {
                $('#two-tabs a').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
{/literal}