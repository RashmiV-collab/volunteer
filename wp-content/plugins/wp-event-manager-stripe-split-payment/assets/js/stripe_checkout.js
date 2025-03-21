jQuery(function () {

    var stripe_submit = false;

    // WooCommerce lets us return a false on checkout_place_order_{gateway} to keep the form from submitting
    jQuery('form.checkout').on('checkout_place_order_stripe', function () {
        if (stripe_submit) {
            stripe_submit = false;
            return true;
        }

        //check stripe method selected or not
        if (!jQuery('#payment_method_stripe').is(':checked')) {
            return true;
        }
        
        //check user' using previously stored card exist or not
        if (jQuery('input[name=stripe_card_id]').length > 0 && jQuery('input[name=stripe_card_id]:checked').val() != 'new') {
            return true;
        }

        //if terms and condition enabled and then user has checked or not.
        // Don't open modal if required fields are not complete
        if (jQuery('input#terms').size() === 1 && jQuery('input#terms:checked').size() === 0) {
            alert(wc_stripe_params.i18n_terms);
            return false;
        }

        // check to see if we need to validate shipping address
        $required_inputs = jQuery('.woocommerce-billing-fields .address-field.validate-required');

        if ($required_inputs.size()) {
            var required_error = false;
            $required_inputs.each(function () {
                if (jQuery(this).find('input.input-text').val() === '') {
                    required_error = true;
                }
            });
            if (required_error) {
                alert(wc_stripe_params.i18n_required_fields);
                return false;
            }
        }

        // Capture submittal and open stripecheckout
        var $form = jQuery("form.checkout, form#order_review, form#add_payment_method");
        var $stripe_payment_data = jQuery('#stripe_new_card');
        var token = $form.find('input.stripe_token');

        token.val('');

        var token_action = function (res) {
            $form.find('input.stripe_token').remove();
            $form.append("<input type='hidden' class='stripe_token' name='stripe_token' value='" + res.id + "'/>");
            stripe_submit = true;
            $form.submit();
        };

     
        //The following extra data will pass to stripe along during checkout process.
        //The following data effect will apply before show checkout popup dialog of stripe.It means it will  pass this data to stripe js (https://checkout.stripe.com/v2/checkout.js)
        // and return dialog according to passed settings data like bitcoin show or not etc.
        StripeCheckout.open({
            key: wc_stripe_params.key,
            billingAddress: false,
            amount: $stripe_payment_data.data('amount'),
            name: $stripe_payment_data.data('name'),
            description: $stripe_payment_data.data('description'),
            panelLabel: $stripe_payment_data.data('label'),
            currency: $stripe_payment_data.data('currency'),
            image: $stripe_payment_data.data('image'),
            bitcoin: $stripe_payment_data.data('bitcoin'),           
            locale: $stripe_payment_data.data('locale'),
            email: jQuery('#billing_email').val() || $stripe_payment_data.data('email'),
            token: token_action
        });

        return false;
    });
});