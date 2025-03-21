<?php
namespace WPEventManagerAttendee;

/**
 * Class Plugin
 *
 * Main Plugin class
 */
class Plugin {

	/**
	 * Instance
	 *
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @access public
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 *  Plugin class constructor
	 *
	 * Register plugin action hooks and filters
	 *
	 * @access public
	 */
	public function __construct() {
	    // Register widget scripts
	    add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
		// Register widgets
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
	}
	/**
	 * widget_scripts
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function widget_scripts() {
	    wp_register_script( 'wp-event-manager-attendee-information', WPEM_ATTENDEE_INFORMATION_PLUGIN_URL . '/assets/js/attendee-information.min.js', array( 'jquery','wp-event-manager-common'), WPEM_ATTENDEE_INFORMATION_VERSION, true );
	    
	    wp_enqueue_script( 'wp-event-manager-attendee-information');
	    wp_localize_script( 'wp-event-manager-attendee-information', 'event_manager_attendee_information', array(
	        'admin_ajax_url' => admin_url( 'admin-ajax.php' )
	    ) );
	}	
	/**
	 * Include Widgets files
	 *
	 * Load widgets files
	 *
	 * @access private
	 */
	private function include_widgets_files() {
		require_once( __DIR__ . '/elementor-widgets/elementor-event-attendee.php' );
	}

	/**
	 * Register Widgets
	 *
	 * Register new Elementor widgets.
	 *
	 * @access public
	 */
	public function register_widgets() {
		// Its is now safe to include Widgets files
		$this->include_widgets_files();

		// Register Widgets
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Widgets\Elementor_Event_Attendee() );
	}
}

// Instantiate Plugin Class
Plugin::instance();
