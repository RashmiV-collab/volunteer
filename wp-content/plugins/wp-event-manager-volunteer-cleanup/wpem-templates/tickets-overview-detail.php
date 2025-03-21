<?php 
$all_new_tickets = array();
$total_sales = 0;
$total_free_tickets_sales =0;
$total_donation_tickets_sales =0;
$total_paid_tickets_sales = 0;  

if($all_tickets){
    foreach ($all_tickets as $post_data) : 
        setup_postdata($post_data);
        $product_id  = $post_data->ID;	
        $event_id = get_post_meta($product_id, '_event_id', true);
        $event = get_post($event_id);
        if($event){
            $all_new_tickets[] = $post_data;
            $total_sales += intval(get_post_meta($product_id, 'total_sales', true)); 
            $ticket_type = get_post_meta($product_id, '_ticket_type', true);
            if($ticket_type == 'paid'){
                $total_paid_tickets_sales += intval(get_post_meta($product_id, 'total_sales', true));     
            }elseif($ticket_type == 'free'){
                $total_free_tickets_sales += intval(get_post_meta($product_id, 'total_sales', true));   
            }else{
                $total_donation_tickets_sales += intval(get_post_meta($product_id, 'total_sales', true));   
            }		
        }
    endforeach;  
    wp_reset_postdata();

    $all_tickets = $all_new_tickets;
}
?>


<div class="wpem-regi-block-wrapper">
     <h3 class="wpem-heading-text wpem-sell-ticket-dashboard-heading"><?php _e('Tickets Details', 'wp-event-manager-sell-tickets'); ?></h3>
    <div class="wpem-regi-block-wrap">

        <div class="wpem-regi-info-blocks">
            <div class="wpem-regi-info-block-icon">
                <i class="wpem-icon-ticket wpem-regi-icon-sold-tkt"></i>
            </div>
            <div class="wpem-regi-info-block-info">
                <div class="wpem-regi-info-block-title"><?php _e($total_sales, 'wp-event-manager-sell-tickets'); ?></div>
                <div class="wpem-regi-info-block-desc"><?php _e('Total Sold Tickets', 'wp-event-manager-sell-tickets'); ?></div>
            </div>
        </div>
        <div class="wpem-regi-info-blocks">
            <div class="wpem-regi-info-block-icon">
                <i class="wpem-icon-coin-dollar wpem-regi-icon-paid-tkt"></i>
            </div>
            <div class="wpem-regi-info-block-info">
                <div class="wpem-regi-info-block-title"><?php _e($total_paid_tickets_sales, 'wp-event-manager-sell-tickets'); ?></div>
                <div class="wpem-regi-info-block-desc"><?php _e('Paid Tickets', 'wp-event-manager-sell-tickets'); ?></div>
            </div>
        </div>
        <div class="wpem-regi-info-blocks">
            <div class="wpem-regi-info-block-icon">
                <i class="wpem-icon-gift wpem-regi-icon-free-tkt"></i>
            </div>
            <div class="wpem-regi-info-block-info">
                <div class="wpem-regi-info-block-title "><?php _e($total_free_tickets_sales, 'wp-event-manager-sell-tickets'); ?></div>
                <div class="wpem-regi-info-block-desc"><?php _e('Free Tickets', 'wp-event-manager-sell-tickets'); ?></div>
            </div>
        </div>
        <div class="wpem-regi-info-blocks">
            <div class="wpem-regi-info-block-icon">
                <i class="wpem-icon-gift wpem-regi-icon-free-tkt"></i>
            </div>
            <div class="wpem-regi-info-block-info">
                <div class="wpem-regi-info-block-title "><?php _e($total_donation_tickets_sales, 'wp-event-manager-sell-tickets'); ?></div>
                <div class="wpem-regi-info-block-desc"><?php _e('Donation Tickets', 'wp-event-manager-sell-tickets'); ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($show_remaining_tickets == true && !empty($all_tickets)) : ?>
    <div class="wpem-sell-ticket-block-wrapper">
        <h3 class="wpem-heading-text"><?php _e('All Tickets', 'wp-event-manager-sell-tickets'); ?></h3>
        <div class="wpem-sell-ticket-block-wrap">

            <?php foreach ($all_tickets as $post_data) : setup_postdata($post_data);
                $units_sold = get_post_meta($post_data->ID, 'total_sales', true);
                $units_sold = $units_sold == 0 ? '' : $units_sold;
                $stock = get_post_meta($post_data->ID, '_stock', true);
                $ticket_tile = "<a href='" . get_edit_post_link($post_data->ID) . "'>" . $post_data->post_title . "</a> : "; ?>

                <div class="wpem-sell-ticket-block">
                    <div class="wpem-sell-ticket-name">
                        <h3><?php printf(__('%s', 'wp-event-manager-sell-tickets'), $ticket_tile); ?></h3>
                    </div>
                    <div class="wpem-sell-ticket-sold">
                        <?php printf(__('<b>Ticket Sold</b> - %s ( Remaining tickets %s )', 'wp-event-manager-sell-tickets'), $units_sold, $stock); ?>
                    </div>
                    <div class="wpem-sell-ticket-fee-type">
                        <b><?php _e('Fee Type : ', 'wp-event-manager-sell-tickets'); ?></b> -
                        <?php
                        if (empty($ticket_fee_pay_by) || $ticket_fee_pay_by == 'ticket_fee_pay_by_attendee')
                            _e('Fee Pay By Attendee', 'wp-event-manager-sell-tickets');
                        else
                            _e('Fee Pay By Organizer', 'wp-event-manager-sell-tickets');
                        ?>
                    </div>
                </div>

            <?php endforeach; 
            wp_reset_postdata();
            ?>
        </div>
    </div>
<?php endif; ?>