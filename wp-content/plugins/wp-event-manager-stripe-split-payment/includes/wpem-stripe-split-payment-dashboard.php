<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WPEM_Stripe_Split_Payments_Dashboard class.
 */
class WPEM_Stripe_Split_Payments_Dashboard {
	private $error      = '';
	private $stripe_dashboard_message = '';

	/**
	 * Constructor
	 */
	public function __construct() {
	
		add_filter( 'wpem_dashboard_menu', array($this,'wpem_dashboard_menu_add') );

		add_action( 'event_manager_event_dashboard_content_split_payment', array( $this, 'show_stripe_settings' ) );
	}

	/**
	 * add dashboard menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpem_dashboard_menu_add($menus) 
	{
		$menus['split_payment'] = [
						'title' => __('Stripe', 'wp-event-manager-stripe-split-payment'),
						'icon' => 'wpem-icon-stripe',
						'query_arg' => ['action' => 'split_payment'],
					];
		return $menus;
	}


	/**
	 * show_stripe_settings function.
	 *
	 * @access public
	 * @param $event
	 * @return void
	 * @since 1.0.0
	 */
	public function show_stripe_settings( $event ) 
	{
		echo do_shortcode('[connect_with_stripe]');
	}

	
}

new WPEM_Stripe_Split_Payments_Dashboard();
