/**
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
 */

const tillitRequiredField = '<abbr class="required" title="required">*</abbr>'
const tillitSearchLimit = 50

let tillitWithCompanySearch = null
let tillitSearchCache


class Tillit {

    constructor()
    {
        const $body = jQuery(document.body);
        const $checkout = jQuery('.js-address-form');
        
        if ($checkout.length === 0) {
            return;
        }

        Tillit.selectAccountType();

        if (tillit.company_name_search === '1') {

            //const $billingCompany = $checkout.find('select[name="company"]');
            const $billingCompany = $checkout.find('input[name="company"]');

            if ($billingCompany.length) {
                $billingCompany.autocomplete({
                    minLength: 3,
                    minLength: function (event, ui) {

                    },
                    source: function (request, response) {
                        $.ajax({
                            url: tillit.tillit_search_host + '/search?limit=' + tillitSearchLimit + '&offset=0',
                            dataType: "json",
                            delay: 200,
                            data: {
                                q: request.term
                            },
                            success: function (results) {
                                if (results.status == 'success') {
                                    var items = [];
                                    if (results.data.items.length > 0) {
                                        for (let i = 0; i < results.data.items.length; i++) {
                                            var item = results.data.items[i];
                                            items.push({
                                                value: item.name,
                                                label: item.highlight + ' (' + item.id + ')',
                                                company_id: item.id,
                                            })
                                        }
                                    } else {
                                        items.push({
                                            value: '',
                                            label: 'No result found',
                                        })
                                    }
                                    response(items);
                                } else {
                                    alert(results);
                                }
                            },

                        });
                    },
                    select: function (event, ui) {
                        $billingCompany.val(ui.item.value);

                        if (tillit.company_id_search === '1') {
                            $("input[name='companyid']").val(ui.item.company_id);
                        }

                        const addressResponse = jQuery.ajax({
                            dataType: 'json',
                            url: tillit.tillit_checkout_host + '/v1/company/' + ui.item.company_id + '/address'
                        });

                        addressResponse.done(function (response) {
                            if (response.company_location) {

                                const companyLocation = response.company_location;

                                $("input[name='address1']").val(companyLocation.street_address);

                                $("input[name='city']").val(companyLocation.municipality_name);

                                $("input[name='postcode']").val(companyLocation.postal_code);
                            }
                        });
                    }
                }).data("ui-autocomplete")._renderItem = function (ul, item) {
                    return $("<li></li>")
                            .data("item.autocomplete", item)
                            .append("<a>" + item.label + "</a>")
                            .appendTo(ul);
                };
            }
        }
    }

    static selectAccountType()
    {
        const typevalue = $('select[name="account_type"]').val();
        if (!typevalue) {
            $('select[name="account_type"]').val("business");
        }
        Tillit.toggleCompanyFields("business");

        $('select[name="account_type"]').on('change', function () {
            Tillit.toggleCompanyFields(this.value);
        });
    }

    static toggleCompanyFields(value)
    {
        if (value === "business") {
            //Comapny set data
            $("input[name='company']").prop('required', true);
            $("input[name='company']").closest(".form-group").show();
            $("input[name='company']").closest(".form-group").children('.form-control-comment').hide();

            //Comapny set data
            $("input[name='companyid']").prop('required', true);
            $("input[name='companyid']").closest(".form-group").show();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').hide();

        } else {
            //Comapny set data
            $("input[name='company']").prop('required', false);
            $("input[name='company']").closest(".form-group").hide();
            $("input[name='company']").closest(".form-group").children('.form-control-comment').show();

            //Comapny id set data
            $("input[name='companyid']").prop('required', false);
            $("input[name='companyid']").closest(".form-group").hide();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').show();
        }
    }
}


$(document).ready(function () {

    new Tillit()

    if (typeof prestashop !== 'undefined') {
        prestashop.on(
                'updatedAddressForm',
                function () {
                    new Tillit()
                }
        );
        prestashop.on(
                'updateDeliveryForm',
                function () {
                    new Tillit()
                }
        );
    }
});