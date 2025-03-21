<?php 
/**
 * Get event organizer attendee fields for sell ticket at checkout page to gather with woocommerce fields
 * Return selected attendee information type and selected information form fields.
 * Registration fields which is selected by the organizer
 * @return $registration_fields
 */
function get_event_organizer_attendee_fields($event_id=''){
    
    //get all registration fields of registration forms(Event Registraions Addon) which defined by admin 
    $all_registration_fields = get_event_registration_form_fields();   
    
    if(empty($event_id))
        return $all_registration_fields;
       
    //get the selected attendee information fields from the event
    $attendee_info_collect = apply_filters('attendee_information_fields', get_post_meta($event_id, '_attendee_information_fields', true));     
    
    //if user deleted registration fields from the registartion form for event 
    //OR Event submitted before installed attendee information addon so event do not have fields.
    if(empty($attendee_info_collect))
        return $all_registration_fields;     
    $registration_fields = array();
    //collect organizer selected fields from the registration fields     
    foreach($all_registration_fields as $field => $value_array){ 
        foreach($attendee_info_collect as $fields_to_collect){
            if($field == $fields_to_collect)
                $registration_fields[$field] = $value_array;
        }   
    }  

    return $registration_fields;
}

/**
 * This function will give the total number of tickets fields  to show on the checkout page based on selected Buyer Only or Each attendees info.
 * if selected option is buyer only then show/display attendee information just once time.
 * if selected option is each attendee then show/display attendee information for each attendee.
 * It will get the attendee information type and give the appropriate number of registration fields.
 * $woocommerce->cart->get_cart_contents_count() : Get number of items in the cart.
 * @return $cart_contents 
 */
function get_display_count_of_attendee_information_fields(){
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    foreach($items as $item => $values) {
       $product_id = $values['product_id'];
    }
    $event_id = get_post_meta($product_id, '_event_id', true);
    $attendee_information_type = get_post_meta($event_id, '_attendee_information_type', true);
    $cart_contents = $attendee_information_type == 'each_attendee' ? $woocommerce->cart->get_cart_contents_count() : 1 ;
 
    return $cart_contents; 
}

/**
 * This function will give the total number of tickets fields  to show on the checkout page based on selected Buyer Only or Each attendees info.
 * if selected option is buyer only then show/display attendee information just once time.
 * if selected option is each attendee then show/display attendee information for each attendee.
 * It will get the attendee information type and give the appropriate number of registration fields.
 * $woocommerce->cart->get_cart_contents_count() : Get number of items in the cart.
 * @return $cart_contents 
 */
function get_display_count_of_attendee_forms(){
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $cart_contents =  0;
    $simple_product =  0;
    foreach($items as $item => $values) {
        $product_id = $values['product_id'];
        $event_id = get_post_meta($product_id, '_event_id', true);
        if(isset($event_id) && !empty($event_id)){
            $attendee_information_type = get_post_meta($event_id, '_attendee_information_type', true);
            if($attendee_information_type == 'each_attendee'){
                $cart_contents = $woocommerce->cart->get_cart_contents_count();
            } else
                $cart_contents = 1;
        }else{
            $simple_product += $values['quantity'];
        }
    }
    if($cart_contents > 1)
        $cart_contents = $cart_contents - $simple_product;
    return $cart_contents; 
} ?>
