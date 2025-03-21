<?php
/*
* This file is use to create a shortcode of gam event manager bookmarks plugin. 
* Attendees/User can bookmark events and organizer can bookmark attendees/user using the shortcode [event_manager_my_bookmarks]. Only logged in users can bookmarks.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WPEM_Bookmarks_Shortcodes class.
 */
class WPEM_Bookmarks_Shortcodes {	
	/**
	 * Constructor
	 */
	public function __construct() {			
		add_shortcode( 'event_manager_my_bookmarks', array( $this, 'event_manager_my_bookmarks' ) );

		add_filter( 'wpem_dashboard_menu', array($this,'wpem_dashboard_menu_add') );
		add_action( 'event_manager_event_dashboard_content_wpem_bookmarks', array($this,'wpem_dashboard_content_add') );
	}

	/**
	 * add dashboard menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpem_dashboard_menu_add($menus){
		$menus['wpem_bookmarks'] = [
						'title' => __('Bookmarks', 'wp-event-manager-bookmarks'),
						'icon' => 'wpem-icon-bookmark',
						'query_arg' => ['action' => 'wpem_bookmarks'],
					];

		return $menus;
	}

	/**
	 * add dashboard content function.
	 */
	public function wpem_dashboard_content_add(){
    	echo do_shortcode('[event_manager_my_bookmarks]');
	}
    
    /**
	 * User bookmarks shortcode
	 */
	public function event_manager_my_bookmarks(){
    	ob_start();

    	if ( ! is_user_logged_in() ) {
			?>
			<div class="wpem-alert wpem-alert-info"><?php _e( 'You need to be signed in to manage your bookmarks.', 'wp-event-manager-bookmarks' ); ?>
		       	<a href="<?php echo apply_filters( 'event_manager_event_dashboard_login_url', get_option('event_manager_login_page_url',wp_login_url()) ); ?>"><?php _e( 'Sign in', 'wp-event-manager-bookmarks' ); ?></a>
		    </div>
			<?php
			return ob_get_clean();
		}

		wp_enqueue_script( 'wp-event-manager-bookmarks-bookmark' );

		do_action('event_manager_my_bookmarks_before');

		$bookmarks = $this->get_user_bookmarks();

		get_event_manager_template( 'my-bookmarks.php', array(
			'bookmarks'     => $bookmarks
		), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );

		do_action('event_manager_my_bookmarks_after');

		return ob_get_clean();
	}
	
	   /**
	 * Get a user's bookmarks
	 * @param  integer $user_id
	 * @return array
	 */
	public function get_user_bookmarks( $user_id = 0 ) {
		if ( ! $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} elseif ( ! $user_id ) {
			return false;
		}

		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}event_manager_bookmarks WHERE user_id = %d ORDER BY date_created;", $user_id ) );
	}
}
new WPEM_Bookmarks_Shortcodes();