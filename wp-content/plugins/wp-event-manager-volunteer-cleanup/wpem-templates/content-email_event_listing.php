<?php 
global $post;

$post_id = $post->ID;
$title    = $post->post_title;
$type     = get_event_type();
$location = get_event_location();
$organizer = get_organizer_name();
$link     = get_event_permalink();
$start_date = get_event_start_date();
$end_date   = get_event_end_date();
$start_time = get_event_start_time();
$end_time   = get_event_end_time();
$separator = get_wpem_date_time_separator();
$date_format           = WP_Event_Manager_Date_Time::get_event_manager_view_date_format();
$thumbnail  = get_event_thumbnail($post, 'full'); 

$all_tickets = array();
if(function_exists('wpem_sell_tickets_get_event_tickets')){
    $atts =  apply_filters( 'event_manager_output_event_sell_tickets_defaults', array('event_id'  => '','orderby'   => '_ticket_priority','order'     => 'ASC') );
    $all_tickets = wpem_sell_tickets_get_event_tickets( $post->ID, $atts['orderby'], $atts['order'] );
}

/*echo "\n";

// Event title
echo esc_html( $title ) . "\n";

// Location and company
if ( $location ) {
    printf( __( 'Location: %s', 'wp-event-manager-alerts' ) . "\n", esc_html( strip_tags( $location ) ) );
}
if ( $organizer ) {
    printf( __( 'Organizer: %s', 'wp-event-manager-alerts' ) . "\n", esc_html( strip_tags( $organizer ) ) );
}

// Permalink
printf( __( 'View Details: %s', 'wp-event-manager-alerts' ) . "\n", $link  );*/
?>
<tr style="display: flex;border-bottom: 1px solid #f5f5f5; padding: 20px;">
    <td>
        <div class="wpem-event-banner-img" style="background-image: url(<?php echo esc_attr($thumbnail) ?>); height: 90px;
        width: 90px;
        background-size: cover!important;
        background-position: center!important;
        border-radius: 4px;
        background-color:#e4e4e4">
        </div>
    </td>
    <td id="dateHide">
        <div class="wpem-event-information" style="float: left;font-size: 15px;line-height: 20px;width: 100%; padding: 0px 15px;">
            <div class="wpem-event-date" style="width: 80px; font-size: 15px; line-height: 20px;">
                <div class="wpem-event-date-type" style="display: flex;">
                <?php
                    if (!empty($start_date)) { ?>
                    <div class="wpem-from-date" style="width: 40px;">
                        <div class="wpem-date" style="font-size: 29px; line-height: 30px; font-weight: 600; color:#555555"><?php echo  date_i18n('d', strtotime($start_date)); ?></div>
                        <div class="wpem-month" style="font-size: 13px; text-transform: uppercase; font-weight: 400; line-height: 15px;  color: #555555;"><?php echo date_i18n('M', strtotime($start_date)); ?></div>
                    </div>
                    <?php } 
                    if ($start_date != $end_date && !empty($end_date)) {  ?>
                    <div class="wpem-to-date" style="display: flex; float: left; padding-left: 10px;  position: relative; ">
                        <div class="wpem-date-separator" style="position: absolute; left: 0; top: 50%;  transform: translate(0,-50%);  font-size: 20px;  color: #555555">-</div>
                        <div style="padding-left: 10px; padding-top: 2px;">
                            <div class="wpem-date" style="font-size: 15px; line-height: 15px; font-weight: 500; color: #555555"><?php echo  date_i18n('d', strtotime($end_date)); ?></div>
                            <div class="wpem-month" style="font-size: 9px; text-transform: uppercase; font-weight: 400; line-height: 12px; color:#555555"><?php echo  date_i18n('M', strtotime($end_date)); ?></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="clear:both"></div>
    </td>
    <td class="addFlex">
        <table width="100%" cellpadding="0" cellspacing="0" style="box-sizing: border-box; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; margin: 0; padding: 0; width: 100%;">
            <tr>
                <h3 class="wpem-heading-text" style="font-family: Lexend, Sans-serif; font-size: 22px; line-height: 30px; font-weight: 700; color: #111111; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0; text-transform: capitalize;"><a style="color: #111111;" href="<?php echo $link; ?>"> <?php echo $title; ?></a></h3>
            </tr>
            <tr>
                <div class="wpem-event-date-time" style="margin: 5px 0px;  min-height: 22px;  color: #555555;  display: flex;">
                   
                        <img width="20" height="20" src="<?php echo WPEM_VOLUNTEER_URI.'assets/img/clock-icon.png'; ?>" alt="-" /><span class="wpem-event-date-time-text" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;"> <?php display_event_start_date(); ?>
                                <?php
                                if (!empty($start_time)) {
                                    display_date_time_separator();
                                }
                                display_event_start_time(); 
                                if (!empty($end_date) || !empty($end_time)) {
                                ?> - <?php
                                } 
                                if (isset($start_date) && isset($end_date) && $start_date != $end_date) {
                                    display_event_end_date();
                                }
                                if (!empty($end_date) && !empty($end_time)) {
                                    display_date_time_separator();
                                }
                                display_event_end_time(); ?></span>
                </div>
            </tr>
            <tr>
                <div class="wpem-event-location" style="margin: 5px 0px;  min-height: 22px;  color: #555555;  display: flex;">
                    
                    <img width="15" height="20" src="<?php echo WPEM_VOLUNTEER_URI.'assets/img/map-pointer.png'; ?>" alt="-" /><span class="wpem-event-location-text" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;"> <?php
                                if (get_event_location() == 'Online Event' || get_event_location() == '') : 
                                    echo esc_attr('Online Event', 'wp-event-manager');
                                else : 
                                    display_event_location(false);
                                endif; ?> </span>
                </div>
            </tr>
            <tr>
                <div class="wpem-event-ticket-type" style="margin: 5px 0px;  min-height: 22px;  color: #555555;">
                <?php if($all_tickets){ 
                    foreach($all_tickets as $product){
                        $product_id = $product->ID;
                        $product = wc_get_product($product_id);
                        $stock     = (int)get_post_meta($product_id, '_stock', true);
                        $total_sales = get_post_meta($product_id, 'total_sales', true);
                        if(!$total_sales){
                            $total_sales = 0;
                        }
                        $total = $stock + (int) $total_sales;
                        $total = (string)$total;
                        $ticket_type = get_post_meta($product_id, '_ticket_type', true);
                    /*<div class="wpem-ticket-type"><?php printf(__('%s', 'wp-event-manager-sell-tickets'), $post_data->post_title); ?></div>*/
                    ?>
                    <div style="display:block;width: 100%;margin:10px 0px">
                        <span class="wpem-ticket-price" style="font-size: 15px; line-height: 20px; padding-left: 5px; font-family: Roboto, Sans-serif;"><?php echo $ticket_type == 'free' ? __('Free', 'wp-event-manager-sell-tickets') : 'Paid '.$product->get_price_html(); ?></span>

                        <?php if($stock == 0) {
                        ?>
                        <span class="wpem-ticket-quantity wpem-form-group wpem-ticket-sold-out" style="background-color: rgb(255 0 0 / 80%);color: white; padding: 4px 10px;border-radius: 4px; text-align: center;">
                            Sold Out                                
                        </span>
                        <?php
                        }else{ ?>
                        
                        <span class="wpem-event-ticket-type-text" style="background: #f5f5f5 ; color: #111111; padding: 5px 7px; display: inline-block; line-height: 15px;  font-weight: 500; font-size: 14px; border-radius: 4px;margin-left:5px;"><?php echo '<span class="remaining-tickets-counter">';
                           printf(__('( Remaining tickets %s out of %s)', 'wp-event-manager-sell-tickets'),$stock,$total);
                            echo '</span>'; ?></span>
                        <?php } ?>
                    </div>
                <?php } 
                }
                ?>
                </div>
            </tr>
        </table>
    </td>
</tr>


