<?php
global $product, $woocommerce;
// this value will use to calculate fee and show in the fee column
$event_timezone    = get_event_timezone();
if (empty($event_timezone)) {
    $event_timezone = wp_timezone_string();
}
//check if timezone settings is enabled as each event then set current time stamp according to the timezone
// for eg. if each event selected then Berlin timezone will be different then current site timezone.
if (WP_Event_Manager_Date_Time::get_event_manager_timezone_setting() == 'each_event')
    $current_timestamp = WP_Event_Manager_Date_Time::current_timestamp_from_event_timezone($event_timezone);
else
    $current_timestamp = current_time('timestamp'); // If site wise timezone selected

//view date format to view date in this template
$view_date_format  = WP_Event_Manager_Date_Time::get_event_manager_view_date_format();
$timepicker_format = WP_Event_Manager_Date_Time::get_timepicker_format();
$view_date_format  = $view_date_format . ' ' . $timepicker_format;

$count_fields  = 0; ?>
<div class="wpem-single-event-ticket-information">
    <div class="wpem-event-ticket-info-title">
        <h3 class="wpem-heading-text"><?php _e('Ticket Information', 'wp-event-manager-sell-tickets'); ?></h3>
    </div>
    <form name="event-tickets" method="post" class="wpem-form-wrapper">
        <div class="wpem-ticket-information-wrapper">
            <div class="wpem-ticket-information-body">
                <?php
                //check any ticket is activate or not
                $enable_ticket = false;
                //set default event id
                $event_id      = 0;
                //get all the tickets of perticular event.
                foreach ($product_event_tickets as $post_data) : setup_postdata($post_data);
                    //get all the product meta  
                    $product_id              = $post_data->ID;
                    $product = wc_get_product($product_id);

                    $show_description        = get_post_meta($post_data->ID, '_ticket_show_description', true);
                    $price                   = get_post_meta($post_data->ID, '_price', true);
                    $ticket_sales_start_date = get_post_meta($post_data->ID, '_ticket_sales_start_date', true);
                    $ticket_sales_end_date   = get_post_meta($post_data->ID, '_ticket_sales_end_date', true);
                    $ticket_fee_pay_by       = get_post_meta($product_id, '_ticket_fee_pay_by', true); //ticket_fee_pay_by : ticket_fee_pay_by_organizer or ticket_fee_pay_by_attendee 
                    $price                   = $price == 0 ? __('Free', 'wp-event-manager-sell-tickets') : $price;
                    $stock                   = get_post_meta($post_data->ID, '_stock', true);
                    $stock_status            = get_post_meta($post_data->ID, '_stock_status', true);
                    $min_order               = get_post_meta($post_data->ID, 'minimum_order', true);
                    $max_order               = get_post_meta($post_data->ID, 'maximum_order', true);
                    $show_remaining_tickets  = get_post_meta($post_data->ID, '_show_remaining_tickets', true);
                    $ticket_type             = get_post_meta($post_data->ID, '_ticket_type', true);
                    $sold_individually             = get_post_meta($post_data->ID, '_sold_individually', true);
                    $event_id                = get_post_meta($post_data->ID, '_event_id', true);

                    //check registration is closed or not
                    $registration_closed = false;
                    $registration_end_date = get_event_registration_end_date($event_id);
                    $registration_end_date = !empty($registration_end_date) ? $registration_end_date . ' 23:59:59' : '';
                    $event_timezone          = get_event_timezone();

                    // check if timezone settings is enabled as each event then set current time stamp according to the timezone
                    // for eg. if each event selected then Berlin timezone will be different then current site timezone.
                    if (WP_Event_Manager_Date_Time::get_event_manager_timezone_setting() == 'each_event') {
                        $current_timestamp = WP_Event_Manager_Date_Time::current_timestamp_from_event_timezone($event_timezone);
                    } else {
                        $current_timestamp = strtotime(current_time('Y-m-d H:i:s'));
                    }

                    if (!empty($registration_end_date) && strtotime($registration_end_date) < $current_timestamp || !attendees_can_apply()) {
                        $registration_closed = true;
                    }
                    $event_timezone    = get_event_timezone($event_id);

                    //check if timezone settings is enabled as each event then set current time stamp according to the timezone
                    // for eg. if each event selected then Berlin timezone will be different then current site timezone.
                    if (WP_Event_Manager_Date_Time::get_event_manager_timezone_setting() == 'each_event')
                        $current_timestamp = WP_Event_Manager_Date_Time::current_timestamp_from_event_timezone($event_timezone);
                    else
                        $current_timestamp = current_time('timestamp'); // If site wise timezone selected
                    ?>
                    <div class="wpem-ticket-info-wrap">
                        <?php do_action('wpem_sell_tickets_ticket_loop_content_start', $product); ?>
                        <div class="wpem-ticket-info-flex">
                            <div class="wpem-ticket-type-and-price">
                                <div class="wpem-ticket-type"><?php printf(__('%s', 'wp-event-manager-sell-tickets'), $post_data->post_title); ?></div>

                                <div class="wpem-ticket-price">
                                    <?php echo $ticket_type == 'free' ? __('Free', 'wp-event-manager-sell-tickets') : $product->get_price_html(); ?>
                                </div>
                                <div class="wpem-ticket-start-end-date">
                                    <?php
                                    if ($current_timestamp < strtotime($ticket_sales_start_date)) { ?>
                                        <span><?php _e('Start:', 'wp-event-manager-sell-tickets'); ?> </span><?php echo date_i18n($view_date_format, strtotime($ticket_sales_start_date)); ?>
                                        <?php
                                    } else {
                                        if ($current_timestamp > strtotime($ticket_sales_end_date)) : ?>
                                            <span><?php _e('Sales Ended:', 'wp-event-manager-sell-tickets'); ?> </span><?php echo date_i18n($view_date_format, strtotime($ticket_sales_end_date)); ?>
                                    <?php endif;
                                    }  ?>
                                </div>
                            </div>
                            <?php 
                                $enable_ticket = true;
                                if($is_user_registered == true || $registration_closed == true){
                                    $disabled = "disabled=disabled";
                                    $class = 'wpem-tooltip wpem-tooltip-bottom';
                                    $tooltip = '';
                                } else{
                                     $disabled = "";
                                     $class = '';
                                     $tooltip = 'style="display:none"';
                                } ?>
                            <div class="wpem-ticket-quantity wpem-form-group <?php echo ($stock < 1) ? 'wpem-ticket-sold-out' : ''; echo $class;?>">
                                <?php
                                if ($stock < 1) {
                                    echo __('Sold Out', 'wp-event-manager-sell-tickets');
                                } else {
                                    $total_sales = get_post_meta($post_data->ID, 'total_sales', true);
                                    if(!$total_sales){
                                        $total_sales = 0;
                                    }
                                    $total = $stock + (int) $total_sales;
                                    $total = (string)$total;
                                    if ($ticket_type == 'donation' && $current_timestamp < strtotime($ticket_sales_end_date)) { ?>
                                        <input <?php echo $disabled;?>  class="price-donation" type="number" name="donation_price-<?php echo esc_attr($count_fields);?>" id="donation_price-<?php echo esc_attr($count_fields);?>" value="<?php echo esc_attr($price);?>"  min="<?php echo esc_attr($price);?>" />
                                        <input <?php echo $disabled;?>  class="donation-value" type="hidden" name="ticket_quantity" id="quantity-<?php echo esc_attr($count_fields);?>" value="1" />

                                        <?php if (isset($show_remaining_tickets) && $show_remaining_tickets == 'yes') {
                                            echo '<span class="remaining-tickets-counter">';
                                            printf(__('( Remaining tickets %s out of %s)', 'wp-event-manager-sell-tickets'),$stock,$total);
                                            echo '</span>';
                                        }
                                    } else {
                                        $total_sales = get_post_meta($post_data->ID, 'total_sales', true);
                                        if(!$total_sales){
                                            $total_sales = 0;
                                        }
                                        $total = $stock + (int) $total_sales;
                                        $total = (string)$total;
                                        if (!empty($ticket_sales_start_date) && $current_timestamp > strtotime($ticket_sales_start_date) && $current_timestamp < strtotime($ticket_sales_end_date)) { ?>
                                            <select <?php echo $disabled.' '.$class;?> name="ticket_quantity" id="quantity-<?php echo $count_fields; ?>">
                                                <option value="0">0</option>
                                                <?php

                                                //if minimum and maximum order quantity not set
                                                $min_order     = empty($min_order) || $min_order > $max_order ? 1 : $min_order;
                                                $max_order     = empty($max_order) || $max_order < $min_order ? 20 : $max_order;

                                                if ($sold_individually === true || $sold_individually == 1 || $sold_individually == 'yes') $max_order = 1;
                                                for ($quantity = $min_order; $quantity <= $max_order; $quantity++) :
                                                    if ($quantity <= $stock) :?>
                                                        <option value="<?php echo $quantity; ?>"><?php _e($quantity, 'wp-event-manager-sell-tickets'); ?></option>
                                                <?php
                                                    endif;
                                                endfor; ?>
                                            </select>
                                            <?php if (isset($show_remaining_tickets) && $show_remaining_tickets == 'yes') {
                                                echo '<span class="remaining-tickets-counter">';
                                                printf(__('( Remaining tickets %s out of %s)', 'wp-event-manager-sell-tickets'),$stock,$total);
                                                echo '</span>';
                                            }
                                        } else {
                                            echo ' - ';
                                        }
                                    }
                                } //if stock available end
                                ?>
                                <span class="wpem-tooltiptext" <?php echo $tooltip ;?>>
                                    <?php printf(__('Already registered for this event.', 'wp-event-manager-sell-tickets'));?>
                                </span>
                            </div>
                        </div>
                        <?php if ($show_description == 'yes') : ?>
                            <div class="wpem-ticket-description">
                                <?php echo get_post_field('post_content', $product_id); ?>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="" id="product-<?php echo $count_fields; ?>" value="<?php echo $post_data->ID; ?>">

                        <?php do_action('wpem_sell_tickets_ticket_loop_content_end', $product); ?>
                    </div>
                <?php
                    $count_fields++;
                endforeach;
                wp_reset_query(); ?>
            </div>
            <?php
            if ($enable_ticket == true && 'expired' !== get_post_status($event_id) && $is_user_registered == false && $registration_closed == false) : ?>
                <div class="wpem-ticket-information-fotoer">
                    <div id="sell-ticket-status-message"></div>
                    <div class="wpem-ticket-register-button">
                        <input type="hidden" name="" id="total_ticket" value="<?php echo $count_fields; ?>">
                        <button type="submit" class="wpem-theme-button" name="order_now" value="<?php _e('Order Now', 'wp-event-manager-sell-tickets'); ?>" id="order_now"><?php _e('Order Now', 'wp-event-manager-sell-tickets'); ?></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>