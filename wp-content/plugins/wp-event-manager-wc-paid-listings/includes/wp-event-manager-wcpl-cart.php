<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_WCPL_Cart
 */
class WP_Event_Manager_WCPL_Cart {

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
		add_action( 'woocommerce_event_package_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
		add_action( 'woocommerce_event_package_subscription_add_to_cart', 'WC_Subscriptions::subscription_add_to_cart', 30 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		if ( WP_Event_Manager_WC_Paid_Listings::is_woocommerce_pre( '3.0.0' ) ) {
			add_action( 'woocommerce_add_order_item_meta', array( $this, 'legacy_order_item_meta' ), 10, 2 );
		} else {
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'checkout_create_order_line_item' ), 10, 4 );
		}
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );

		// Force reg during checkout process
		add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', array( $this, 'enable_signup_and_login_from_checkout' ) );
		add_filter( 'option_woocommerce_enable_guest_checkout', array( $this, 'enable_guest_checkout' ) );
	}

	/**
	 * Checks an cart to see if it contains a event_package.
	 *
	 * @return bool|null
	 */
	public function cart_contains_event_package() {
		global $woocommerce;

		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product instanceof WC_Product && $product->is_type( 'event_package' ) && ! $product->is_type( 'event_package_subscription' ) ) {
					return true;
				}
			}
		}
	}

	/**
	 * Ensure this is yes
	 *
	 * @param string $value
	 * @return string
	 */
	public function enable_signup_and_login_from_checkout( $value ) {
		remove_filter( 'option_woocommerce_enable_guest_checkout', array( $this, 'enable_guest_checkout' ) );
		$woocommerce_enable_guest_checkout = get_option( 'woocommerce_enable_guest_checkout' );
		add_filter( 'option_woocommerce_enable_guest_checkout', array( $this, 'enable_guest_checkout' ) );

		if ( 'yes' === $woocommerce_enable_guest_checkout && ( $this->cart_contains_event_package()  ) ) {
			return 'yes';
		} else {
			return $value;
		}
	}

	/**
	 * Ensure this is no
	 *
	 * @param string $value
	 * @return string
	 */
	public function enable_guest_checkout( $value ) {
		if ( $this->cart_contains_event_package() ) {
			return 'no';
		} else {
			return $value;
		}
	}

	/**
	 * Get the data from the session on page load
	 *
	 * @param array $cart_item
	 * @param array $values
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['event_id'] ) ) {
			$cart_item['event_id'] = $values['event_id'];
		}
		return $cart_item;
	}

	/**
	 * Legacy function for storing meta data in order line items pre-WC 3.0.
	 *
	 * @param mixed $item_id
	 * @param array $values
	 */
	public function legacy_order_item_meta( $item_id, $values ) {
		// Add the fields
		if ( isset( $values['event_id'] ) ) {
			$event = get_post( absint( $values['event_id'] ) );

			wc_add_order_item_meta( $item_id, __( 'Event Listing', 'wp-event-manager-wc-paid-listings' ), $event->post_title );
			wc_add_order_item_meta( $item_id, '_event_id', $values['event_id'] );
		}
	}

	/**
	 * Set the order line item's meta data prior to being saved (WC >= 3.0.0).
	 *
	 * @since 2.7.3
	 *
	 * @param WC_Order_Item_Product $order_item
	 * @param string                $cart_item_key  The hash used to identify the item in the cart
	 * @param array                 $cart_item_data The cart item's data.
	 * @param WC_Order              $order          The order or subscription object to which the line item relates
	 */
	public function checkout_create_order_line_item( $order_item, $cart_item_key, $cart_item_data, $order ) {
		if ( isset( $cart_item_data['event_id'] ) ) {
			$event = get_post( absint( $cart_item_data['event_id'] ) );

			$order_item->update_meta_data( __( 'Event Listing', 'wp-event-manager-wc-paid-listings' ), $event->post_title );
			$order_item->update_meta_data( '_event_id', $cart_item_data['event_id']  );
		}
	}

	/**
	 * Output event name in cart
	 *
	 * @param  array $data
	 * @param  array $cart_item
	 * @return array
	 */
	public function get_item_data( $data, $cart_item ) {
		if ( isset( $cart_item['event_id'] ) ) {
			$event = get_post( absint( $cart_item['event_id'] ) );

			$data[] = array(
				'name'  => __( 'Event Listing', 'wp-event-manager-wc-paid-listings' ),
				'value' => $event->post_title,
			);
		}
		return $data;
	}
}
WP_Event_Manager_WCPL_Cart::get_instance();
