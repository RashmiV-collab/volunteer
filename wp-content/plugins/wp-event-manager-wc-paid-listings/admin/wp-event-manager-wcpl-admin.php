<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_WCPL_Admin
 */
class WP_Event_Manager_WCPL_Admin {

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
		include_once( 'wp-event-manager-wcpl-settings.php' );

		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_ids' ) );
		add_filter( 'event_manager_admin_screen_ids', array( $this, 'add_screen_ids' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_filter( 'woocommerce_subscription_product_types', array( $this, 'woocommerce_subscription_product_types' ) );
		add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );
		add_action( 'woocommerce_process_product_meta_event_package', array( $this, 'save_event_package_data' ) );
		add_action( 'woocommerce_process_product_meta_event_package_subscription', array( $this, 'save_event_package_data' ) );
	
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_data' ) );
		add_filter( 'parse_query', array( $this, 'parse_query' ) );
	}

	/**
	 * Screen IDS
	 *
	 * @param  array $ids
	 * @return array
	 */
	public function add_screen_ids( $ids ) {
		$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );
		return array_merge( $ids, array(
			'users_page_wpem_paid_listings_packages'
		) );
	}

	/**
	 * Add menu items
	 */
	public function admin_menu() {
		add_submenu_page( 'users.php', __( 'Listing Packages', 'wp-event-manager-wc-paid-listings' ), __( 'Listing Packages', 'wp-event-manager-wc-paid-listings' ), 'manage_options', 'wpem_paid_listings_packages' , array( $this, 'packages_page' ) );
	}

	/**
	 * Manage Packages
	 */
	public function packages_page() {
		global $wpdb;

		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

		if ( 'delete' === $action && ! empty( $_GET['delete_nonce'] ) && wp_verify_nonce( $_GET['delete_nonce'], 'delete' ) ) {
			$package_id = absint( $_REQUEST['package_id'] );
			$wpdb->delete( "{$wpdb->prefix}emwcpl_user_packages", array(
				'id' => $package_id,
			) );
			$wpdb->delete( $wpdb->postmeta, array(
				'meta_key' => '_user_package_id',
				'meta_value' => $package_id,
			) );
			echo sprintf( '<div class="updated"><p>%s</p></div>', __( 'Package successfully deleted', 'wp-event-manager-wc-paid-listings' ) );
		}

		if ( 'add' === $action || 'edit' === $action ) {
			$this->add_package_page();
		} else {
			include_once( 'wp-event-manager-wcpl-admin-packages.php' );
			$table = new WP_Event_Manager_WCPL_Admin_Packages();
			$table->prepare_items();
			?>
			<div class="woocommerce wrap">
				<h2><?php _e( 'Listing Packages', 'wp-event-manager-wc-paid-listings' ); ?> <a href="<?php echo esc_url( add_query_arg( 'action', 'add', admin_url( 'users.php?page=wpem_paid_listings_packages' ) ) ); ?>" class="add-new-h2"><?php _e( 'Add User Package', 'wp-event-manager-wc-paid-listings' ); ?></a></h2>
				<form id="package-management" method="post">
					<input type="hidden" name="page" value="wpem_paid_listings_packages" />
					<?php $table->display() ?>
					<?php wp_nonce_field( 'save', 'wpem_paid_listings_packages_nonce' ); ?>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Add package
	 */
	public function add_package_page() {
		include_once( 'wp-event-manager-wcpl-admin-add-package.php' );
		$add_package = new WP_Event_Manager_WCPL_Admin_Add_Package();
		?>
		<div class="woocommerce wrap">
			<h2><?php _e( 'Add User Package', 'wp-event-manager-wc-paid-listings' ); ?></h2>
			<form id="package-add-form" method="post">
				<input type="hidden" name="page" value="wpem_paid_listings_packages" />
				<?php $add_package->form() ?>
				<?php wp_nonce_field( 'save', 'wpem_paid_listings_packages_nonce' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Types for subscriptions
	 *
	 * @param  array $types
	 * @return array
	 */
	public function woocommerce_subscription_product_types( $types ) {
		$types[] = 'event_package_subscription';
		return $types;
	}

	/**
	 * Add the product type
	 *
	 * @param array $types
	 * @return array
	 */
	public function product_type_selector( $types ) {
		$types['event_package'] = __( 'Event Package', 'wp-event-manager-wc-paid-listings' );
		
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$types['event_package_subscription'] = __( 'Event Package Subscription', 'wp-event-manager-wc-paid-listings' );
			
		}
		return $types;
	}

	/**
	 * Show the event package product options
	 */
	public function product_data() {
		global $post;
		$post_id = $post->ID;
		get_event_manager_template( '/admin/html-event-package-data.php', array(), 'wp-event-manager-wc-paid-listings', EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_DIR. '/templates/' );

	}

	/**
	 * Save Event Package data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_event_package_data( $post_id ) {
		global $wpdb;

		// Save meta
		$meta_to_save = array(
			'_event_listing_duration'   => '',
			'_event_listing_limit'      => 'int',
			'_event_listing_featured'   => 'yesno',
			'_event_listing_category'	=> 'array',
			'_event_listing_type'     	=> 'array',
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = absint( $value );
					break;
				case 'float' :
					$value = floatval( $value );
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				case 'array' :
					$value = $value;
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}

		$_package_subscription_type = ! empty( $_POST['_event_listing_package_subscription_type'] ) ? $_POST['_event_listing_package_subscription_type'] : 'package';
		update_post_meta( $post_id, '_package_subscription_type', $_package_subscription_type );
	}


	/**
	 * Filters and sorting handler
	 *
	 * @param  WP_Query $query
	 * @return WP_Query
	 */
	public function parse_query( $query ) {
		global $typenow;

		if ( 'event_listing' === $typenow ) {
			if ( isset( $_GET['package'] ) ) {
				$query->query_vars['meta_key']   = '_user_package_id';
				$query->query_vars['meta_value'] = absint( $_GET['package'] );
			}
		}

		return $query;
	}
}
WP_Event_Manager_WCPL_Admin::get_instance();
