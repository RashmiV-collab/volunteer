<?php
namespace WPEventManagerEventsByTags;

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
		// Register widgets
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
	}

	/**
	 * Include Widgets files
	 *
	 * Load widgets files
	 *
	 * @access private
	 */
	private function include_widgets_files() { 
		require_once( __DIR__ . '/elementor-widgets/elementor-events-by-tag.php' );
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
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Widgets\Elementor_Events_By_Tag() );
	}

}

// Instantiate Plugin Class
Plugin::instance();
