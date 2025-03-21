<?php
global $post;
$start_date = get_event_start_date();
$start_time = get_event_start_time();
$end_date   = get_event_end_date();
$end_time   = get_event_end_time();
$event_type = get_event_type();
if (is_array($event_type) && isset($event_type[0]))
    $event_type = $event_type[0]->slug;

$thumbnail  = get_event_thumbnail($post, 'full'); 

$all_tickets = array();
if(function_exists('wpem_sell_tickets_get_event_tickets')){
    $atts =  apply_filters( 'event_manager_output_event_sell_tickets_defaults', array('event_id'  => '','orderby'   => '_ticket_priority','order'     => 'ASC') );
    $all_tickets = wpem_sell_tickets_get_event_tickets( $post->ID, $atts['orderby'], $atts['order'] );
}

?>

<div class="wpem-event-box-col wpem-col wpem-col-12 wpem-col-md-6 wpem-col-lg-<?php echo esc_attr(apply_filters('event_manager_event_wpem_column', '4')); ?>">
    <!----- wpem-col-lg-4 value can be change by admin settings ------->
    <div class="wpem-event-layout-wrapper">
        <div <?php event_listing_class(''); ?>>
            <a href="<?php display_event_permalink(); ?>" class="wpem-event-action-url event-style-color <?php echo esc_attr($event_type); ?>">
                <div class="wpem-event-banner">
                    <div class="wpem-event-banner-img" style="background-image: url(<?php echo esc_attr($thumbnail) ?>)">
                        <!-- Hide in list View // Show in Box View -->
                        <?php do_action('event_already_registered_title'); ?>
                        <div class="wpem-event-date">
                            <div class="wpem-event-date-type">
                                <?php
                                if (!empty($start_date)) { ?>
                                    <div class="wpem-from-date">
                                        <div class="wpem-date"><?php echo  date_i18n('d', strtotime($start_date)); ?></div>
                                        <div class="wpem-month"><?php echo date_i18n('M', strtotime($start_date)); ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <!-- Hide in list View // Show in Box View -->
                    </div>
                </div>

                <div class="wpem-event-infomation">
                    <div class="wpem-event-date">
                        <div class="wpem-event-date-type">
                            <?php
                            if (!empty($start_date)) { ?>
                                <div class="wpem-from-date">
                                    <div class="wpem-date"><?php echo  date_i18n('d', strtotime($start_date)); ?></div>
                                    <div class="wpem-month"><?php echo  date_i18n('M', strtotime($start_date)); ?></div>
                                </div>
                            <?php } 
                            
                            if ($start_date != $end_date && !empty($end_date)) {  ?>
                                <div class="wpem-to-date">
                                    <div class="wpem-date-separator">-</div>
                                    <div class="wpem-date"><?php echo  date_i18n('d', strtotime($end_date)); ?></div>
                                    <div class="wpem-month"><?php echo date_i18n('M', strtotime($end_date)); ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="wpem-event-details">
                        <?php do_action('wpem_event_listing_event_detail_start', $post->ID); ?>
                        <div class="wpem-event-title">
                            <h3 class="wpem-heading-text"><?php echo esc_html(get_the_title()); ?></h3>
                        </div>

                        <div class="wpem-event-date-time">
                            <span class="wpem-event-date-time-text">
                                <?php display_event_start_date(); ?>
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
                                display_event_end_time(); ?>
                            </span>
                        </div>
                        <div class="wpem-event-location">
                            <span class="wpem-event-location-text">
                                <?php
                                if (get_event_location() == 'Online Event' || get_event_location() == '') : 
                                    echo esc_attr('Online Event', 'wp-event-manager');
                                else : 
                                    display_event_location(false);
                                endif; ?>
                            </span>
                        </div>
                        <?php
                        if (get_option('event_manager_enable_event_types') && get_event_type()) { ?>
                            <div class="wpem-event-type"><?php display_event_type(); ?></div>
                        <?php } 
                        do_action('event_already_registered_title'); ?>

                        <!-- Show in list View // Hide in Box View -->
                        <?php
                        if (get_event_ticket_option()) { ?>
                            <div class="wpem-event-ticket-type">
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
                                    
                                    <div>
                                        <span class="wpem-ticket-price"><?php echo $ticket_type == 'free' ? __('Free', 'wp-event-manager-sell-tickets') : 'Paid '.$product->get_price_html(); ?></span>
                                        <?php if($stock == 0) {
                                        ?>
                                        <span class="wpem-ticket-quantity wpem-form-group wpem-ticket-sold-out" style="background-color: rgb(255 0 0 / 80%);color: white; padding: 4px 10px;border-radius: 4px; text-align: center;">
                                            Sold Out                                
                                        </span>
                                        <?php
                                        }else{ ?>
                                        <span class="wpem-event-ticket-type-text"><?php echo '<span class="remaining-tickets-counter">';
                                            printf(__('( Remaining tickets %s out of %s)', 'wp-event-manager-sell-tickets'),$stock,$total);
                                            echo '</span>'; ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } 
                                }
                                ?>
                                
                            </div>
                           <?php /* <div class="wpem-event-ticket-type <?php echo display_event_ticket_option(); ?>" class="wpem-event-ticket-type-text">
                                <span class="wpem-event-ticket-type-text"><?php display_event_ticket_option(); ?></span>
                            </div> */?>
                        <?php } ?>
                        <!-- Show in list View // Hide in Box View -->
                        <?php do_action('wpem_event_listing_event_detail_end', $post->ID); ?>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>