/**
 * 2021 Tillit
 * @author Tillit
 * @copyright Tillit Team
 * @license Tillit Commercial License
 */

class Tillit {

    constructor()
    {
        const $body = jQuery(document.body);
        const $checkout = jQuery('.js-address-form');
        const id_country = $('select[name="id_country"]').val();

        if ($checkout.length === 0) {
            return;
        }

        Tillit.selectAccountType();
        Tillit.setInternationalPhoneDropDown(id_country);

        if (tillit.company_name_search === '1') {

            const $billingCompany = $checkout.find('input[name="company"]');

            if ($billingCompany.length) {
                $billingCompany.autocomplete({
                    minLength: 3,
                    minLength: function (event, ui) {
                    },
                    source: function (request, response) {
                        $.ajax({
                            url: 'https://' + tillit.countries[id_country] + '.search.tillit.ai/search?limit=50&offset=0',
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
                                                company_id: item.id
                                            })
                                        }
                                    } else {
                                        items.push({
                                            value: '',
                                            label: tillit.search_empty_text
                                        })
                                    }
                                    response(items);
                                } else {
                                    var items = [];
                                    items.push({
                                        value: '',
                                        label: tillit.search_empty_text
                                    })
                                }
                            },

                        });
                    },
                    select: function (event, ui) {
                        $billingCompany.val(ui.item.value);

                        if (tillit.company_id_search === '1') {
                            $("input[name='companyid']").val(ui.item.company_id);
                        }

                        $.ajax({
                            url: tillit.checkout_host + '/v1/' + tillit.countries[id_country] + '/company/' + ui.item.company_id + '/address?client=' + tillit.client + '&client_v=' + tillit.client_version,
                            dataType: "json",
                            success: function (response) {
                                if (response.address) {
                                    $("input[name='address1']").val(response.address.streetAddress);
                                    $("input[name='city']").val(response.address.city);
                                    $("input[name='postcode']").val(response.address.postalCode);
                                }
                            },
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

            $("input[name='company']").prop('required', true);
            $("input[name='company']").closest(".form-group").show();
            $("input[name='company']").closest(".form-group").children('.form-control-comment').hide();

            $("input[name='companyid']").prop('required', true);
            $("input[name='companyid']").closest(".form-group").show();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').hide();

        } else {

            $("input[name='company']").prop('required', false);
            $("input[name='company']").closest(".form-group").hide();
            $("input[name='company']").closest(".form-group").children('.form-control-comment').show();

            $("input[name='companyid']").prop('required', false);
            $("input[name='companyid']").closest(".form-group").hide();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').show();
        }
    }

    static setInternationalPhoneDropDown(id_country)
    {
        const phoneInputField = document.querySelector("input[name='phone']");
        const phoneInput = window.intlTelInput(phoneInputField, {
            preferredCountries: ["no", "gb"],
            utilsScript:
                "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
        });
        $('.iti__selected-flag .iti__flag').removeClass('iti__us');
        $('.iti__selected-flag .iti__flag').addClass(' iti__' + tillit.countries[id_country]);
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