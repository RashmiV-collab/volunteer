<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Package Product Type
 * WP_Event_Manager_WCPL_Product_Event_Package
 */
class WC_Product_Event_Package extends WP_Event_Manager_WCPL_Package_Product {
	/**
	 * Constructor
	 *
	 * @param int|WC_Product|object $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		$this->product_type = 'event_package';
		parent::__construct( $product );
	}
	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'event_package';
	}
	
	/**
	 * We want to sell events one at a time
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {
		return apply_filters( 'event_manager_paid_listing_' . $this->get_type() . '_is_sold_individually', true );
	}
	
	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->get_id() ) ) : get_permalink( $this->get_id() );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}
	/**
	 * Get the add to cart button text
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'wp-event-manager-wc-paid-listings' ) : __( 'Read More', 'wp-event-manager-wc-paid-listings' );

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}
	/**
	 * Event Packages can always be purchased regardless of price.
	 *
	 * @return boolean
	 */
	public function is_purchasable() {
		return true;
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
		if ( $event_listing_duration ) {
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
}
