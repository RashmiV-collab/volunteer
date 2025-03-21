<?php
if ( $packages || $user_packages ) :
	$checked = 1; ?>
    <div class="wpem-evnet-packages event_packages">
        <?php if ( $user_packages ) : ?>
        
			<h3 class="wpem-heading-text package-section">
				<?php _e( 'Your Packages:', 'wp-event-manager-wc-paid-listings' ); ?>
			</h3>
			
			<div class="event-package-wrapper">
				<?php foreach ( $user_packages as $key => $package ) : $package = wpem_paid_listings_get_package( $package );
					$limit = true;
					$disable = '';
					//check if package reached limit then disable radio selection
					if($package->get_count() == $package->get_limit()){
						$limit = false;
						$disable = 'disabled="disabled"';
					} ?>
					<div class="user-event-package wpem-event-package-box-wrapper" >
						<input class="wpem-event-package-radio" type="radio" <?php echo $disable;?> <?php checked( $checked, 1 ); ?> name="event_package" value="user-<?php echo $key; ?>" id="user-package-<?php echo $package->get_id(); ?>" />
						<label class="wpem-event-package-box wpem-your-package-radio" title="Select <?php echo $package->get_title(); ?>" for="user-package-<?php echo $package->get_id(); ?>">
							<div class="wpem-event-package-icon-wrapper"><span class="wpem-event-package-icon" <?php echo $disable;?> ></span></div>
							<div class="wpem-heading-text event-package-title"><?php echo $package->get_title(); ?></div>
							<div class="event-package-content">
								<?php
								if ( $package->get_limit() ) {
									printf( _n( '%1$s event posted out of %2$d', '%1$s events posted out of %2$d', $package->get_count(), 'wp-event-manager-wc-paid-listings' ), $package->get_count(), $package->get_limit() );
								} else {
									printf( _n( '%s event posted', '%s events posted', $package->get_count(), 'wp-event-manager-wc-paid-listings' ), $package->get_count() );
								}
								if ( $package->get_duration() ) {
									printf( ', ' . _n( 'listed for %s day', 'listed for %s days', $package->get_duration(), 'wp-event-manager-wc-paid-listings' ), $package->get_duration() );
								}
								//if package limit is not over then only select default user package
								if($limit == true)
									$checked = 0;
								?>
							</div>
						</label>
					</div>    
				<?php endforeach; ?>
			</div>
		<?php endif; 
		
		if ( $packages ) : ?>
		
			<h3 class="wpem-heading-text package-section"><?php _e( 'Purchase Package:', 'wp-event-manager-wc-paid-listings' ); ?></h3>
			<div class="event-package-wrapper">
	    		<?php foreach ( $packages as $key => $package ) :
					$product = wc_get_product( $package );
					if ( ! $product->is_type( array( 'event_package', 'event_package_subscription' ) ) || ! $product->is_purchasable() ) {
						continue;
					}
					/* @var $product WC_Product_Event_Package |WC_Product_Event_Package_Subscription */
					if ( $product->is_type( 'variation' ) ) {
						$post = get_post( $product->get_parent_id() );
					} else {
						$post = get_post( $product->get_id() );
					}?>
					<div class="event-package wpem-event-package-box-wrapper">
						<input class="wpem-event-package-radio" type="radio" <?php checked( $checked, 1 ); $checked = 0; ?> name="event_package" value="<?php echo $product->get_id(); ?>" id="package-<?php echo $product->get_id(); ?>" />
						<label class="wpem-event-package-box wpem-purchase-package-radio" title="Select <?php echo $product->get_title(); ?>" for="package-<?php echo $product->get_id(); ?>">
							<div class="wpem-event-package-icon-wrapper"><span class="wpem-event-package-icon"></span></div>
							<div class="wpem-heading-text event-package-title"><?php echo $product->get_title(); ?></div>
							<div class="event-package-content">
								<?php if ( ! empty( $post->post_excerpt ) ) : ?>
									<?php echo apply_filters( 'woocommerce_short_description', $post->post_excerpt ) ?>
								<?php endif; ?>
								<?php
									printf( _n( '%1$s for %2$s event', '%1$s for %2$s events', $product->get_limit(), 'wp-event-manager-wc-paid-listings' ) . ' ', $product->get_price_html(), $product->get_limit() ? $product->get_limit() : __( 'unlimited', 'wp-event-manager-wc-paid-listings' ) );
									echo $product->get_duration() ? sprintf( _n( 'listed for %s day', 'listed for %s days', $product->get_duration(), 'wp-event-manager-wc-paid-listings' ), $product->get_duration() ) : '';
								?>
							</div>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		
		<?php endif; ?>
    </div>	
<?php else : ?>
	<div class="wpem-main wpem-alert wpem-alert-warning" role="alert">
        <?php _e( 'No packages found', 'wp-event-manager-wc-paid-listings' ); ?>
    </div>
<?php endif; ?>