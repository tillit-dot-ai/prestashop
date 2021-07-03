const tillitRequiredField = '<abbr class="required" title="required">*</abbr>'
const tillitSearchLimit = 50

let tillitWithCompanySearch = null
let tillitSearchCache
let tillitMethodHidden = true
let tillitApproved = null
let tillitCooldown = null

class Tillit {

    constructor()
    {
        const $body = jQuery(document.body);
        const context = this;

        const $checkout = jQuery('.js-address-form');

        if ($checkout.length === 0) {
            return;
        }

        Tillit.selectAccountType();

        if (tillit.company_name_search === '1') {

            const $billingCompany = $checkout.find('select[name="company"]');

            $billingCompany.select2({
                minimumInputLength: 3,
                width: '100%',
                escapeMarkup: function (markup) {
                    return markup
                },
                templateResult: function (data)
                {
                    return data.html
                },
                templateSelection: function (data) {
                    return data.text
                },
                ajax: {
                    dataType: 'json',
                    delay: 200,
                    url: function (params) {
                        params.page = params.page || 1
                        return tillit.tillit_search_host + '/search?limit=' + tillitSearchLimit + '&offset=' + ((params.page - 1) * tillitSearchLimit) + '&q=' + params.term
                    },
                    data: function ()
                    {
                        return {}
                    },
                    processResults: function (response, params)
                    {

                        tillitSearchCache = response

                        return {
                            results: Tillit.extractItems(response),
                            pagination: {
                                more: (params.page * tillitSearchLimit) < response.data.total
                            }
                        }

                    }
                }
            }).on('select2:select', function (e) {

                // Get the option data
                const data = e.params.data

                if (tillit.company_id_search === '1') {

                    // Set the company ID
                    $("input[name='companyid']").val(data.company_id)

                }

                // Fetch the company data
                const addressResponse = jQuery.ajax({
                    dataType: 'json',
                    url: tillit.tillit_checkout_host + '/v1/company/' + $("input[name='companyid']").val() + '/address'
                });

                addressResponse.done(function (response) {

                    // If we have the company location
                    if (response.company_location) {

                        // Get the company location object
                        const companyLocation = response.company_location

                        // Populate the street name and house number fields
                        $("input[name='address1']").val(companyLocation.street_address)

                        // Populate the city
                        $("input[name='city']").val(companyLocation.municipality_name)

                        // Populate the postal code
                        $("input[name='postcode']").val(companyLocation.postal_code)

                    }

                })

            })

        }
    }

    static extractItems(results)
    {

        if (results.status !== 'success')
            return []

        const items = []

        for (let i = 0; i < results.data.items.length; i++) {

            const item = results.data.items[i]

            items.push({
                id: item.name,
                text: item.name,
                html: item.highlight + ' (' + item.id + ')',
                company_id: item.id,
                approved: false
            })

        }

        return items

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
            $("select[name='company']").prop('required', true);
            $("select[name='company']").closest(".form-group").show();
            $("select[name='company']").closest(".form-group").children('.form-control-comment').hide();

            //Comapny set data
            $("input[name='companyid']").prop('required', true);
            $("input[name='companyid']").closest(".form-group").show();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').hide();

            //vat number set data
            //$("input[name='vat_number']").prop('required', true);
            //$("input[name='vat_number']").closest(".form-group").show();
            //$("input[name='vat_number']").closest(".form-group").children('.form-control-comment').hide();

            //dni set here
            //$("input[name='dni']").prop('required', false);
            //$("input[name='dni']").closest(".form-group").children('.form-control-comment').show();
            //$( ".form-control-error" ).remove();
        } else {
            //Comapny set data
            $("select[name='company']").prop('required', false);
            $("select[name='company']").closest(".form-group").hide();
            $("select[name='company']").closest(".form-group").children('.form-control-comment').show();

            //Comapny id set data
            $("input[name='companyid']").prop('required', false);
            $("input[name='companyid']").closest(".form-group").hide();
            $("input[name='companyid']").closest(".form-group").children('.form-control-comment').show();

            //vat number set data
            //$("input[name='vat_number']").prop('required', false);
            //$("input[name='vat_number']").closest(".form-group").hide();
            //$("input[name='vat_number']").closest(".form-group").children('.form-control-comment').show();
            //$( ".form-control-error" ).remove();

            //dni set here
            //$("input[name='dni']").prop('required', true);
            //$("input[name='dni']").closest(".form-group").children('.form-control-comment').hide();
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