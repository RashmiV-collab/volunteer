<?php
/**
 * My Packages
 *
 * Shows packages on the account page
 */
if ( ! defined( 'ABSPATH' ) ) { exit;}?>

<div class="wpem-woo-dashboard-table">
    <h2 class="wpem-heading-text"><?php echo apply_filters( 'woocommerce_my_account_wpem_paid_listings_packages_title', __( 'My Event Packages', 'wp-event-manager-wc-paid-listings' ), $type ); ?></h2>
    <table class="shop_table my_account_event_packages my_account_wc_paid_listing_packages">
    	<thead>
    		<tr>
    			<th scope="col"><?php _e( 'Package Name', 'wp-event-manager-wc-paid-listings' ); ?></th>
                <th scope="col"><?php _e( 'Order ID', 'wp-event-manager-wc-paid-listings' ); ?></th>
                <th scope="col"><?php _e( 'Total', 'wp-event-manager-wc-paid-listings' ); ?></th>
    			<th scope="col"><?php _e( 'Remaining', 'wp-event-manager-wc-paid-listings' ); ?></th>
    			<?php if ( 'event_listing' === $type ) : ?>
    				<th scope="col"><?php _e( 'Listing Duration', 'wp-event-manager-wc-paid-listings' ); ?></th>
    			<?php endif; ?>
    			<th scope="col"><?php _e( 'Featured?', 'wp-event-manager-wc-paid-listings' ); ?></th>
    		</tr>
    	</thead>
    	<tbody>
    		<?php foreach ( $packages as $package ) :
    		    $package = wpem_paid_listings_get_package( $package );
                $order = wc_get_order($package->get_order_id());
                $view_order_url = esc_url( $order->get_view_order_url() );?>
    			<tr>
    				<td><?php echo $package->get_title(); ?></td>
                    <td><a href="<?php echo $view_order_url; ?>">#<?php echo $package->get_order_id(); ?></a></td>
                    <td><?php echo $package->get_limit(); ?></td>
    				<td><?php echo $package->get_limit() ? absint( $package->get_limit() - $package->get_count() ) : __( 'Unlimited', 'wp-event-manager-wc-paid-listings' ); ?></td>
    				<?php if ( 'event_listing' === $type ) : ?>
    					<td><?php echo $package->get_duration() ? sprintf( _n( '%d day', '%d days', $package->get_duration(), 'wp-event-manager-wc-paid-listings' ), $package->get_duration() ) : '-'; ?></td>
    				<?php endif; ?>
    				<td><?php echo $package->is_featured() ? __( 'Yes', 'wp-event-manager-wc-paid-listings' ) : __( 'No', 'wp-event-manager-wc-paid-listings' ); ?></td>
    			</tr>
    		<?php endforeach; ?>
    	</tbody>
    </table>
</div>
