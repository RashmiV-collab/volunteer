<?php
global $post;
$post_id = $post->ID;?>

<div class="options_group show_if_event_package show_if_event_package_subscription">
	<?php 
	woocommerce_wp_select( array(
		'id' => '_event_listing_package_subscription_type',
		'wrapper_class' => 'hide_if_event_package show_if_event_package_subscription',
		'label' => __( 'Subscription Type', 'wp-event-manager-wc-paid-listings' ),
		'description' => __( 'Choose how subscriptions affect this package', 'wp-event-manager-wc-paid-listings' ),
		'value' => get_post_meta( $post_id, '_package_subscription_type', true ),
		'desc_tip' => true,
		'options' => array(
			'package' => __( 'Link the subscription to the package (renew listing limit every subscription term)', 'wp-event-manager-wc-paid-listings' ),
			'listing' => __( 'Link the subscription to posted listings (renew posted listings every subscription term)', 'wp-event-manager-wc-paid-listings' ),
		),
	) );

	woocommerce_wp_text_input( array(
		'id' => '_event_listing_limit',
		'label' => __( 'Event listing limit', 'wp-event-manager-wc-paid-listings' ),
		'description' => __( 'The number of event listings a user can post with this package.', 'wp-event-manager-wc-paid-listings' ),
		'value' => ( $limit = get_post_meta( $post_id, '_event_listing_limit', true ) ) ? $limit : '',
		'placeholder' => __( 'Unlimited', 'wp-event-manager-wc-paid-listings' ),
		'type' => 'number',
		'desc_tip' => true,
		'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1',
		),
	) );

	woocommerce_wp_text_input( array(
		'id' => '_event_listing_duration',
		'label' => __( 'Event listing duration', 'wp-event-manager-wc-paid-listings' ),
		'description' => __( 'The number of days that the event listing will be active.', 'wp-event-manager-wc-paid-listings' ),
		'value' => get_post_meta( $post_id, '_event_listing_duration', true ),
		'placeholder' => get_option( 'event_manager_submission_duration' ),
		'desc_tip' => true,
		'type' => 'number',
		'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1',
		),
	) );

	woocommerce_wp_checkbox( array(
		'id' => '_event_listing_featured',
		'label' => __( 'Feature Listings?', 'wp-event-manager-wc-paid-listings' ),
		'description' => __( 'Feature this event listing - it will be styled differently and sticky.', 'wp-event-manager-wc-paid-listings' ),
		'value' => get_post_meta( $post_id, '_event_listing_featured', true ),
	) );

	if( get_option('enable_event_category_for_event_manager_paid_listings') ) 
	{
		$categories = get_event_listing_categories();
		if(!empty($categories)){
			$options = [];
			foreach ($categories as $key => $category) {
				$options[$category->term_id] = $category->name;
			}

			wc_wp_select_multiple( array(
				'id' => '_event_listing_category',
				'name' => '_event_listing_category[]',
				'label' => __( 'Event Category', 'wp-event-manager-wc-paid-listings' ),
				'description' => __( 'Choose category for this package', 'wp-event-manager-wc-paid-listings' ),
				'value' => get_post_meta( $post_id, '_event_listing_category', true ),
				'desc_tip' => true,
				'options' => $options
			) );
		}
	}

	if( get_option('enable_event_type_for_event_manager_paid_listings') ){
		$types = get_event_listing_types();
		if(!empty($types)){
			$options = [];
			foreach ($types as $key => $type) {
				$options[$type->term_id] = $type->name;
			}

			wc_wp_select_multiple( array(
				'id' => '_event_listing_type',
				'name' => '_event_listing_type[]',
				'label' => __( 'Event Type', 'wp-event-manager-wc-paid-listings' ),
				'description' => __( 'Choose type for this package', 'wp-event-manager-wc-paid-listings' ),
				'value' => get_post_meta( $post_id, '_event_listing_type', true ),
				'desc_tip' => true,
				'options' => $options
			) );
		}
	}?>

	<script type="text/javascript">
		jQuery(function(){
			jQuery('#product-type').change( function() {
				jQuery('#woocommerce-product-data').removeClass(function(i, classNames) {
					var classNames = classNames.match(/is\_[a-zA-Z\_]+/g);
					if ( ! classNames ) {
						return '';
					}
					return classNames.join(' ');
				});
				jQuery('#woocommerce-product-data').addClass( 'is_' + jQuery(this).val() );
			} );
			jQuery('.pricing').addClass( 'show_if_event_package' );
			jQuery('._tax_status_field').closest('div').addClass( 'show_if_event_package show_if_event_package_subscription' );
			jQuery('.show_if_subscription, .options_group.pricing').addClass( 'show_if_event_package_subscription' );
			jQuery('.options_group.pricing ._regular_price_field').addClass( 'hide_if_event_package_subscription' );
			jQuery('#product-type').change();
			jQuery('#_event_listing_package_subscription_type').change(function(){
				if ( jQuery(this).val() === 'listing' ) {
					jQuery('#_event_listing_duration').closest('.form-field').hide().val('');
				} else {
					jQuery('#_event_listing_duration').closest('.form-field').show();
				}
			}).change();
		});
	</script>
</div>
