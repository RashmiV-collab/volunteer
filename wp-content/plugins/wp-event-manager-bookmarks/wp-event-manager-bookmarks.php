<?php
/*
* Plugin Name: WP Event Manager - Bookmarks
* Plugin URI: http://www.wp-eventmanager.com/plugins/
* Description: Allow logged in attendees and organizers to bookmark events and attendee profile along with an added note.
* 
* Author: WP Event Manager
* Author URI: https://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-bookmarks
* Domain Path: /languages
* Version: 1.2.2
* Since: 1.0
* Requires WordPress Version at least: 4.1
* 
* Copyright: 2015 GAM Themes
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_bookmarks() {
    /*
    * Check weather GAM Event Manager is installed or not
    */
    if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php' ) ) {
        global $pagenow;
    	if( $pagenow == 'plugins.php' )	{
            echo '<div id="error" class="error notice is-dismissible"><p>';
            echo  __( 'WP Event Manager is require to use WP Event Manager - Bookmarks' , 'wp-event-manager-bookmarks');
            echo '</p></div>';		
    	}  		
    }
}
add_action( 'admin_notices', 'pre_check_before_installing_bookmarks' );

/**
 * WP_Event_Manager_Bookmarks class.
 */
class WP_Event_Manager_Bookmarks extends WPEM_Updater {

	/**
	 * Constructor
	 */
	public function __construct() {

		//if wp event manager not active return from the plugin
		if (! is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;
		
		// Define constants
		define( 'WPEM_BOOKMARKS_VERSION', '1.2.2' );
		define( 'WPEM_BOOKMARKS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_BOOKMARKS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        
        // Includes
		include( 'includes/wpem-bookmarks-install.php' );
                
                //external 
		include('external/external.php');

		//Include Admin Setings
		if(is_admin()){
			include( 'admin/wpem-bookmarks-setup.php' );	
			include( 'admin/wpem-bookmarks-settings.php' );	
		}	
        
        //shortcodes
		include( 'shortcodes/wpem-bookmarks-shortcodes.php' );

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'wp', array( $this, 'bookmark_handler' ) );
		add_action( 'single_event_listing_button_end', array( $this, 'bookmark_form' ) );

		// updater
		add_action( 'admin_init', array( $this, 'updater' ) );

		// Activation / deactivation - works with symlinks
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'plugin_activation' ) );
		register_deactivation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'plugin_deactivate' ) );
				
		// Init updates
		$this->init_updates( __FILE__ );
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-bookmarks';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-bookmarks/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
    
        wp_enqueue_style( 'wp-event-manager-bookmarks-frontend', WPEM_BOOKMARKS_PLUGIN_URL . '/assets/css/frontend.min.css' );
        
		wp_register_script( 'wp-event-manager-bookmarks-bookmark', WPEM_BOOKMARKS_PLUGIN_URL . '/assets/js/bookmark.min.js', array( 'jquery','wp-event-manager-common' ), WPEM_BOOKMARKS_VERSION, true );
		
		wp_localize_script( 'wp-event-manager-bookmarks-bookmark', 'event_manager_bookmarks_bookmark', array(			
			'i18n_btnOkLabel' => __( 'Delete', 'wp-event-manager-bookmarks' ),
			'i18n_btnCancelLabel' => __( 'Cancel', 'wp-event-manager-bookmarks' ),
			'i18n_confirm_delete' => __( 'Are you sure you want to delete this bookmark?', 'wp-event-manager-bookmarks' )
		) );
	}

	/**
     * activate function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.2
     */
	public function plugin_activation() {
		WPEM_Bookmarks_Install::install();
	}

	/**
     * deactivate function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.2
     */
	public function plugin_deactivate() {}

	/**
     * updater function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.2
     */
	public function updater() {
		if ( version_compare( WPEM_BOOKMARKS_VERSION, get_option( 'wpem_bookmarks_version' ), '>' ) ){
			WPEM_Bookmarks_Install::update();
			flush_rewrite_rules();
		}
	}

	/**
	 * See if a post is bookmarked by ID
	 * @param  int post ID
	 * @return boolean
	 */
	public function is_bookmarked( $post_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}event_manager_bookmarks WHERE post_id = %d AND user_id = %d;", $post_id, get_current_user_id() ) ) ? true : false;
	}

	/**
	 * Get the total number of bookmarks for a post by ID
	 * @param  int $post_id
	 * @return int
	 */
	public function bookmark_count( $post_id ) {
		global $wpdb;

		if ( false === ( $bookmark_count = get_transient( 'bookmark_count_' . $post_id ) ) ) {
			$bookmark_count = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( id ) FROM {$wpdb->prefix}event_manager_bookmarks WHERE post_id = %d;", $post_id ) ) );
			set_transient( 'bookmark_count_' . $post_id, $bookmark_count, YEAR_IN_SECONDS );
		}

		return absint( $bookmark_count );
	}

	/**
	 * Get a bookmark's note
	 * @param  int post ID
	 * @return string
	 */
	public function get_note( $post_id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT bookmark_note FROM {$wpdb->prefix}event_manager_bookmarks WHERE post_id = %d AND user_id = %d;", $post_id, get_current_user_id() ) );
	}

	/**
	 * Handle the book mark form
	 */
	public function bookmark_handler(){
		global $wpdb;

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! empty( $_POST['submit_bookmark'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_bookmark' ) ){
			$post_id = absint( $_POST['bookmark_post_id'] );
			$note    = wp_kses_post( stripslashes( $_POST['bookmark_notes'] ) );
			//validation filter
			$valid = apply_filters( 'bookmark_form_validate_fields', true );
			if ( is_wp_error( $valid ) ) {
				throw new Exception( $valid->get_error_message() );
			}
			if ( $post_id && in_array( get_post_type( $post_id ), array( 'event_listing') ) ){
				if ( ! $this->is_bookmarked( $post_id ) ){
					$wpdb->insert(
						"{$wpdb->prefix}event_manager_bookmarks",
						array(
							'user_id'       => get_current_user_id(),
							'post_id'       => $post_id,
							'bookmark_note' => $note,
							'date_created'  => current_time( 'mysql' )
						)
					);
				} else {
					$wpdb->update(
						"{$wpdb->prefix}event_manager_bookmarks",
						array(
							'bookmark_note' => $note
						),
						array(
							'post_id'       => $post_id,
							'user_id'       => get_current_user_id()
						)
					);
				}
				delete_transient( 'bookmark_count_' . $post_id );
			}
			add_action('event_content_start', array($this,'add_bookmark_form_success') );
		}

		if ( ! empty( $_GET['remove_bookmark'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'remove_bookmark' ) ){
			$post_id = absint( $_GET['remove_bookmark'] );

			$delete = $wpdb->delete(
				"{$wpdb->prefix}event_manager_bookmarks",
				array(
					'post_id'       => $post_id,
					'user_id'       => get_current_user_id()
				)
			);

			if($delete){
				delete_transient( 'bookmark_count_' . $post_id );

				add_action('event_manager_my_bookmarks_before', array($this,'delete_bookmark_form_success') );
				add_action('event_content_start', array($this,'delete_bookmark_form_success') );
			}
		}
	}
	
	/**
	 * Show the bookmark form
	 */
	public function bookmark_form()  {
		global $post, $event_preview;
		
		if ( $event_preview ) {
			return;
		}

		ob_start();

		$post_type = get_post_type_object( $post->post_type );

		if ( ! is_user_logged_in() ) {
			get_event_manager_template( 'login-to-bookmark-form.php', array(
				'post_type'     => $post_type,
				'post'          => $post
			), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
		} else {
			$is_bookmarked = $this->is_bookmarked( $post->ID );

			if ( $is_bookmarked ) {
				$note = $this->get_note( $post->ID );
			} else {
				$note = '';
			}

			wp_enqueue_script( 'wp-event-manager-bookmarks-bookmark' );
			get_event_manager_template( 'bookmark-form.php', array(
				'post_type'     => $post_type,
				'post'          => $post,
				'is_bookmarked' => $is_bookmarked ,
				'note'          => $note
			), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
		}

		echo ob_get_clean();
	}

	/**
	 * Show the bookmark form
	 */
	public function add_bookmark_form_success() {
		$bookmarks_page_id = get_option( 'event_manager_bookmarks_page_id' );

		//Successful, show next step
		get_event_manager_template( 'add-bookmark-success.php', array(
			'bookmarks_page_id' => $bookmarks_page_id,
		), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Show the bookmark form
	 */
	public function delete_bookmark_form_success() {
		//Successful, show next step
		get_event_manager_template( 'delete-bookmark-success.php', array(), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
	}
}

$GLOBALS['event_manager_bookmarks'] = new WP_Event_Manager_Bookmarks();