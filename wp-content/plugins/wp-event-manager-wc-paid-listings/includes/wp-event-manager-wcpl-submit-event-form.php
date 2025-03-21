<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Integration
 */
class WP_WC_Paid_Listings_Submit_Event_Form {
	private static $package_id      = 0;
	private static $is_user_package = false;
	
	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'the_title', array( __CLASS__, 'append_package_name' ) );
		add_filter( 'submit_event_steps', array( __CLASS__, 'submit_event_steps' ), 20 );
		
		// Posted Data
		if ( ! empty( $_POST['event_package'] ) ) {
			
			if ( is_numeric( $_POST['event_package'] ) ) { 
				self::$package_id      = absint( $_POST['event_package'] );
				self::$is_user_package = false;
			} else {
				self::$package_id      = absint( substr( $_POST['event_package'], 5 ) );
				self::$is_user_package = true;
			}
		} elseif ( ! empty( $_COOKIE['chosen_package_id'] ) ) {
			self::$package_id      = absint( $_COOKIE['chosen_package_id'] );
			self::$is_user_package = absint( $_COOKIE['chosen_package_is_user_package'] ) === 1;
		}

		add_filter( 'event_manager_dropdown_selection_event_listing_category', array( __CLASS__, 'event_listing_category_multiselect' ) );
		add_filter( 'event_manager_dropdown_selection_event_listing_type', array( __CLASS__, 'event_listing_type_multiselect' ) );

		add_filter( 'event_manager_term_select_field_wp_dropdown_categories_args', array( __CLASS__, 'event_listing_category_type' ) );
	}

	/**
	 * Replace a page title with the endpoint title
	 *
	 * @param  string $title
	 * @return string
	 */
	public static function event_listing_category_type( $args ){
		if ( !empty( $_POST ) && !is_admin() && is_page( get_option('event_manager_submit_event_form_page_id' ) ) && self::$package_id && 'before' === get_option('event_manager_paid_listings_flow') && get_option('enable_event_category_for_event_manager_paid_listings') ) {
			if ( self::$is_user_package ) {
				$user_package = wpem_paid_listings_get_user_package( self::$package_id );
			    $product_id = $user_package->get_product_id();
			}else{
				$product_id = self::$package_id;
			}

			if(isset($product_id) && !empty($product_id)){
				if( isset($args['taxonomy']) && $args['taxonomy'] === 'event_listing_type' ){
					$package_type = get_post_meta($product_id, '_event_listing_type', true);

					if(!empty($package_type)){
						$args['include'] = $package_type;
					}
				}else if( isset($args['taxonomy']) && $args['taxonomy'] === 'event_listing_category' ){
					$package_category = get_post_meta($product_id, '_event_listing_category', true);

					if(!empty($package_category)){
						$args['include'] = $package_category;
					}
				}
			}
		}
		return $args;
	}

	/**
	 * Replace a page title with the endpoint title
	 *
	 * @param  string $title
	 * @return string
	 */
	public static function event_listing_category_multiselect( $categories ){
		if ( !empty( $_POST ) && !is_admin() && is_page( get_option('event_manager_submit_event_form_page_id' ) ) && self::$package_id && 'before' === get_option('event_manager_paid_listings_flow') && get_option('enable_event_category_for_event_manager_paid_listings') ) {
			if ( self::$is_user_package ) {
				$user_package = wpem_paid_listings_get_user_package( self::$package_id );
			    $product_id = $user_package->get_product_id();
			}else{
				$product_id = self::$package_id;
			}

			if(isset($product_id) && !empty($product_id)){
				$package_category = get_post_meta($product_id, '_event_listing_category', true);

				if(!empty($package_category)){
					$event_category = [];
					foreach ($categories as $key => $categorie){
						if(in_array($categorie->term_id, $package_category)){
							$event_category[] = $categorie;
						}
					}
					return $event_category;
				}
			}
		}
		return $categories;
	}

		/**
	 * Replace a page title with the endpoint title
	 *
	 * @param  string $title
	 * @return string
	 */
	public static function event_listing_type_multiselect( $types ){
		if ( !empty( $_POST ) && !is_admin() && is_page( get_option('event_manager_submit_event_form_page_id' ) ) && self::$package_id && 'before' === get_option('event_manager_paid_listings_flow') && get_option('enable_event_type_for_event_manager_paid_listings') ){
			if ( self::$is_user_package ) {
				$user_package = wpem_paid_listings_get_user_package( self::$package_id );
			    $product_id = $user_package->get_product_id();
			}else{
				$product_id = self::$package_id;
			}

			if(isset($product_id) && !empty($product_id)){
				$package_type = get_post_meta($product_id, '_event_listing_type', true);

				if(!empty($package_type)){
					$event_type = [];
					foreach ($types as $key => $type){
						if(in_array($type->term_id, $package_type)){
							$event_type[] = $type;
						}
					}
					return $event_type;
				}
			}
		}
		return $types;
	}

	/**
	 * Replace a page title with the endpoint title
	 *
	 * @param  string $title
	 * @return string
	 */
	public static function append_package_name( $title ) {
		if ( ! empty( $_POST ) && ! is_admin() && is_main_query() && in_the_loop() && is_page( get_option( 'event_manager_submit_event_form_page_id' ) ) && self::$package_id && 'before' === get_option( 'event_manager_paid_listings_flow' ) && apply_filters( 'wc_paid_listing_append_package_name', true ) ) {
			if ( self::$is_user_package ) {
				$package = wpem_paid_listings_get_user_package( self::$package_id );
				$title .= ' &ndash; ' . $package->get_title();
			} else {
				$post = get_post( self::$package_id );
				if ( $post ) {
					$title .= ' &ndash; ' . $post->post_title;
				}
			}
			remove_filter( 'the_title', array( __CLASS__, 'append_package_name' ) );
		}
		return $title;
	}

	/**
	 * Change submit button text
	 *
	 * @return string
	 */
	public static function submit_button_text() {
		return __( 'Choose a package &rarr;', 'wp-event-manager-wc-paid-listings' );
	}

	/**
	 * Change initial event status
	 *
	 * @param string  $status
	 * @param WP_Post $event
	 * @return string
	 */
	public static function submit_event_post_status( $status, $event ) {
		
		switch ( $event->post_status ) {
			case 'preview' :
				return 'pending_payment';
			break;
			case 'expired' :
				return 'expired';
			break;
			default :
				return $status;
			break;
		}
	}

	/**
	 * Return packages
	 *
	 * @param array $post__in
	 * @return array
	 */
	public static function get_packages( $post__in = array() ) {
		return get_posts( apply_filters( 'wcpl_get_event_packages_args', array(
			'post_type'        => 'product',
			'posts_per_page'   => -1,
			'post__in'         => $post__in,
			'order'            => 'asc',
			'orderby'          => 'menu_order',
			'suppress_filters' => false,
			'tax_query'        => WC()->query->get_tax_query( array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'event_package', 'event_package_subscription' ),
					'operator' => 'IN',
				),
			) ),
			'meta_query'       => WC()->query->get_meta_query(),
		) ) );
	}

	/**
	 * Change the steps during the submission process
	 *
	 * @param  array $steps
	 * @return array
	 */
	public static function submit_event_steps( $steps ) {
		if ( self::get_packages() && apply_filters( 'wcpl_enable_paid_event_listing_submission', true ) ) {
			// We need to hijack the preview submission to redirect to WooCommerce and add a step to select a package.
			// Add a step to allow the user to choose a package. Comes after preview.
			$steps['wc-choose-package'] = array(
				'name'     => __( 'Choose a package', 'wp-event-manager-wc-paid-listings' ),
				'view'     => array( __CLASS__, 'choose_package' ),
				'handler'  => array( __CLASS__, 'choose_package_handler' ),
				'priority' => 25,
			);

			// If we instead want to show the package selection FIRST, change the priority and add a new handler.
			if ( 'before' === get_option( 'event_manager_paid_listings_flow' ) ) {
				
				$steps['wc-choose-package']['priority'] = 5;
				$steps['wc-process-package'] = array(
													'name'     => '',
													'view'     => false,
													'handler'  => array( __CLASS__, 'choose_package_handler' ),
													'priority' => 25,
											  );
				// If showing the package step after preview, the preview button text should be changed to show this.
			} elseif ( 'before' !== get_option( 'event_manager_paid_listings_flow' ) ) {
				add_filter( 'submit_event_step_preview_submit_text', array( __CLASS__, 'submit_button_text' ), 10 );
			}

			// We should make sure new jobs are pending payment and not published or pending.
			add_filter( 'submit_event_post_status', array( __CLASS__, 'submit_event_post_status' ), 10, 2 );
		}
		return $steps;
	}

	/**
	 * Get the package ID being used for event submission, expanding any user package
	 *
	 * @return int
	 */
	public static function get_package_id() {
		if ( self::$is_user_package ) {
			$package = wpem_paid_listings_get_user_package( self::$package_id );
			return $package->get_product_id();
		}
		return self::$package_id;
	}

	/**
	 * Choose package form
	 *
	 * @param array $atts
	 */
	public static function choose_package( $atts = array() ) {
		$form      = WP_Event_Manager_Form_Submit_Event::instance();
		$event_id    = $form->get_event_id();
		$step      = $form->get_step();
		$form_name = $form->form_name;
		$packages      = self::get_packages( isset( $atts['packages'] ) ? explode( ',', $atts['packages'] ) : array() );
		$user_packages = wpem_paid_listings_get_user_packages( get_current_user_id(), 'event_listing' );
		$button_text   = 'before' !== get_option( 'event_manager_paid_listings_flow' ) ? __( 'Submit', 'wp-event-manager-wc-paid-listings' ) : __( 'Select package', 'wp-event-manager-wc-paid-listings' );
		?>
		<form method="post" id="event_package_selection">
			<h2><?php _e( 'Choose a package', 'wp-event-manager-wc-paid-listings' ); ?></h2>
			<div class="event_listing_packages">
				<?php get_event_manager_template( 'package-selection.php', array(
					'packages' => $packages,
					'user_packages' => $user_packages,
				), 'wp-event-manager-wc-paid-listings', EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_DIR . '/templates/' ); ?>
			</div>
			<div class="event_listing_packages_title">
				<input type="submit" name="continue" class="wpem-theme-button button" value="<?php echo apply_filters( 'submit_event_step_choose_package_submit_text', $button_text ); ?>" />
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
				<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
				<input type="hidden" name="event_manager_form" value="<?php echo $form_name; ?>" />
				
			</div>
		</form>
		<?php
	}

	/**
	 * Validate package
	 *
	 * @param  int  $package_id
	 * @param  bool $is_user_package
	 * @return bool|WP_Error
	 */
	private static function validate_package( $package_id, $is_user_package ) {
		
		if ( empty( $package_id ) ) {
			return new WP_Error( 'error', __( 'Invalid Package.', 'wp-event-manager-wc-paid-listings' ) );
		} elseif ( $is_user_package ) {
			if ( ! wpem_paid_listings_package_is_valid( get_current_user_id(), $package_id ) ) {
				return new WP_Error( 'error', __( 'Invalid Package.', 'wp-event-manager-wc-paid-listings' ) );
			}
		} else {
			$package = wc_get_product( $package_id );
			if ( ! $package->is_type( 'event_package' ) && ! $package->is_type( 'event_package_subscription' ) ) {
				return new WP_Error( 'error', __( 'Invalid Package.', 'wp-event-manager-wc-paid-listings' ) );
			}

			// Don't let them buy the same subscription twice if the subscription is for the package
			if ( class_exists( 'WC_Subscriptions' )
				 && is_user_logged_in()
				 && $package->is_type( 'event_package_subscription' )
				 && $package instanceof WC_Product_Event_Package_Subscription
				 && 'package' === $package->get_package_subscription_type()
			) {
				if ( wcs_user_has_subscription( get_current_user_id(), $package_id, 'active' ) ) {
					return new WP_Error( 'error', __( 'You already have this subscription.', 'wp-event-manager-wc-paid-listings' ) );
				}
			}
		}
		return true;
	}

	/**
	 * Purchase a event package
	 *
	 * @param  int|string $package_id
	 * @param  bool       $is_user_package
	 * @param  int        $event_id
	 * @return bool Did it work or not?
	 */
	private static function process_package( $package_id, $is_user_package, $event_id ) {
		// Make sure the event has the correct status
		if ( 'preview' === get_post_status( $event_id ) ) {
			// Update event listing
			$update_event                  = array();
			$update_event['ID']            = $event_id;
			$update_event['post_status']   = 'pending_payment';
			$update_event['post_date']     = current_time( 'mysql' );
			$update_event['post_date_gmt'] = current_time( 'mysql', 1 );
			$update_event['post_author']   = get_current_user_id();
			wp_update_post( $update_event );
		}

		if ( $is_user_package ) {
			
			$user_package = wpem_paid_listings_get_user_package( $package_id );
			$package      = wc_get_product( $user_package->get_product_id() );

			// Give event the package attributes
			update_post_meta( $event_id, '_event_duration', $user_package->get_duration() );
			update_post_meta( $event_id, '_featured', $user_package->is_featured() ? 1 : 0 );
			update_post_meta( $event_id, '_package_id', $user_package->get_product_id() );
			update_post_meta( $event_id, '_user_package_id', $package_id );

			if ( $package && $package instanceof WC_Product_Event_Package_Subscription && 'listing' === $package->get_package_subscription_type() ) {
				update_post_meta( $event_id, '_event_expires', '' ); // Never expire automatically
			}

			// Approve the event
			if ( in_array( get_post_status( $event_id ), array( 'pending_payment', 'expired' ) ) ) {
				wpem_paid_listings_approve_event_listing_with_package( $event_id, get_current_user_id(), $package_id );
			}

			do_action( 'wcpl_process_package_for_event_listing', $package_id, $is_user_package, $event_id );

			return true;
		} elseif ( $package_id ) {
			$package = wc_get_product( $package_id );

			$is_featured = false;
			if ( $package instanceof WC_Product_Event_Package || $package instanceof WC_Product_Event_Package_Subscription ) {
				$is_featured = $package->is_event_listing_featured();
			}

			// Give event the package attributes
			update_post_meta( $event_id, '_event_duration', $package->get_duration() );
			update_post_meta( $event_id, '_featured', $is_featured ? 1 : 0 );
			update_post_meta( $event_id, '_package_id', $package_id );

			if ( $package instanceof WC_Product_Event_Package_Subscription && 'listing' === $package->get_package_subscription_type() ) {
				update_post_meta( $event_id, '_event_expires', '' ); // Never expire automatically
			}

			// clear cart before add package to the cart
			if(apply_filters('wpem_empty_cart_before_adding_event_package',true))
			    WC()->cart->empty_cart();

			// Add package to the cart
			WC()->cart->add_to_cart( $package_id, 1, '', '', array(
				'event_id' => $event_id,
			) );

			wc_add_to_cart_message( $package_id );

			// Clear cookie
			wc_setcookie( 'chosen_package_id', '', time() - HOUR_IN_SECONDS );
			wc_setcookie( 'chosen_package_is_user_package', '', time() - HOUR_IN_SECONDS );

			do_action( 'wcpl_process_package_for_event_listing', $package_id, $is_user_package, $event_id );

			// Redirect to checkout page
			wp_redirect( get_permalink( wc_get_page_id( 'checkout' ) ) );
			exit;
		}// End if().
	}

	/**
	 * Choose package handler
	 *
	 * @return bool
	 */
	public static function choose_package_handler() {
		$form = WP_Event_Manager_Form_Submit_Event::instance();
		
		// Validate Selected Package
		$validation = self::validate_package( self::$package_id, self::$is_user_package );

		// Error? Go back to choose package step.
		if ( is_wp_error( $validation ) ) {
			$form->add_error( $validation->get_error_message() );
			$form->set_step( array_search( 'wc-choose-package', array_keys( $form->get_steps() ) ) );
			return false;
		}

		// Store selection in cookie
		wc_setcookie( 'chosen_package_id', self::$package_id );
		wc_setcookie( 'chosen_package_is_user_package', self::$is_user_package ? 1 : 0 );

		// Process the package unless we're doing this before a event is submitted
		if ( 'before' !== get_option( 'event_manager_paid_listings_flow' ) || 'wc-process-package' === $form->get_step_key() ) {
			
			if( get_option('enable_event_category_for_event_manager_paid_listings') ){
				if ( self::$is_user_package ) {
					$user_package = wpem_paid_listings_get_user_package( self::$package_id );
				    $product_id = $user_package->get_product_id();
				}else{
					$product_id = self::$package_id;
				}

				$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';

				if(isset($product_id) && !empty($product_id) && !empty($event_id)){
					$package_category = get_post_meta($product_id, '_event_listing_category', true);

					if(!empty($package_category)){
						$selected_category = get_the_terms($event_id, 'event_listing_category');

						if(!empty($selected_category)){
							foreach ($selected_category as $key => $category){
								if(!in_array($category->term_id, $package_category)){
									$form->add_error( __('Event category does not match with package category', 'wp-event-manager-wc-paid-listings') );
									$form->previous_step();
									return false;
								}
							}
						}
					}
				}else{
					return false;
				}
			}

			if( get_option('enable_event_type_for_event_manager_paid_listings') ){
				if ( self::$is_user_package ) {
					$user_package = wpem_paid_listings_get_user_package( self::$package_id );
				    $product_id = $user_package->get_product_id();
				}else{
					$product_id = self::$package_id;
				}

				$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';

				if(isset($product_id) && !empty($product_id) && !empty($event_id)){
					$package_type = get_post_meta($product_id, '_event_listing_type', true);

					if(!empty($package_type)){
						$selected_type = get_the_terms($event_id, 'event_listing_type');

						if(!empty($selected_type)){
							foreach ($selected_type as $key => $type){
								if(!in_array($type->term_id, $package_type)){
									$form->add_error( __('Event type does not match with package type', 'wp-event-manager-wc-paid-listings') );
									$form->previous_step();
									return false;
								}
							}
						}
					}
				}else{
					return false;
				}
			}

			// Product the package
			if ( self::process_package( self::$package_id, self::$is_user_package, $form->get_event_id() ) ) {
				$form->next_step();
			}
		} else {
			$form->next_step();
		}
	}
}

WP_WC_Paid_Listings_Submit_Event_Form::init();
