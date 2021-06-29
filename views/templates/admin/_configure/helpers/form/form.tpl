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

{extends file="helpers/form/form.tpl"}
{block name="field"}
    {if $input.type == 'file'}
        <div class="col-lg-9">
            <div class="form-group">
                <div class="col-lg-6">
                    <input id="{$input.name}" type="file" name="{$input.name}" class="hide" />
                    <div class="dummyfile input-group">
                        <span class="input-group-addon"><i class="icon-file"></i></span>
                        <input id="{$input.name}-name" type="text" class="disabled" name="filename" readonly />
                        <span class="input-group-btn">
                            <button id="{$input.name}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
                                <i class="icon-folder-open"></i> {l s='Choose a file' mod='tillit'}
                            </button>
                        </span>
                    </div>
                </div>

            </div>
            <div class="form-group">
                {if isset($fields_value[$input.name]) && $fields_value[$input.name] != ''}
                    <div id="{$input.name}-images-thumbnails" class="col-lg-12">
                        <img src="{$uri}views/img/{$fields_value[$input.name]}" class="img-thumbnail" style="height: 70px;"/>
                        <a class="btn btn-default" href="{$current}&{$identifier}={$form_id|intval}&token={$token}&deleteLogo=1">
                            <i class="icon-trash"></i> {l s='Delete' mod='tillit'}
                        </a>
                    </div>
                {/if}
            </div>
            <script>

                $(document).ready(function () {
                    $('#{$input.name}-selectbutton').click(function (e) {
                        $('#{$input.name}').trigger('click');
                    });
                    $('#{$input.name}').change(function (e) {
                        var val = $(this).val();
                        var file = val.split(/[\\/]/);
                        $('#{$input.name}-name').val(file[file.length - 1]);
                    });
                });
            </script>

            {if isset($input.desc) && !empty($input.desc)}
                <p class="help-block">
                    {$input.desc}
                </p>
            {/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}