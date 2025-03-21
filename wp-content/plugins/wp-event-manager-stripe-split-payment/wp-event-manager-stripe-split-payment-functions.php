<?php

/**
 * Get fees which pay by organizer
 * @param WC_Order $order
 * @param array $fees_array
 */
function get_fees_pay_by_organizer_from_order($order)
{
    $fixed_fee_value              = 0;
    $percentage_fee_value         = 0;
    $fees_array                   = array();
    $total_fee                    = 0;
    $fees_array['total_fee']      = $total_fee;
    $fees_array['post_author_id'] = '';
    $items                        = $order->get_items();

    if (empty($items))
        return $fees_array;

    $total_fees = 0;
    foreach ($items as $item)
    {
        $product_id                   = $item['product_id'];
        $ticket_type                  = get_post_meta($product_id, '_ticket_type', true);
        $fees_array['post_author_id'] = get_post_field('post_author', $product_id);

        if ($ticket_type == 'free')
            continue;

        $price                        = get_post_meta($product_id, '_price', true);
        $event_id                     = get_post_meta($product_id, '_event_id', true);
        $ticket_fee_pay_by            = get_post_meta($product_id, '_ticket_fee_pay_by', true); //ticket_fee_pay_by : ticket_fee_pay_by_organizer or ticket_fee_pay_by_attendee 
        $fee_settings                 = get_option('fee_settings_rules',get_default_fee_settings() );
        $country_code                 = get_event_host_country_code($event_id);
        $fixed_fee_value              = 0;
        $percentage_fee_value         = 0;
        $total_fee_value              = 0;

        //check array is multidimentional or not
        if (count($fee_settings) != count($fee_settings, COUNT_RECURSIVE))
        {
            foreach ($fee_settings as $key => $value)
            {
                if ($ticket_fee_pay_by == 'ticket_fee_pay_by_organizer')
                {
                    if ($value['fee_mode'] == 'fee_per_ticket')
                    {
                        if ($value['fee_type'] == 'fixed_fee')
                        {
                            $fixed_fee_value += $value['fee_value'];
                        }
                        elseif ($value['fee_type'] == 'fee_in_percentage')
                        {
                            //$percentage_fee_value += $price * ($value['fee_value'] * $item['qty'] / 100);
                            $percentage_fee_value += ($price * $value['fee_value']) / 100;
                        }
                    }
                    elseif ($value['fee_mode'] == 'fee_per_order')
                    {
                        //do the stuff for per order
                    }
                }

                $total_fee_value = $fixed_fee_value + $percentage_fee_value;

                //check if total fee value is not greater than maximum fee value.
                if (isset($value['maximum_fee']) && $total_fee_value >= $value['maximum_fee'])
                    $total_fee_value = $value['maximum_fee'];

            } //end of fee loop

            $total_fee_value = $total_fee_value * $item['qty'];
            $total_fees      = $total_fees + $total_fee_value;

        } //end of if is multidimentional    

    } //end of cart items loop

    $fees_array['total_fee'] = $total_fees;
    
    return $fees_array;
}
