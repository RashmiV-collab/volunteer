<fieldset>		
    <?php
    //from woocommerce
    wp_enqueue_script('wc-credit-card-form');
    global $wpem_stripe_split_payment_gateway;
    if ($description)
        echo $description;

    if ($stripe_saved_cards && is_user_logged_in() && $cards)
    {
        ?>
        <p class="form-row form-row-wide">
            <a class="button" style="float:right;" href="<?php echo apply_filters('wc_stripe_manage_saved_cards_url', get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>#saved-cards"><?php _e('Manage cards', 'wp-event-manager-stripe-split-payment'); ?></a>
            <?php
            if ($cards)
            {
                $default_card = $cards[0]->id;
                foreach ((array) $cards as $card)
                {
                    if ('card' !== $card->object)
                        continue;
                    ?>
                    <label for="stripe_card_<?php echo $card->id; ?>" class="brand-<?php echo esc_attr(strtolower($card->brand)); ?>">
                        <input type="radio" id="stripe_card_<?php echo $card->id; ?>" name="stripe_card_id" value="<?php echo $card->id; ?>" <?php checked($default_card, $card->id) ?> />
                        <?php printf(__('%s card ending in %s (Expires %s/%s)', 'wp-event-manager-stripe-split-payment'), (isset($card->type) ? $card->type : $card->brand), $card->last4, $card->exp_month, $card->exp_year); ?>
                    </label>
                    <?php
                }
            }
            ?>
            <label for="new">
                <input type="radio" id="new" name="stripe_card_id"  value="new" />
                <?php _e('Use a new credit card', 'wp-event-manager-stripe-split-payment'); ?>
            </label>
        </p>
    <?php } ?>

    <!-- Please refer stripe checkout data form parameter : https://stripe.com/docs/checkout#integration-simple -->
    <!-- Below div will disabled(hidden) and it will use for passing info to stripe along with popup stripe checkout -->   
    <div id="stripe_new_card" class="stripe_new_card" <?php if ($display === 1) : ?>style="display:none;"<?php endif; ?>
         data-description=""               
         data-email="<?php echo $email; ?>"
         data-amount="<?php echo $amount; ?>"
         data-name="<?php echo $name; ?>"
         data-label="<?php echo $label; ?>"
         data-currency="<?php echo $currency; ?>"
         >
             <?php
             
                 //$WC_Payment_Gateway_CC->form( array( 'fields_have_names' => false ) ); 	                         
                 //https://wordpress.org/support/topic/error-on-checkout-you-have-passed-a-blank-string-for-card/page/2/
                 // https://docs.woocommerce.com/wc-apidocs/class-WC_Payment_Gateway_CC.html

                 $fields         = array();
                 
                $default_fields = array(
                     'card-number-field' => '<p class="form-row form-row-wide">
                                            <label for="' . esc_attr($id) . '-card-number">' . __('Card Number', 'wp-event-manager-stripe-split-payment') . ' <span class="required">*</span></label>
                                            <input id="' . esc_attr($id) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $wpem_stripe_split_payment_gateway->field_name('card-number') . ' />
                                            </p>',
                     'card-expiry-field' => '<p class="form-row form-row-first">
                                            <label for="' . esc_attr($id) . '-card-expiry">' . __('Expiry (MM/YY)', 'wp-event-manager-stripe-split-payment') . ' <span class="required">*</span></label>
                                            <input id="' . esc_attr($id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__('MM / YY', 'wp-event-manager-stripe-split-payment') . '" ' . $wpem_stripe_split_payment_gateway->field_name('card-expiry') . ' />
                                            </p>',
                     'card-cvc-field'    => '<p class="form-row form-row-last">
                                        <label for="' . esc_attr($id) . '-card-cvc">' . __('Card Code', 'wp-event-manager-stripe-split-payment') . ' <span class="required">*</span></label>
                                        <input id="' . esc_attr($id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__('CVC', 'wp-event-manager-stripe-split-payment') . '" ' . $wpem_stripe_split_payment_gateway->field_name('card-cvc') . ' />
                                     </p>',
                 );

                 $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $id));
                 ?>

            <fieldset id="wc-<?php echo esc_attr($id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
                <?php do_action('wpem_stripe_split_credit_card_form_start', $id); ?>
                <?php
                foreach ($fields as $field)
                {
                    echo $field;
                }
                ?>
                <?php do_action('wpem_stripe_split_credit_card_form_end', $id); ?>
                <div class="clear"></div>
            </fieldset>
            <?php
            //show checkbox with lable : Save to Account
            if ($display_tokenization)
            {
                $WC_Payment_Gateway_CC->save_payment_method_checkbox();
            }
        ?>
    </div>
</fieldset>
