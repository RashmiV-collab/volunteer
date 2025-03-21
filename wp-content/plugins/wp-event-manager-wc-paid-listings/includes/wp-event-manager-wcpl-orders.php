<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_Orders
 */
class WP_Event_Manager_Orders {

	/** @var object Class Instance */
	private static $instance;

	/**
	 * Get the class instance
	 *
	 * @return static
	 */
	public static function get_instance() {
		return null === self::$instance ? ( self::$instance = new self ) : self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_thankyou', array( $this, 'woocommerce_thankyou' ), 5 );

		// Displaying user packages on the frontend
		add_action( 'woocommerce_before_my_account', array( $this, 'my_packages' ) );

		// Statuses
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'package_cancelled' ) );

		add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );

		// User deletion
		add_action( 'delete_user', array( $this, 'delete_user_packages' ) );
	}

	/**
	 * Thanks page
	 *
	 * @param mixed $order_id
	 */
	public function woocommerce_thankyou( $order_id ) {
		global $wp_post_types;

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			if ( isset( $item['event_id'] ) && 'publish' === get_post_status( $item['event_id'] ) ) {
				switch ( get_post_status( $item['event_id'] ) ) {
					case 'pending' :
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once approved.', 'wp-event-manager-wc-paid-listings' ), get_the_title( $item['event_id'] ) ) );
					break;
					case 'pending_payment' :
					case 'expired' :
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once payment has been confirmed.', 'wp-event-manager-wc-paid-listings' ), get_the_title( $item['event_id'] ) ) );
					break;
					default :
						echo wpautop( sprintf( __( '%s has been submitted successfully.', 'wp-event-manager-wc-paid-listings' ), get_the_title( $item['event_id'] ) ) );
					break;
				}

				echo '<p class="event-manager-submitted-paid-listing-actions">';

				if ( 'publish' === get_post_status( $item['event_id'] ) ) {
					echo '<a class="button" href="' . get_permalink( $item['event_id'] ) . '">' . __( 'View Listing', 'wp-event-manager-wc-paid-listings' ) . '</a> ';
				} elseif ( get_option( 'event_manager_event_dashboard_page_id' ) ) {
					echo '<a class="button" href="' . get_permalink( get_option( 'event_manager_event_dashboard_page_id' ) ) . '">' . __( 'View Dashboard', 'wp-event-manager-wc-paid-listings' ) . '</a> ';
				}

				echo '</p>';

			}// End if().
		}// End foreach().
	}

	/**
	 * Show my packages
	 */
	public function my_packages() {
		if ( ( $packages = wpem_paid_listings_get_user_packages( get_current_user_id(), 'event_listing' ) ) && is_array( $packages ) && sizeof( $packages ) > 0 ) {
			
			wc_get_template( '/templates/my-packages.php', array(
				'packages' => $packages,
				'type' => 'event_listing',
			), '', EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_DIR );
		}
	}

	/**
	 * Triggered when an order is paid
	 *
	 * @param  int $order_id
	 */
	public function order_paid( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		if ( get_post_meta( $order_id, 'wpem_paid_listings_packages_processed', true ) ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );

			if ( $product->is_type( array( 'event_package' ) ) && wpem_paid_listings_get_order_customer_id( $order ) ) {

				// Give packages to user
				$user_package_id = false;
				for ( $i = 0; $i < $item['qty']; $i ++ ) {
					$user_package_id = wpem_paid_listings_give_user_package( wpem_paid_listings_get_order_customer_id( $order ), $product->get_id(), $order_id );
				}

				$this->attach_package_listings( $item, $order, $user_package_id );
			}
		}

		update_post_meta( $order_id, 'wpem_paid_listings_packages_processed', true );
	}

	/**
	 * Delete packages on user deletion
	 *
	 * @param mixed $user_id
	 */
	public function delete_user_packages( $user_id ) {
		global $wpdb;

		if ( $user_id ) {
			$wpdb->delete(
				"{$wpdb->prefix}emwcpl_user_packages",
				array(
					'user_id' => $user_id,
				)
			);
		}
	}

	/**
	 * Handles the tasks after the restoration of orders and event listing posts.
	 *
	 * @param int $post_id
	 */
	public function untrash_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		switch ( $post_type ) {
			case 'shop_order':
				$this->untrash_shop_order( $post_id );
				break;
			case 'event_listing':
				$this->untrash_wpem_post( $post_id );
				break;
		}
	}

	/**
	 * Handles the tasks after a event listing post is restored.
	 *
	 * @param int $post_id
	 */
	public function untrash_wpem_post( $post_id ) {
		$product_id = get_post_meta( $post_id, '_package_id', true );
		$user_package_id = get_post_meta( $post_id, '_user_package_id', true );
		$product = wc_get_product( $product_id );
		$user_package = wpem_paid_listings_get_user_package( $user_package_id );
		$order = false;
		if ( $user_package && $user_package->has_package() ) {
			$order = wc_get_order( $user_package->get_order_id() );
		}
		if ( $order && $product && $product->is_type( array( 'event_package' ) ) ) {
			/** This filter is documented in includes/package-functions.php */
			if ( apply_filters( 'event_manager_event_listing_affects_package_count', true, $post_id ) ) {
				wpem_paid_listings_increase_package_count( $order->get_user_id(), $user_package_id );
			}
		}
	}

	/**
	 * Handles tasks after a WC order is restored.
	 *
	 * @param int $order_id
	 */
	public function untrash_shop_order( $order_id ) {
		$order  = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			/**
			 * @var WC_Order_Item_Product $item
			 */
			$product = $item->get_product();
			if ( $product->is_type( array( 'event_package' ) ) && $order->get_user_id() ) {
				$user_package_id = null;
				// Give packages to user
				for ( $i = 0; $i < $item['qty']; $i ++ ) {
					$user_package_id = wpem_paid_listings_give_user_package( $order->get_user_id(), $product->get_id(), wpem_paid_listings_get_order_id( $order ) );
				}
				$this->attach_package_listings( $item, $order, $user_package_id );
			}
		}
	}

	/**
	 * Attached listings to the user package.
	 *
	 * @param array    $item
	 * @param WC_Order $order
	 * @param int      $user_package_id
	 */
	private function attach_package_listings( $item, $order, $user_package_id ) {
		global $wpdb;
		$listing_ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_cancelled_package_order_id', wpem_paid_listings_get_order_id( $order ) ) );
		$listing_ids[] = isset( $item[ 'event_id' ] ) ? $item[ 'event_id' ] : '';
		$listing_ids   = array_unique( array_filter( array_map( 'absint', $listing_ids ) ) );
		foreach ( $listing_ids as $listing_id ) {
			if ( in_array( get_post_status( $listing_id ), array( 'pending_payment', 'expired' ) ) ) {
				wpem_paid_listings_approve_listing_with_package( $listing_id, $order->get_user_id(), $user_package_id );
				delete_post_meta( $listing_id, '_cancelled_package_order_id' );
			}
		}
	}

	/**
	 * Handles the tasks after WC orders and WPJM related posts are trashed.
	 *
	 * @param int $post_id
	 */
	public function wp_trash_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		switch ( $post_type ) {
			case 'shop_order':
				$this->trash_shop_order( $post_id );
				break;
			case 'event_listing':
				$this->trash_wpem_post( $post_id );
				break;
		}
	}

	/**
	 * Handles tasks after a event listing post is trashed.
	 *
	 * @param int $post_id
	 */
	public function trash_wpem_post( $post_id ) {
		$product_id 		= get_post_meta( $post_id, '_package_id', true );
		$user_package_id 	= get_post_meta( $post_id, '_user_package_id', true );
		$product 			= wc_get_product( $product_id );
		$user_package 		= wpem_paid_listings_get_user_package( $user_package_id );
		$order 				= false;
		if ( $user_package && $user_package->has_package() ) {
			$order = wc_get_order( $user_package->get_order_id() );
		}
		if ( $order && $product && $product->is_type( array( 'event_package' ) ) ) {
			/** This filter is documented in includes/package-functions.php */
			if ( apply_filters( 'event_manager_event_listing_affects_package_count', true, $post_id ) ) {
				wpem_paid_listings_decrease_package_count( $order->get_user_id(), $user_package_id );
			}
		}
	}

	/**
	 * If a listing gets trashed/deleted, the pack may need it's listing count changing
	 *
	 * @param int $order_id
	 */
	public function trash_shop_order( $order_id ) {
		$order  = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( empty($product)  ||  ! $product->is_type( array( 'event_package' ) ) && $order->get_user_id() ) {
				continue;
			}
			$this->delete_package( $order_id, $product->get_id() );
		}
	}

	/**
	 * Fires when a order was canceled. Looks for event Packages in order and deletes the package if found.
	 *
	 * @param $order_id
	 */
	public function package_cancelled( $order_id ) {
		$order     = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product->is_type( array( 'event_package' ) ) ) {
				continue;
			}
			$this->delete_package( $order_id, $product->get_id() );
		}
	}

	/**
	 * Deletes a package.
	 *
	 * @param int $order_id
	 * @param int $product_id
	 */
	private function delete_package( $order_id, $product_id ) {
		global $wpdb;

		$user_package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}emwcpl_user_packages WHERE order_id = %d AND product_id = %d;", $order_id, $product_id ) );
		if ( $user_package ) {
			// Delete the package
			$wpdb->delete(
				"{$wpdb->prefix}emwcpl_user_packages",
				array(
					'id' => $user_package->id,
				)
			);

			$listing_ids = wpem_paid_listings_get_listings_for_package( $user_package->id );
			foreach ( $listing_ids as $listing_id ) {
				$original_status = get_post_status( $listing_id );
				$listing = array(
					'ID' => $listing_id,
					'post_status' => 'expired',
				);
				wp_update_post( $listing );

				// Make a record of the order ID and original status in case of re-activation
				update_post_meta( $listing_id, '_cancelled_package_order_id', $order_id );
				update_post_meta( $listing_id, '_post_status_before_package_pause', $original_status );
			}
		}
	}
}
WP_Event_Manager_Orders::get_instance();
