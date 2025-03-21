<?php
/**
 * This is registration form will show at checkout page when user will buy ticket.
 * This registration form will show below Additional Information : Order Notes field at checkout page.
 * 
 * if registration fields not found or not activated registration addon, then it will return and not allow to further proceed.
 *
 */
global $post, $woocommerce;

if (empty($registration_fields)) {
    echo __('There is no any field in registration form', 'wp-event-manager-sell-tickets');
} else {
    //If wp-event-manager-attendee-information plugin is active then it will get the the cart contents count of the attendee information fields to show on checkout pgae.
    if (function_exists('get_display_count_of_attendee_forms')) {
        $cart_contents = get_display_count_of_attendee_forms();
    } else {
        $cart_contents = $woocommerce->cart->get_cart_contents_count();
    }

    // title for registration form at checkout page
    echo "<h3>" . __('Registration', 'wp-event-manager-sell-tickets') . "</h3>";
    $cart_item['quantity'] = $woocommerce->cart->get_cart_contents_count();
    $ticket_price          = WC()->cart->cart_contents_total;

    $i = 1;
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item){
        
        $product_id = $cart_item['product_id'];
        $event_id = get_post_meta ( $product_id, '_event_id', true );
        $_product   = apply_filters('sell_tickets_woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
        if(isset($event_id) && !empty($event_id)){
            
            if($cart_contents > 0){
                $j = 1;
                for ($i; $j <= $cart_item['quantity']; $i++) {
                    $j++;
                
                    if ($cart_contents > 1){
                        echo "<h3>" . __('Ticket', 'wp-event-manager-sell-tickets') . " " . $i . " : " . $_product->get_title() . "</h3>";
                        $ticket_price = get_post_meta($product_id, '_price', true);
                    }
                    
                    $ticket_price = get_post_meta($product_id, '_price', true);
                    $ticket_type = get_post_meta($product_id, '_ticket_type', true); ?>

                    <input type="hidden" name="ticket_id_<?php echo $i; ?>" value="<?php echo $product_id; ?>"/>
                    <input type="hidden" name="ticket_type_<?php echo $i; ?>" value="<?php echo $ticket_type; ?>"/>
                    <input type="hidden" name="total_ticket_price_<?php echo $i; ?>" value="<?php echo $ticket_price; ?>"/>
                    <input type="hidden" name="event_id" value="<?php echo absint($event_id); ?>" />
                    <?php
                    /*
                    * This function will gives loop of all the tickets which is in the cart.
                    * Thi will add fields at the checkout page
                    *
                    */
                    add_registration_fields_to_form($i);
                    ?>
                    <?php 
                    $waiver = get_post_meta($event_id,'_waiver',true);
                    if($waiver && $waiver !='nowaiver'){
                        $waiver_desc = $hidden_waiver = '';
                        switch($waiver){
                            case 'standard' : 
                                $waiver_desc = $hidden_waiver = (get_option('event_manager_event_standard_waiver')?get_option('event_manager_event_standard_waiver'):'');
                                break;
                            case 'custom' : 
                                $waiver_desc = $hidden_waiver = (get_post_meta($event_id,'_custom_waiver',true)?get_post_meta($event_id,'_custom_waiver',true):'');
                                break;    
                            case 'external' : 
                                $waiver_desc = (get_post_meta($event_id,'_external_waiver',true)?'<a href="'.trim(get_post_meta($event_id,'_external_waiver',true)).'" target="_blank">View Waiver</a>':'');
                                break;
                                $hidden_waiver = (get_post_meta($event_id,'_external_waiver',true)?get_post_meta($event_id,'_external_waiver',true):'');
                        }
                    ?>
                    <p class="form-row">
                        <?php echo $waiver_desc ?>
                    </p>
                    <p class="form-row validate-required" id="wavier-<?php echo $i; ?>_field" data-priority="7"><span class="woocommerce-input-wrapper"><label class="checkbox " required="required" aria-describedby="wavier-<?php echo $i; ?>-description">
                        <input type="hidden" value="<?php echo $hidden_waiver ?>" name="waiver_desc_<?php echo $i; ?>"/>
                        <input type="checkbox" class="input-checkbox " name="waiver_<?php echo $i; ?>" id="waiver_<?php echo $i; ?>" value="<?php echo $waiver ?>"> <strong>Waiver of Acceptance.</strong> Guarantor hereby waives any acceptance of this Agreement by Additional User.&nbsp;<abbr class="required" title="required">*</abbr></label>
                    </p>
                    <?php
                    }
                    if ($show_submit_registration_button == true) : ?>
                        <input type="submit" name="gam_event_manager_send_registration" value="<?php esc_attr_e('Send registration', 'wp-event-manager-sell-tickets'); ?>" />
                    <?php endif; 
                    //if attendee type is Buyer only
                    if ($cart_contents == 1)
                        break;
                }
            }
            if ($cart_contents == 1)
                break;
        }
    }
}
?>