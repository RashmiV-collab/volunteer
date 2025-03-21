<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Package Product Type
 */
class WC_Product_Event_Package_Subscription extends WP_Event_Manager_WCPL_Subscription_Product {

	/**
	 * Constructor
	 *
	 * @param int|WC_Product|object $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
		$this->product_type = 'event_package_subscription';
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'event_package_subscription';
	}

	/**
	 * Checks the product type.
	 *
	 * Backwards compat with downloadable/virtual.
	 *
	 * @access public
	 * @param mixed $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( 'event_package_subscription' == $type || ( is_array( $type ) && in_array( 'event_package_subscription', $type ) ) ) ? true : parent::is_type( $type );
	}

	/**
	 * We want to sell events one at a time
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {
		return true;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Events are always virtual
	 *
	 * @return boolean
	 */
	public function is_virtual() {
		return true;
	}

	/**
	 * Return event listing duration granted
	 *
	 * @return int
	 */
	public function get_duration() {
		$event_listing_duration = $this->get_event_listing_duration();
		if ( 'listing' === $this->get_package_subscription_type() ) {
			return false;
		} elseif ( $event_listing_duration ) {
			return $event_listing_duration;
		} else {
			return get_option( 'event_manager_submission_duration' );
		}
	}

	/**
	 * Return event listing limit
	 *
	 * @return int 0 if unlimited
	 */
	public function get_limit() {
		$event_listing_limit = $this->get_event_listing_limit();
		if ( $event_listing_limit ) {
			return $event_listing_limit;
		} else {
			return 0;
		}
	}

	/**
	 * Return if featured
	 *
	 * @return bool true if featured
	 */
	public function is_event_listing_featured() {
		return 'yes' === $this->get_event_listing_featured();
	}

	/**
	 * Get event listing featured flag
	 *
	 * @return string
	 */
	public function get_event_listing_featured() {
		return $this->get_product_meta( 'event_listing_featured' );
	}

	/**
	 * Get event listing limit
	 *
	 * @return int
	 */
	public function get_event_listing_limit() {
		return $this->get_product_meta( 'event_listing_limit' );
	}

	/**
	 * Get event listing duration
	 *
	 * @return int
	 */
	public function get_event_listing_duration() {
		return $this->get_product_meta( 'event_listing_duration' );
	}

	/**
	 * Get package subscription type
	 *
	 * @return string
	 */
	public function get_package_subscription_type() {
		return $this->get_product_meta( 'package_subscription_type' );
	}
}
