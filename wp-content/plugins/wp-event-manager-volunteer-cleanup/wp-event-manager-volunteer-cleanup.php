<?php

/* * 
 * Plugin Name: WP Event Manager - VolunteerCleanup
 * Description: The custom plugin relies on the following dependencies: Event Manager and its add-ons, FluentCRM, Fluent Forms, WooCommerce, and WP Fusion.
 * Version: 2.4.3
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('WPEM_VOLUNTEER_FILE', __FILE__);
define('WPEM_VOLUNTEER_DIR', plugin_dir_path(WPEM_VOLUNTEER_FILE));
define('WPEM_VOLUNTEER_URI', plugins_url('/', WPEM_VOLUNTEER_FILE));

/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER
{

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * The single instance of the class.
	 */
	private static $_instance = null;
	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * An instance of the class.
	 */
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct()
	{

		// Include required files
		/*******************************/
		/* filter to override event manager templates */
		add_filter('event_manager_locate_template', [$this, 'volunteer_alert_form_template'], 10, 3);
		/* custom js */
		add_action('wp_footer', [$this, 'volunteer_alert_geo_location']);
		/* update alert geo location */
		add_action('wp_insert_post', [$this, 'volunteer_save_alert_geo_location'], 10, 3);

		/** filtering events occuring within 3 weeks within 15 miles */
		add_filter('get_event_listings_result_args', [$this, 'get_volunteer_alert_event_listings'], 10, 2);
		add_filter('event_manager_alerts_get_event_listings_args', [$this, 'get_volunteer_alert_event_listings_args'], 10, 1);
		add_filter('get_event_listings_query_args', [$this, 'get_volunteer_alert_event_listings_query_args'], 10, 2);
		/** Custom settings at event manager admin to add dynamic pages */
		add_filter('event_manager_settings', [$this, 'add_unsubscribe_page__event_manager_settings_filter'], 10, 1);
		/** addition of unsubscribe link  */
		add_filter('wp_mail', [$this, 'get_volunteer_alert_wp_mail'], 10, 1);
		add_shortcode('event_alert_unsusbcribe', [$this, 'event_unsubscribe_alert']);
		add_filter('wp_mail_from', array($this, 'volunteer_mail_from_email'), 20, 1);
		/** unset some options from event manager dashboard menu */
		add_filter('wpem_dashboard_menu', array($this, 'volunteer_wpem_dashboard_menu'), 10, 1);
		/** adding organizer role to author */
		add_action('event_manager_event_submitted', array($this, 'volunteer_add_role_organizer'), 9999, 1);
		// Validation hook
		//add_filter( 'alert_form_validate_fields',array($this,'volunteer_alert_form_validate_fields'),10,1 );
		//add_action('wp',array($this,'volunteer_event_page_redirect'));
		/** Skip profile code on first event creation */
		add_shortcode('event_skip_profile', [$this, 'event_skip_profile_code']);
		//bootstrap
		add_action('wp_enqueue_scripts', [$this, 'custom_css_js_enqueue']);
		// ajax
		// bulk mail to all event registered users
		add_action('wp_ajax_nopriv_wpem_send_bulk_mail', [$this, 'wpem_send_bulk_mail']);
		add_action('wp_ajax_wpem_send_bulk_mail', [$this, 'wpem_send_bulk_mail']);
		// corn to send mail to all event registered users
		add_action('wp_ajax_nopriv_wpem_event_cron_mail', [$this, 'wpem_event_cron_mail']);
		add_action('wp_ajax_wpem_event_cron_mail', [$this, 'wpem_event_cron_mail']);
		// Add content for standard waiver
		add_shortcode('standard_waiver', [$this, 'standard_waiver_code']);
		add_action('admin_footer', [$this, 'waiver_admin_footer']); // For back-end
		// email to attendee
		//add_action( 'woocommerce_thankyou', [$this, 'action_woocommerce_after_order_placed'], 10, 1 ); 

		// wp-admin settings
		add_action('admin_menu', [$this, 'add_sub_menu']);
		// table to save token to save unsubscribe alert
		register_activation_hook(WPEM_VOLUNTEER_FILE, [$this, 'log_table__install']);

		// 1. additional guest 
		// 2. custom script for alert sending mail via wordpress default cron
		include_once('wp-event-registrations-mail-cron.php');

		// Server Executing Bulk Alert Mail Cron (e-blast)
		include_once('server-cron/volunteer-server-cron-send-grid-dev.php');

		// 2.1
		include_once('wpem-volunteer-cleanup-2-1.php');

		// 2.2
		include_once('event-mails/wpem-volunteer-cleanup-em-mails.php');
		include_once('wpem-volunteer-cleanup-2-2.php');

		// 2.3
		include_once('wpem-volunteer-fluentForm-custom.php');
		include_once('wpem-volunteer-cleanup-2-3.php');

		// 2.4
		include_once('wpem-volunteer-cleanup-2-4.php');

		// 2.5
		include_once('wpem-volunteer-cleanup-2-5.php');
	}


	/** 
	 * 1. alert CSV
	 * 2. dynamic cron settings 
	 */
	function add_sub_menu()
	{
		$alert_submenu = add_menu_page('Volunteer Cleanup', 'Volunteer Cleanup', 'activate_plugins', 'volunteer-event-alert', [$this, 'em_import_csv_form']);

		add_submenu_page(
			'volunteer-event-alert',
			'Create Alert',
			'Create Alert',
			'manage_options',
			'volunteer-event-alert',
			[$this, 'em_import_csv_form']
		);

		add_action('load-' . $alert_submenu, array($this, 'volunteer_admin_custom_css'));
		//--------------
		/* $alert_cron_settings = add_submenu_page(
									'volunteer-event-alert',
									'Cron Settings',
									'Cron Settings',
									'manage_options',
									'cron-settings',
									[$this, 'volunteer_cron_settings']
								  );*/

		$alert_cron_settings = add_submenu_page(
			'volunteer-event-alert',
			'SendGrid Log',
			'SendGrid Log',
			'manage_options',
			'volunteer-event-alert-sendgrid',
			[$this, 'volunteer_cron_sendgrid']
		);

		add_action('load-' . $alert_cron_settings, array($this, 'volunteer_admin_custom_css'));
	}

	/**
	 * UI alert cron sendgrid
	 */
	function volunteer_cron_sendgrid()
	{
		if (isset($_GET['id']) && !empty($_GET['id'])) {
			$this->volunteer_get_single_sendgrid($_GET['id']);
			return;
		}
		// Cron Stats 
		$last_cron_start_timestamp = get_option('volunteer_server_custom_cron_start_time');
		$last_cron_start = '';
		if ($last_cron_start_timestamp && $last_cron_start_timestamp > 1) {
			$last_cron_start = date('Y-m-d H:i', $last_cron_start_timestamp);
		}

		$last_cron_end_timestamp = get_option('volunteer_server_custom_cron_end_time');
		$last_cron_end = '';
		if ($last_cron_end_timestamp && $last_cron_end_timestamp > 1) {
			$last_cron_end = date('Y-m-d H:i', $last_cron_end_timestamp);
		}

		// total alert
		global $wpdb;
		$table_name = $wpdb->prefix . 'sendgrid_execute_log';
		$tuesday = date('Y-m-d', strtotime('last Tuesday'));
		$get_email_count = $wpdb->get_results("SELECT SUM(email_count) as email_count FROM $table_name WHERE `created_date` LIKE '%" . $tuesday . "%'", ARRAY_A);
		$email_count = 0;
		if ($get_email_count && isset($get_email_count[0]['email_count'])) {
			$email_count = $get_email_count[0]['email_count'];
		}
		$post_table = $wpdb->prefix . 'posts';
		$postmeta_table = $wpdb->prefix . 'postmeta';
		$get_alerts = $wpdb->get_results("SELECT count(meta_value) as alert_count FROM $postmeta_table as pm join $post_table as p on p.ID = pm.post_id WHERE `meta_key` LIKE '_alert_location' AND `meta_value` IS NOT NULL and post_status like 'publish'", ARRAY_A);
		$alerts = 0;
		if ($get_alerts && isset($get_alerts[0]['alert_count'])) {
			$alerts = $get_alerts[0]['alert_count'];
		}

		$html = '<div class="wrap cron_alert_log">
			<h2>Server Alert Cron</h2>
			<div class="white-background" style="margin-top:3rem;padding: 1rem;border:solid 1px #c3c4c7">
			<h3>Last Cron Stats</h3>
			<form action="" method="post" enctype="multipart/form-data" style="margin-top:2rem;	">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">  Start Time </label> </th>
						<td> ' . $last_cron_start . '
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  End Time </label> </th>
						<td> ' . $last_cron_end . '
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  Total Run </label> </th>
						<td> ' . $rowCount . ' / ' . $alerts . '<br/><p style="color:red"> *Total alerts with valid locations.<br/>
							** Some alerts were not work due to user or events not present
							</p>
						</td>
					</tr>
				</table>
				</form>
			</div>';

		// per cycle execution
		$myrows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
		if ($myrows) {
			$html .= '<div class="white-background" style="margin-top:5rem;padding: 1rem;border:solid 1px #c3c4c7">
				<h3>Cron Running Log</h3>
				<table class="widefat striped dataTable">
				<thead>
					<tr>
						<th> ID </th>
						<th> DateTime </th>
						<th> Count </th>
						<th> Response Code </th>
						<th> Actions </th>
					</tr>
				</thead>
				<tbody>';
			foreach ($myrows as $log) {
				$html .= '<tr>
					<td> ' . $log['ID'] . ' </td>
					<td> ' . $log['created_date'] . ' </td>
					<td> ' . $log['email_count'] . ' </td>
					<td> ' . $log['response_code'] . ' </td>
					<td> <a href="admin.php?page=volunteer-event-alert-sendgrid&id=' . $log['ID'] . '" target="_blank">View More</a></td>
				</tr>';
			}

			$html .= '</tbody>
				</table>
				</div>';
		}

		$html .= '
			</div>
			
		<!-- css -->	
		<style>
			.noVis {
				display: none;
			}
		</style>
		<!-- script -->	
		<script>
		jQuery(document).ready(function($){
			if($(".cron_alert_log").length > 0){
				$columnDefs_arr = [ { targets: [0], className: "noVis" } ];
				var table = $(".dataTable").DataTable({
					columnDefs: $columnDefs_arr,
					order: [[ 0, "desc" ]],
					responsive: true,
					dom: "Bfrtip",
					buttons: [
						"csv",
					]
				} );


			}
		});
		</script>';
		echo $html;
	}

	function volunteer_get_single_sendgrid($id = null)
	{
		// per cycle execution
		global $wpdb;
		$html = '<div class="wrap cron_alert_log">
			<h2>Server Alert Cron</h2>';
		$table_name = $wpdb->prefix . 'sendgrid_execute_log';
		$myrows = $wpdb->get_results("SELECT * FROM $table_name where ID = " . $id, ARRAY_A);
		$i = 1;
		if ($myrows && isset($myrows[0]['request_data'])) {
			$user_list = json_decode($myrows[0]['request_data'], true);
			if (is_array($user_list)) {
				$html .= '<div class="white-background" style="margin-top:5rem;padding: 1rem;border:solid 1px #c3c4c7">
				<h3>Email List</h3>
				<table class="widefat striped dataTable">
				<thead>
					<tr>
						<th> ID </th>
						<th> Alert ID </th>
						<th> Email </th>
					</tr>
				</thead>
				<tbody>';
				foreach ($user_list as $log) {
					$html .= '<tr>
					<td> ' . ($i++) . ' </td>
					<td> ' . $log['alert_id'] . ' </td>
					<td> ' . $log['email'] . ' </td>
				</tr>';
				}

				$html .= '</tbody>
				</table>
				</div>';
			}
		} else {
			$html .= '<div> No data found.</div>';
		}
		$html .= '
		</div>
		
	<!-- script -->	
	<script>
	jQuery(document).ready(function($){
		if($(".cron_alert_log").length > 0){
			var table = $(".dataTable").DataTable({
				responsive: true,
				dom: "Bfrtip",
				buttons: [
					"csv",
				]
			} );


		}
	});
	</script>';
		echo $html;
	}

	/**
	 * UI and Saving alert cron setting to option
	 */
	function volunteer_cron_settings()
	{

		if (!empty($_POST)) {
			update_option('volunteer_alert_cron_settings', $_POST);
		}

		$cron_option = get_option('volunteer_alert_cron_settings');
		$checked = (is_array($cron_option) && isset($cron_option['enable_volunteer_cron'])) ? $cron_option['enable_volunteer_cron'] : 0;
		$sendGrid_checked = (is_array($cron_option) && isset($cron_option['enable_volunteer_send_grid'])) ? $cron_option['enable_volunteer_send_grid'] : 0;
		$start_day = (is_array($cron_option) && isset($cron_option['cron_start_day'])) ? $cron_option['cron_start_day'] : 'Monday';
		$start_time = (is_array($cron_option) && isset($cron_option['cron_start_time'])) ? $cron_option['cron_start_time'] : '1:00';
		$end_day = (is_array($cron_option) && isset($cron_option['cron_end_day'])) ? $cron_option['cron_end_day'] : 'Monday';
		$end_time = (is_array($cron_option) && isset($cron_option['cron_end_time'])) ? $cron_option['cron_end_time'] : '1:00';
		$html = '
		<div class="wrap cron_alert_log">
			<h2>Server Alert Cron</h2>
			<div class="white-background" style="padding: 1rem;border:solid 1px #c3c4c7">
			<h3>Set Cron Execution Day</h3>
			<form action="" method="post" style="margin-top:2rem;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
							<th scope="row"><label for="blogname"> Alert Mail Cron </label> </th>
							<td> <input type="radio" id="enable_volunteer_cron" name="enable_volunteer_cron" value="1" ' . ($checked == 1 ? 'checked' : '') . '> <label for="enable_volunteer_cron">Enable Wp_mail Cron</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="radio" id="enable_volunteer_cron" name="enable_volunteer_cron" value="2" ' . ($checked == 2 ? 'checked' : '') . '> <label for="enable_volunteer_cron">Enable Send Grid Cron</label></td>
					</tr>
					<tr>
							<th scope="row"><label for="blogname"> Send Mail </label> </th>
							<td> <input type="checkbox" id="enable_volunteer_send_grid" name="enable_volunteer_send_grid" value="1" ' . ($sendGrid_checked ? 'checked' : '') . '>
							<label for="enable_volunteer_send_grid">Enable</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  Start (For wp_mail)</label> </th>
						<td>
							<div style="display : inline-block">
								<select name="cron_start_day" class="form-control">
								<option value="1" ' . (($start_day == '1') ? 'selected' : '') . '>Monday</option>
								<option value="2" ' . (($start_day == '2') ? 'selected' : '') . '>Tuesday</option>
								<option value="3" ' . (($start_day == '3') ? 'selected' : '') . '>Wednesday</option>
								<option value="4" ' . (($start_day == '4') ? 'selected' : '') . '>Thursday</option>
								<option value="5" ' . (($start_day == '5') ? 'selected' : '') . '>Friday</option>
								<option value="6" ' . (($start_day == '6') ? 'selected' : '') . '>Saturday</option>
								<option value="7" ' . (($start_day == '7') ? 'selected' : '') . '>Sunday</option>
								</select> 
								<select name="cron_start_time" class="form-control">
									<option value="1:00" ' . (($start_time == '1:00') ? 'selected' : '') . '>1:00</option>
									<option value="5:00" ' . (($start_time == '5:00') ? 'selected' : '') . '>5:00</option>
									<option value="9:00" ' . (($start_time == '9:00') ? 'selected' : '') . '>9:00</option>
									<option value="13:00" ' . (($start_time == '13:00') ? 'selected' : '') . '>13:00</option>
									<option value="17:00" ' . (($start_time == '17:00') ? 'selected' : '') . '>17:00</option>
									<option value="21:00" ' . (($start_time == '21:00') ? 'selected' : '') . '>21:00</option>
									<option value="23:00" ' . (($start_time == '23:00') ? 'selected' : '') . '>23:00</option>
								</select> 
							<div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  End (For wp_mail)</label> </th>
						<td>
							<div style="display : inline-block">
								<select name="cron_end_day" class="form-control">
									<option value="1" ' . (($end_day == '1') ? 'selected' : '') . '>Monday</option>
									<option value="2" ' . (($end_day == '2') ? 'selected' : '') . '>Tuesday</option>
									<option value="3" ' . (($end_day == '3') ? 'selected' : '') . '>Wednesday</option>
									<option value="4" ' . (($end_day == '4') ? 'selected' : '') . '>Thursday</option>
									<option value="5" ' . (($end_day == '5') ? 'selected' : '') . '>Friday</option>
									<option value="6" ' . (($end_day == '6') ? 'selected' : '') . '>Saturday</option>
									<option value="7" ' . (($end_day == '7') ? 'selected' : '') . '>Sunday</option>
								</select> 
								<select name="cron_end_time" class="form-control">
									<option value="1:00" ' . (($end_time == '1:00') ? 'selected' : '') . '>1:00</option>
									<option value="5:00" ' . (($end_time == '5:00') ? 'selected' : '') . '>5:00</option>
									<option value="9:00" ' . (($end_time == '9:00') ? 'selected' : '') . '>9:00</option>
									<option value="13:00" ' . (($end_time == '13:00') ? 'selected' : '') . '>13:00</option>
									<option value="17:00" ' . (($end_time == '17:00') ? 'selected' : '') . '>17:00</option>
									<option value="21:00" ' . (($end_time == '21:00') ? 'selected' : '') . '>21:00</option>
									<option value="23:00" ' . (($end_time == '23:00') ? 'selected' : '') . '>23:00</option>
								</select> 
							<div>
						</td>
					</tr>
					<tr>
						<th><input type="submit" value="Save Settings" class="button-primary" /></th>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  Wp-mail Cron Url </label> </th>
						<td> ' . (get_site_url()) . '/wp-json/volunteer-cron/v1/alert-mail </td>
					</tr>
					<!-- <tr>
						<th scope="row"><label for="blogname">  Send Grid Cron Url </label> </th>
						<td> ' . (get_site_url()) . '/wp-json/volunteer-cron/v1/alert-mail-sendGrid </td>
					</tr> -->	
				</table>
				</form>
			</div> ';

		// Cron Stats 
		global $wpdb;
		$table_name = $wpdb->prefix . 'wpevents_alert_blast_log';
		$wpdb->get_results("select * from $table_name");
		$rowCount = $wpdb->num_rows;

		$last_cron_start_timestamp = get_option('volunteer_server_custom_cron_start_time');
		$last_cron_start = '';
		if ($last_cron_start_timestamp && $last_cron_start_timestamp > 1) {
			$last_cron_start = date('Y-m-d H:i', $last_cron_start_timestamp);
		}

		$last_cron_end_timestamp = get_option('volunteer_server_custom_cron_end_time');
		$last_cron_end = '';
		if ($last_cron_end_timestamp && $last_cron_end_timestamp > 1) {
			$last_cron_end = date('Y-m-d H:i', $last_cron_end_timestamp);
		}

		// total alert
		$args = array(
			'post_type' => 'event_alert', // my custom post type    
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);
		$posts = get_posts($args);
		$alerts = 0;
		if ($posts) {
			$alerts = count($posts);
		}


		$html .= '<div class="white-background" style="margin-top:3rem;padding: 1rem;border:solid 1px #c3c4c7">
			<h3>Last Cron Stats</h3>
			<form action="" method="post" enctype="multipart/form-data" style="margin-top:2rem;	">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">  Start Time </label> </th>
						<td> ' . $last_cron_start . '
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  End Time </label> </th>
						<td> ' . $last_cron_end . '
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">  Total Run </label> </th>
						<td> ' . $rowCount . ' / ' . $alerts . '<br/>' . (($checked == 2) ? '<p style="color:red"> **Excluded from the list are alerts without saved locations in their settings.</p>' : '') . '
						</td>
					</tr>
				</table>
				</form>
			</div>';

		// per cycle execution
		$myrows = $wpdb->get_results("select log_date,count(alert_id) as count from $table_name group by log_date", ARRAY_A);
		if ($myrows) {
			$html .= '<div class="white-background" style="margin-top:5rem;padding: 1rem;border:solid 1px #c3c4c7">
				<h3>Last Cron Running Log</h3>
				<table class="widefat striped dataTable">
				<thead>
					<tr>
						<th> DateTime </th>
						<th> Count </th>
					</tr>
				</thead>
				<tbody>';
			foreach ($myrows as $log) {
				$html .= '<tr>
					<td> ' . $log['log_date'] . ' </td>
					<td> ' . $log['count'] . ' </td>
				</tr>';
			}

			$html .= '</tbody>
				</table>
				</div>';
		}

		// per cycle execution
		$myrows2 = $wpdb->get_results("select * from $table_name where status like '%fail%'", ARRAY_A);
		if ($myrows2) {
			$html .= '<div class="white-background" style="margin-top:3rem;padding: 1rem;border:solid 1px #c3c4c7">
				 <h3 style="margin-top:3rem;">Last Cron Failed Alert</h3>
				<table class="widefat striped dataTable">
				<thead>
					<tr>
						<th> Alert Id </th>
						<th> Reason </th>
					</tr>
				</thead>
				<tbody>';
			foreach ($myrows2 as $log) {
				$html .= '<tr>
					<td> ' . $log['alert_id'] . ' </td>
					<td> ' . $log['reason'] . ' </td>
				</tr>';
			}

			$html .= '</tbody>
				</table>
				</div>';
		}
		// cron finishes
		$html .= '</div>';
		$html .= '
		<script>
		jQuery(document).ready(function($){
			if($(".cron_alert_log").length > 0){
				var table = $(".dataTable").DataTable({
					responsive: true,
					dom: "Bfrtip",
					buttons: [
						"csv",
					]
				} );
			}
		});
		</script>';
		echo $html;
	}

	function volunteer_admin_custom_css()
	{
		add_action('admin_enqueue_scripts', array('WPEM_VOLUNTEER', 'volunteer_enqueue_admin_js'));
	}

	public function volunteer_enqueue_admin_js()
	{
		wp_enqueue_script('data_table_script', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array(), '1.0');
		wp_enqueue_script('data_table_button_script', 'https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js', array(), '1.0');
		wp_enqueue_script('data_table_button_html_script', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js', array(), '1.0');
		wp_enqueue_script('data_table_responsive_script', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', array(), '1.0');
		//
		wp_enqueue_style('data_table_style', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
		wp_enqueue_style('data_table_button_style', 'https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css');
		wp_enqueue_style('data_table_responsive_style', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css');
		//
		wp_enqueue_style('custom_bootstrap_css', WPEM_VOLUNTEER_URI . 'assets/summernote/bootstrap.min.css');
		wp_enqueue_script('custom_bootstrap_js', WPEM_VOLUNTEER_URI . 'assets/summernote/bootstrap.min.js', array('jquery'), '', true);
	}


	/**
	 * Create Alert via CSV
	 */
	function em_import_csv_form()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$log_table = array();
		if (isset($_FILES['csv_file']) || is_uploaded_file($_FILES['csv_file']['tmp_name'])) {

			$file_name = $_FILES['csv_file']['name'];
			$file_size = $_FILES['csv_file']['size'];
			$file_type = $_FILES['csv_file']['type'];

			if ($file_size > 1000000) {
				echo '<div class="error">The CSV file is too large.</div>';
				return;
			}

			if (!preg_match('/\.csv$/', $file_name)) {
				echo '<div class="error" style="padding:2rem;">The file must be a CSV file.</div>';
				return;
			}

			$csv_data = file_get_contents($_FILES['csv_file']['tmp_name']);
			$alerts_data = array_map('str_getcsv', explode("\n", $csv_data));

			$header = array_shift($alerts_data);
			$i = 0;

			foreach ($alerts_data as $alert) {
				$alert_data = array_combine($header, $alert);

				$email = trim($alert_data['email']);

				$user = get_user_by('email', $email);
				$_POST['alert_name'] = $alert_data['zipcode'];
				$_POST['alert_location'] = $alert_data['zipcode'];
				$_POST['alert_frequency'] = 'weekly';
				// prepare log
				$log_table[$i]['email'] = $email;
				$log_table[$i]['zipcode'] = $alert_data['zipcode'];
				//
				if ($user) {
					// Alert not present
					$alert_arr = array();
					$alert_arr = array(
						'post_status' => 'any',
						'post_type' => 'event_alert',
						'author' => $user->ID,
						'meta_query' => array(
							array(
								'key' => '_alert_location', // Replace with the actual meta key for the checkbox field
								'value' => trim($alert_data['zipcode']), // Replace with the value representing the checkbox being checked
								'compare' => '=',
							),
						),
					);
					$is_alert = get_posts($alert_arr);
					// final 
					if (!($is_alert && isset($is_alert[0]->ID))) {

						$alert_arr = array();
						$alert_arr = array(
							'post_title' => $alert_data['zipcode'],
							'post_status' => 'publish',
							'post_type' => 'event_alert',
							'comment_status' => 'closed',
							'post_author' => $user->ID
						);


						// Validation hook
						$alert_arr = apply_filters('alert_form_validate_fields', $alert_arr);

						if ($alert_arr) {
							$alert_id = wp_insert_post($alert_arr);
							if ($alert_id) {

								update_post_meta($alert_id, '_alert_frequency', 'weekly');
								update_post_meta($alert_id, '_alert_location', $alert_data['zipcode']);

								wp_clear_scheduled_hook('event-manager-alert', array($alert_id));
								// prepare log
								$log_table[$i]['status'] = 'Sucess';
								$log_table[$i]['message'] = 'Alert Created : ' . $alert_id;
								//
							} else {
								// prepare log
								$log_table[$i]['status'] = 'Failed';
								$log_table[$i]['message'] = 'Alert not created by Wordpress';
								//
							}
						} else {
							// prepare log
							$log_table[$i]['status'] = 'Failed';
							$log_table[$i]['message'] = 'Alert Validation Failed';
							//
						}
					} else {
						// prepare log
						$log_table[$i]['status'] = 'Failed';
						$log_table[$i]['message'] = 'Alert already exists';
						//
					}

				} else {
					// prepare log
					$log_table[$i]['status'] = 'Failed';
					$log_table[$i]['message'] = 'User not exists.';
					//
				}
				$i++;
			}
		}


		$args = array(
			'post_type' => 'event_alert', // my custom post type    
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);
		$posts = get_posts($args);
		$alerts = 0;
		if ($posts) {
			$alerts = count($posts);
		}

		$html = '
		<div class="wrap">
			<h2>Import Alert</h2>
			<div class="white-background" style="padding: 1rem;border:solid 1px #c3c4c7">
				<label for="blogname">  Total Publish Alerts : </label> <label for="blogname"> ' . $alerts . ' </label>
			</div>
			<div class="white-background" style="padding: 1rem;border:solid 1px #c3c4c7">
			<form action="" method="post" enctype="multipart/form-data" style="margin-top:2rem;	">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">  CSV </label> </th>
						<td>
							<input type="file" name="csv_file" required/>
							<p class="description">(Header : email and zipcode )</p>
						</td>
					</tr>
					<tr>
						<th><input type="submit" value="Import CSV" class="button-primary" /></th>
					</tr>
				</table>
				</form>
			</div> ';
		if (!empty($log_table)) {
			$html .= '<div style="margin-top: 5rem;" class="alert_csv_container">
				<h3> Alert Insert Log </h3>
				<table class="widefat striped dataTable">
					<thead>
						<tr>
							<th> Email </th>
							<th> Zipcode </th>
							<th> Status </th>
							<th> Message </th>
						</tr>
					</thead>
					<tbody>';
			foreach ($log_table as $log) {
				$html .= '<tr>
						<td> ' . $log['email'] . ' </td>
						<td> ' . $log['zipcode'] . ' </td>
						<td> ' . $log['status'] . ' </td>
						<td> ' . $log['message'] . ' </td>
					</tr>';
			}

			$html .= '
					</tbody>
				</table>
			</div>';
		}
		$html .= '</div>';

		$html .= '
		<script>
		jQuery(document).ready(function($){
			if($(".alert_csv_container").length > 0){
				var table = $(".dataTable").DataTable({
					responsive: true,
					dom: "Bfrtip",
					buttons: [
						"csv",
					]
				} );
			}
		});
		</script>';

		echo $html;
	}


	/**
	 *  Admin event corn mail values update 
	 */
	function wpem_event_cron_mail()
	{
		if (isset($_POST['event_id'])) {
			$event_id = $_POST['event_id'];
			$check = false;
			if ($_POST['check'] == 'true') {
				$check = true;
			}
			$clean_html_body = preg_replace('/\xc2\xa0/', '', $_POST['message']);
			$event_cron_mail = array(
				'enable' => $check,
				'message' => json_encode($clean_html_body),
				'subject' => $_POST['subject'],
				'days' => $_POST['day'],
				'time' => $_POST['time']
			);
			if ($event_id) {
				update_post_meta($event_id, 'event_cron_mail_setting', $event_cron_mail);
			}

			echo json_encode(array('success' => true));
		}
		wp_die();
	}

	/**
	 *  Admin ajax bulk email
	 */
	function wpem_send_bulk_mail()
	{
		$get_arr = array();
		if (isset($_POST['value'])) {
			$value_arr = explode('&', $_POST['value']);
			if (is_array($value_arr)) {
				foreach ($value_arr as $val) {
					$index_arr = explode('=', $val);
					if (is_array($index_arr)) {
						$get_arr[$index_arr[0]] = $index_arr[1];
					}
				}
			}
		}

		$event_name = '';
		$message = isset($_POST['message']) ? $_POST['message'] : '';
		if (isset($get_arr['event_id']) && !empty($message)) {

			$post = get_post($get_arr['event_id']);
			$event_name = $post->post_title;

			// Organizer details
			$event_id = $get_arr['event_id'];
			$organizers = get_post_meta($event_id, '_event_organizer_ids', true);
			$organizer_email = '';

			if ($organizers && isset($organizers[0])) {
				$organizer_email = get_post_meta($organizers[0], '_organizer_email', true);
			}

			$subject = isset($_POST['subject']) ? $_POST['subject'] : 'Event: ' . $event_name;

			$args = apply_filters('event_manager_event_registrations_args', array(
				'post_type' => 'event_registration',
				'post_status' => array_merge(array_keys(get_event_registration_statuses()), array('publish', 'archived')),
				'ignore_sticky_posts' => 1,
				'posts_per_page' => -1,
				'post_parent' => $event_id,
			));

			// Filters
			$registration_status = !empty($get_arr['registration_status']) ? sanitize_text_field($get_arr['registration_status']) : '';
			$registration_byname = !empty($get_arr['registration_byname']) ? sanitize_text_field($get_arr['registration_byname']) : '';

			if ($registration_status) {
				$args['post_status'] = $registration_status;
			}
			if ($registration_byname) {
				$args['s'] = $registration_byname;
			}

			$registrations = get_posts($args);
			$sent_emails = array(); // To prevent duplicate emails

			if ($registrations) {
				foreach ($registrations as $register) {
					$replacements = array(
						'event_name' => $event_name,
						'registration_name' => $register->post_title,
					);
					$mail_message = $this->bind_to_template($replacements, $message);
					$email = get_event_registration_email($register->ID);
					$headers = array();

					if ($email && !in_array($email, $sent_emails)) {
						$headers[] = 'Content-Type: text/html; charset=UTF-8';
						if ($organizer_email) {
							$headers[] = 'Reply-To: ' . $organizer_email;
						}

						$mail_message = stripslashes($mail_message);
						wp_mail($email, $subject, $mail_message, $headers);
						$sent_emails[] = $email; // Store sent emails
					}
				}
			}

			// Send a copy to the organizer if not already included
			if (!empty($organizer_email) && !in_array($organizer_email, $sent_emails)) {
				$headers = array('Content-Type: text/html; charset=UTF-8');

				// Manually replace the placeholders for the organizer's copy
				$organizer_replacements = array(
					'event_name' => $event_name,
					'registration_name' => 'Organizer' // Since the organizer is not a registered attendee
				);

				$organizer_message = $this->bind_to_template($organizer_replacements, $message);
				$organizer_message = stripslashes($organizer_message);

				wp_mail($organizer_email, $subject, $organizer_message, $headers);
			}

			echo json_encode(array('success' => true));
		}
		wp_die();
	}


	/**
	 *  replace message variables
	 */
	function bind_to_template($replacements, $template)
	{
		return preg_replace_callback(
			'/{{ (.+?) }}/',
			function ($matches) use ($replacements) {
				return $replacements[$matches[1]];
			},
			$template
		);
	}

	/**
	 *  Css/JS enqueue
	 */
	function custom_css_js_enqueue()
	{
		global $post;
		if (is_object($post) && $post->ID == get_option('event_manager_event_dashboard_page_id')) {
			//bootstrap
			wp_enqueue_script('custom_popper_js', WPEM_VOLUNTEER_URI . 'assets/summernote/popper.min.js', array('jquery'), '', true);
			wp_enqueue_style('custom_bootstrap_css', WPEM_VOLUNTEER_URI . 'assets/summernote/bootstrap.min.css');
			wp_enqueue_script('custom_bootstrap_js', WPEM_VOLUNTEER_URI . 'assets/summernote/bootstrap.min.js', array('jquery'), '', true);

			//summernote
			wp_enqueue_style('custom_summernote_css', WPEM_VOLUNTEER_URI . 'assets/summernote/summernote-bs4.min.css');
			wp_enqueue_script('custom_summernote_js', WPEM_VOLUNTEER_URI . 'assets/summernote/summernote-bs4.min.js', array('jquery'), '', true);
		}

		//custom
		wp_enqueue_style('custom_wpem_css', WPEM_VOLUNTEER_URI . 'assets/custom.css');
	}


	/**
	 *  Shortcode to add skip profile link
	 */
	public function event_skip_profile_code()
	{
		$current_user = wp_get_current_user();
		if ($current_user) {
			$user_id = $current_user->ID;
			$post_arr = array(
				'author' => $user_id,
				'post_type' => 'event_listing',
			);

			// Insert the post into the database
			$posts = get_posts($post_arr);
			//var_dump($posts);exit;
			if (!$posts && isset($_GET['profile'])) {
				$url = get_the_permalink(get_option('event_manager_submit_event_form_page_id')) . '?skip_profile=true';
				echo '<div class="volunter_skip_profile_link"><a href="' . $url . '" area-label="skip-profile">SKIP PROFILE</a></div>';
			}
		}

		return;
	}

	/**
	 *  Add content for standard waiver
	 */
	public function standard_waiver_code()
	{

		if (get_option('event_manager_event_standard_waiver')) {
			return get_option('event_manager_event_standard_waiver');
		}

		return;
	}

	/**
	 * redirect to profile page on first event creation
	 */
	function volunteer_event_page_redirect()
	{
		global $post;
		if (is_object($post) && $post->ID == get_option('event_manager_submit_event_form_page_id')) {
			$current_user = wp_get_current_user();
			if ($current_user) {
				$user_id = $current_user->ID;
				$post_arr = array(
					'author' => $user_id,
					'post_type' => 'event_listing',
				);

				// Insert the post into the database
				$posts = get_posts($post_arr);
				//var_dump($posts);exit;
				if (!$posts && !isset($_GET['skip_profile'])) {
					$url = get_the_permalink(get_option('event_manager_redirect_after_first_event_registration_page_id')) . '?profile=true';
					wp_redirect($url);
					exit;
				}
			}
		}
	}

	/**
	 * Change Event Manager Alert Email Form Template.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function volunteer_alert_form_template($template, $template_name, $template_path)
	{

		switch ($template_name) {
			// alert form on alert page
			case 'alert-form.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/alert-form.php';
				break;
			// alert listing on alert page
			case 'my-alerts.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/my-alerts.php';
				break;
			// select - autoselect , ticket_sale_date
			case 'event-submit.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/event-submit.php';
				break;
			// bulk notification , stats
			case 'event-registrations.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/event-registrations.php';
				break;

			case 'registration-form.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/registration-form.php';
				break;
			// hiding if event meta not present
			case 'content-single-event_listing.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/content-single-event_listing.php';
				break;


			case 'events-calendar.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/events-calendar.php';
				break;

			case 'form-fields/multiselect-field.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/multiselect-field.php';
				break;

			case 'form-fields/radio-field.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/radio-field.php';
				break;

			case 'google-maps-filters.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/google-maps-filters.php';
				break;

			case 'content-event_listing.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/content-event_listing.php';
				break;

			case 'content-email_event_listing.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/content-email_event_listing.php';
				break;

			case 'content-tickets-details.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/content-tickets-details.php';
				break;

			case 'embed-code.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/embed-code.php';
				break;

			case 'tickets-overview-detail.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/tickets-overview-detail.php';
				break;

			case 'embed-code-generator-form.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/embed-code-generator-form.php';
				break;

			case 'content-embeddable-widget-event_listing.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/content-embeddable-widget-event_listing.php';
				break;

			case 'event-registration-edit.php':
				$template = WPEM_VOLUNTEER_DIR . 'wpem-templates/event-registration-edit.php';
				break;
		}

		return $template;
	}

	/**
	 * Email to attendees on order place.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */

	public function action_woocommerce_after_order_placed($order_id)
	{
		global $wpdb;
		$order = wc_get_order($order_id);
		$customer_id = $order->get_user_id();
		$customer_display_name = get_the_author_meta('display_name', $customer_id);

		// $_product =  wc_get_product($k);

		$args = array(
			'post_type' => 'event_registration',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => '_order_id', // Replace with the actual meta key for the checkbox field
					'value' => $order_id, // Replace with the value representing the checkbox being checked
					'compare' => '=',
				),
			),
			'post_status' => array('new', 'confirmed', 'waiting')
		);

		$registrations = get_posts($args);


		$attendee_data = array();
		if (!empty($registrations)) {

			$registrations = json_decode(json_encode($registrations), true);

			$ticket_id = array();
			$registration_id = null;
			foreach ($registrations as $value) {
				// $registration_id = $value['ID'];
				$reg_ticket_id = get_post_meta($value['ID'], '_ticket_id', true);
				if (is_array($reg_ticket_id)) {
					$reg_ticket_id = is_serialized($reg_ticket_id) ? @unserialize($reg_ticket_id) : $reg_ticket_id;
					if (is_array($reg_ticket_id)) {
						$ticket_id[] = array(
							'ticket_id' => $reg_ticket_id[0],
							'registration_id' => $value['ID'],
							'event' => get_the_title($value['post_parent']),
							'event_id' => $value['post_parent'],
						);
					}
				}
			}

			foreach ($order->get_items() as $item) {
				$product_name = $item['name'];
				$product_id = $item['product_id'];

				foreach ($ticket_id as $ticket) {
					if ($product_id == $ticket['ticket_id']) {
						$registration_id = $ticket['registration_id'];
						$t_event_id = $ticket['event_id'];
						$organizer_name = '';
						$t_organizer = get_post_meta($t_event_id, '_event_organizer_ids', true);
						if (isset($t_organizer[0])) {
							$organizer_name = get_post_meta($t_organizer[0], '_organizer_name', true);
						}
						//
						// if user buy multiple ticket from single email address
						$attendee_data[get_post_meta($registration_id, '_attendee_email', true)][] = array(
							'event' => $ticket['event'],
							'attendee_name' => get_post_meta($registration_id, '_attendee_name', true),
							'waiver_name' => get_post_meta($registration_id, 'waiver_', true),
							'waiver' => get_post_meta($registration_id, 'waiver_desc_', true),
							'product_name' => $product_name,
							'start_date_time' => get_post_meta($t_event_id, 'event_start_date', true) . ' ' . get_post_meta($t_event_id, 'event_start_time', true),
							'end_date_time' => get_post_meta($t_event_id, 'event_end_date', true) . ' ' . get_post_meta($t_event_id, 'event_end_time', true),
							'event_location' => get_post_meta($t_event_id, 'event_location', true),
							'event_location' => get_post_meta($t_event_id, 'event_location', true),
							'organizer' => $organizer_name,
							'event_url' => get_permalink($t_event_id),
							'event_thing' => ucwords(str_replace("_", " ", implode(", ", get_post_meta($t_event_id, '_what_will_be_provided?', true)))),
							'event_bring' => ucwords(str_replace("_", " ", implode(", ", get_post_meta($t_event_id, '_what_should_volunteers_bring?', true))))
						);

					}
				}
			}


		}

		foreach ($attendee_data as $attendee_email => $attendee) {
			//  getting all registration data per email
			$message = '';
			foreach ($attendee as $att_data) {
				$messages .= '
				<p>Hi ' . $att_data['product_name'] . ' – Order #' . $order_id . ' has signed you up to attend a cleanup.</p>
				<p>Here is the information you need to know:</p>
				<p>Title: ' . $att_data['event'] . '</p>
				<p>Date and Time: ' . $att_data['start_date_time'] . ' from ' . $att_data['end_date_time'] . '</p>
				<p>Location:' . $att_data['event_location'] . '</p>
				<p>Host:' . $att_data['organizer_name'] . '</p>
				<p>- If you have any questions about this event, please contact the host through the event page here <a href="' . $att_data['event_url'] . '"  target="_blank">' . $att_data['event_url'] . ' </a></p>
				<p>- You can invite others to join you by sharing this link: <a href="' . $att_data['event_url'] . '"  target="_blank">' . $att_data['event_url'] . ' </a></p>
				<p>- Add this event to your calendar by clicking here: <a href="' . $att_data['event_url'] . '?feed=single-event-listings-ical"  target="_blank">' . $att_data['event_url'] . '?feed=single-event-listings-ical </a></p>
				<p>- Items you should bring:  ' . $att_data['event_bring'] . ' </p>
				<p>- Items the organizer will provide: ' . $att_data['event_thing'] . '</p>
				<p></p><p></p>
				<p>In addition:</p>
				<p>- You can create an account to manage your registration and optionally sign up receive weekly emails of upcoming cleanups in your area here: <a href="' . get_site_url() . '/registration" target="_blank">' . get_site_url() . '/registration </a></p>
				<p>- Organize your own cleanup: <a href="' . get_site_url() . '/organize" target="_blank">' . get_site_url() . '/organize </a> </p>
				<p></p><hr/><p></p>';
			}

			$header = array('Content-Type: text/html; charset=UTF-8');

			$subject = "Volunteer Cleanup - Event Invitation.";
			wp_mail($attendee_email, $subject, $messages, $header);
		}


	}

	/**
	 * Script for WP Admin for standard wavier 
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */

	public function waiver_admin_footer()
	{
		?>
		<style>
			.standard_waiver {
				margin: 0px auto 30px;
				padding: 10px;
				border: 1px solid #ccc;
			}

			textarea#_standard_waiver {
				display: none;
			}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				/** Waiver */

				$('label[for="_standard_waiver"] .tips').hide();

				var newDiv = $('<div class="standard_waiver">').html("<?php echo do_shortcode('[standard_waiver]'); ?>");
				newDiv.insertAfter('#_standard_waiver');

				if ($('input[name="_waiver"]').is(':checked')) {

					if ($('input[name="_waiver"]:checked').val() == 'standard') {
						$('.standard_waiver, label[for="_standard_waiver"]').show();
						$('#_external_waiver, label[for="_external_waiver"]').hide();
						$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
					} else if ($('input[name="_waiver"]:checked').val() == 'custom') {
						$('.standard_waiver, label[for="_standard_waiver"]').hide();
						$('#_external_waiver, label[for="_external_waiver"]').hide();
						$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').show();
					} else if ($('input[name="_waiver"]:checked').val() == 'external') {
						$('.standard_waiver, label[for="_standard_waiver"]').hide();
						$('#_external_waiver, label[for="_external_waiver"]').show();
						$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
					} else if ($('input[name="_waiver"]:checked').val() == 'nowaiver') {
						$('.standard_waiver, label[for="_standard_waiver"]').hide();
						$('#_external_waiver, label[for="_external_waiver"]').hide();
						$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
					}
				} else {
					$('.standard_waiver, label[for="_standard_waiver"]').hide();
					$('#_external_waiver, label[for="_external_waiver"]').hide();
					$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
				}

				$('input[name="_waiver"]').on('click', function () {

					if ($(this).is(':checked')) {

						if ($(this).val() == 'standard') {
							$('.standard_waiver, label[for="_standard_waiver"]').show();
							$('#_external_waiver, label[for="_external_waiver"]').hide();
							$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
						} else if ($(this).val() == 'custom') {
							$('.standard_waiver, label[for="_standard_waiver"]').hide();
							$('#_external_waiver, label[for="_external_waiver"]').hide();
							$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').show();
						} else if ($(this).val() == 'external') {
							$('.standard_waiver, label[for="_standard_waiver"]').hide();
							$('#_external_waiver, label[for="_external_waiver"]').show();
							$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
						} else if ($(this).val() == 'nowaiver') {
							$('.standard_waiver, label[for="_standard_waiver"]').hide();
							$('#_external_waiver, label[for="_external_waiver"]').hide();
							$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
						}
					} else {
						$('.standard_waiver, label[for="_standard_waiver"]').hide();
						$('#_external_waiver, label[for="_external_waiver"]').hide();
						$('#wp-_custom_waiver-wrap, label[for="_custom_waiver"]').hide();
					}
				});

				/** End: Waiver */
			});
		</script>
		<?php
	}

	/**
	 * 1. Changes In Event Manager Alert Geolocation.
	 * 2. Send bulk notification to user
	 * 3. Standard wavier
	 * 4. Ticket meta info
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function volunteer_alert_geo_location()
	{
		?>
		<style>
			.volunter_skip_profile_link a {

				background: #ecf5ff;
				border: 1px solid #b3d8ff;
				border-radius: 7px;
				color: #1a7efb;
				cursor: pointer;
				display: inline-block;
				font-size: 14px;
				font-weight: 500;
				line-height: 1;
				padding: 12px 20px;
				text-align: center;
				-webkit-user-select: none;
				-moz-user-select: none;
				user-select: none;
				vertical-align: middle;
				white-space: nowrap;
				float: right;

			}
		</style>
		<script type="text/javascript">
			var x = new Date();
			console.log(x);
		</script>
		<script type="text/javascript">
			if (document.getElementById('alert_location')) {
				function init() {
					var input = document.getElementById('alert_location');
					var options = {
						types: ['(regions)'],
						componentRestrictions: { country: 'us' }
					};
					var autocomplete = new google.maps.places.Autocomplete(input, options);

					// Remove address from the suggestion display
					autocomplete.setComponentRestrictions({ 'country': 'us' });
					autocomplete.addListener('place_changed', function () {
						var place = autocomplete.getPlace();
						if (!place.address_components) {
							document.getElementById('alert_location').value = '';
							return;
						}
						var postalCode = '';
						for (var i = 0; i < place.address_components.length; i++) {
							var addressType = place.address_components[i].types[0];
							if (addressType === 'postal_code') {
								postalCode = place.address_components[i].long_name;
								break;
							}
						}
						document.getElementById('alert_location').value = postalCode;
					});

				}

				google.maps.event.addDomListener(window, 'load', init);
			}
			function validateInput(event) {
				var key = event.key;
				if (!/^[0-9]+$/.test(key)) {
					event.preventDefault();
				}
			}

			jQuery(document).ready(function ($) {

				jQuery(document).on('click', '.wpem-send-bulk-notification', function (e) {
					e.preventDefault();
					var sPageURL = window.location.search.substring(1);
					var message = jQuery('textarea[data-id="wpem_bulk_message_container"]').val();
					message = message.replace(/\r?\n/g, '<br />');
					var subject = jQuery('#wpem_bulk_message_subject').val();
					jQuery("#volunteer_wait").show();
					jQuery(".mail-message").html('');
					jQuery(".mail-message").removeClass("wpem-alert-danger");
					if (message && subject) {
						jQuery.ajax({
							type: "POST",
							url: "<?php echo admin_url('admin-ajax.php'); ?>",
							data: { action: 'wpem_send_bulk_mail', value: sPageURL, message: message, subject: subject },
							dataType: "json",
							success: function (data) {
								if (data.success) {
									jQuery("#volunteer_wait").hide();
									jQuery(".mail-message").addClass("wpem-alert-danger");
									jQuery(".mail-message").html('Mail has been sent.');
									jQuery(".modal-body-form").hide();
									jQuery(".modal-footer").hide();
									jQuery(".mail-message").show();
								}
							}
						});
					}
				});

				jQuery(document).on('click', '.wpem-send-co-host-request', function (e) {
					e.preventDefault();
					var host = jQuery('input#event_co_host').val();
					jQuery("#volunteer_wait").show();
					jQuery(".mail-message").html('');
					jQuery(".mail-message").removeClass("wpem-alert-danger");
					if (host) {
						jQuery.ajax({
							type: "POST",
							url: "<?php echo admin_url('admin-ajax.php'); ?>",
							data: { action: 'wpem_send_co_host_request', host: host },
							dataType: "json",
							success: function (data) {
								if (data.success) {
									jQuery("#volunteer_wait").hide();
									jQuery(".mail-message").addClass("wpem-alert-danger");
									jQuery(".mail-message").html('Mail has been sent.');
									jQuery(".modal-body-form").hide();
									jQuery(".modal-footer").hide();
									jQuery(".mail-message").show();
								}
							}
						});
					}
				});

				jQuery(document).on('click', '#wpem_model_button', function (e) {
					e.preventDefault();
					jQuery(".mail-message").html('');
					jQuery(".mail-message").hide();
					jQuery(".modal-body-form").show();
					jQuery(".modal-footer").show();
					jQuery(".mail-message").removeClass("wpem-alert-danger");
					jQuery("#myModal").show();

				});

				$('#myModal').on('shown.bs.modal', function () {
					/** Summer Note - Editor */
					$('#summernote').summernote({
						placeholder: '',
						tabsize: 2,
						height: 100
					});
				});


				$('#enable_cron').on('click', function () {

					if ($(this).is(':checked')) {

						$('.wpem-cron-box-text-section').show();
					} else {
						$('.wpem-cron-box-text-section').hide();
					}
				});

				/** Waiver Enable/Disable functionality */

				/** Front End */

				if ($('input[name="waiver"]').is(':checked')) {
					$('.fieldset-custom_waiver').hide();
					$('.fieldset-external_waiver').hide();
					$('.fieldset-standard_waiver').hide();

					if ($('input[name="waiver"]:checked').val() == 'standard') {
						$('.fieldset-standard_waiver').show();
					} else if ($('input[name="waiver"]:checked').val() == 'custom') {
						$('.fieldset-custom_waiver').show();
					} else if ($('input[name="waiver"]:checked').val() == 'external') {
						$('.fieldset-external_waiver').show();
					}

				} else {
					$('.fieldset-custom_waiver').hide();
					$('.fieldset-external_waiver').hide();
					$('.fieldset-standard_waiver').show();
				}

				$('input[name="waiver"]').on('click', function () {
					$('.fieldset-custom_waiver').hide();
					$('.fieldset-external_waiver').hide();
					$('.fieldset-standard_waiver').hide();

					if ($(this).is(':checked')) {

						$('.fieldset-custom_waiver').hide();
						$('.fieldset-external_waiver').hide();
						$('.fieldset-standard_waiver').hide();
						if ($(this).val() == 'standard') {
							$('.fieldset-standard_waiver').show();
						} else if ($(this).val() == 'custom') {
							$('.fieldset-custom_waiver').show();
						} else if ($(this).val() == 'external') {
							$('.fieldset-external_waiver').show();
						}

					} else {
						$('.fieldset-standard_waiver').show();
					}
				});

				if ($('.fieldset-standard_waiver').find('.description').text().trim() == '[standard_waiver]') {
					$('.fieldset-standard_waiver').find('.description').html("<?php echo do_shortcode('[standard_waiver]'); ?>");
				}

												/** End: Waiver */


												/* setTimeout(() => {

													console.log($('#standard_waiver_ifr'));
													console.log($('body#tinymce.mce-content-body.standard_waiver'));
													console.log("<?php //echo do_shortcode('[standard_waiver]'); ?>");

				if ($('#standard_waiver_ifr').length > 0) {
					console.log($('#standard_waiver_ifr'));
					console.log($('body#tinymce.mce-content-body.standard_waiver'));
					console.log("<?php //echo do_shortcode('[standard_waiver]'); ?>");
					$('body#tinymce.mce-content-body.standard_waiver').html("<?php echo do_shortcode('[standard_waiver]'); ?>");
				}

			}, 25000); */


			$(document).on('click', '.save_event_cron', function (event) {
				event.preventDefault();
				var message = $('textarea[name="cron_email_template"]').val();
				var subject = $('input[name="cron_email_subject"]').val();
				var day = $('select[name="cron_days"]').val();
				var time = $('select[name="cron_time"]').val();
				var event_id = $('#event_cron_id').val();
				var check = false;
				if ($('#enable_cron').is(':checked')) {
					check = true;
				}
				var _this = jQuery(this);
				// ajax
				if (event_id) {
					_this.addClass('active');
					jQuery(".wpem-cron-ajax-loader").show();
					jQuery.ajax({
						type: "POST",
						url: "<?php echo admin_url('admin-ajax.php'); ?>",
						data: {
							action: 'wpem_event_cron_mail',
							event_id: event_id,
							message: message,
							subject: subject,
							day: day,
							time: time,
							check: check
						},
						dataType: "json",
						success: function (data) {
							if (data.success) {
								jQuery(".wpem-cron-ajax-loader").hide();
								_this.removeClass('active');
							}
						}
					});
				}
			});

			// location zipcode
			jQuery('input.event_location').blur(function () {
				let that = this;
				var val = '';
				setTimeout(() => {
					address = jQuery(that).val();
					geocoder = new google.maps.Geocoder();
					geocoder.geocode({ 'address': address }, function (results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							place = results[0];
							if (!place.address_components) {
								document.getElementById('event_pincode').value = '';
								return;
							}
							var postalCode = '';
							for (var i = 0; i < place.address_components.length; i++) {
								var addressType = place.address_components[i].types[0];
								if (addressType === 'postal_code') {
									postalCode = place.address_components[i].long_name;
									break;
								}
							}
							document.getElementById('event_pincode').value = postalCode;
						}
					});
				}, 10);
			});

			// sales ticket 
			//add links for paid and free tickets	
			jQuery('.event_ticket_add_link').on('click', function (params) {
				setTimeout(() => {
					var start_date = "<?php echo date("m/d/Y"); ?>";
					if (jQuery('.fieldset-ticket_sales_start_date').length > 0) {
						jQuery('.fieldset-ticket_sales_start_date input').each(function () {
							if (jQuery(this).val() == '') {
								jQuery(this).val(start_date);
							}
						});
					}
					var start_time = document.getElementById('event_start_time').value;
					if (jQuery('.fieldset-ticket_sales_start_time').length > 0) {
						jQuery('.fieldset-ticket_sales_start_time input').each(function () {
							if (jQuery(this).val() == '') {
								jQuery(this).val(start_time);
							}
						});
					}
					var end_date = document.getElementById('event_end_date').value;
					if (jQuery('.fieldset-ticket_sales_end_date').length > 0) {
						jQuery('.fieldset-ticket_sales_end_date input').each(function () {
							if (jQuery(this).val() == '') {
								jQuery(this).val(end_date);
							}
						});
					}
					var end_time = document.getElementById('event_end_time').value;
					if (jQuery('.fieldset-ticket_sales_end_time').length > 0) {
						jQuery('.fieldset-ticket_sales_end_time input').each(function () {
							if (jQuery(this).val() == '') {
								jQuery(this).val(end_time);
							}
						});
					}
					jQuery('.fieldset-show_remaining_tickets input').each(function () {
						jQuery(this).prop("checked", true);
					});

				}, 10);
			});
											});

		</script>
		<?php
		global $post;
		if (is_object($post) && $post->ID == get_option('event_manager_event_dashboard_page_id')) {
			$event_name = '';
			if (isset($_GET['event_id'])) {
				$event = get_post($_GET['event_id']);
				if ($event) {
					$event_name = $event->post_title;
				}
			}
			?>
			<!-- Modal -->
			<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalTitle" aria-hidden="true">
				<div class="modal-dialog mt-5" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">Mail Body</h4>
							<button type="button" class="close" data-dismiss="modal">&times;</button>
						</div>
						<div class="modal-body">
							<div class="mail-message" style="display:none;"></div>
							<div class="modal-body-form">
								<div style="margin-bottom: 20px;">
									<label for="wpem_bulk_message_subject">Subject</label>
									<input type="text" id="wpem_bulk_message_subject" class="wpem_bulk_message_subject"
										style="font-size: inherit;" placeholder="<?php echo 'Event : ' . $event_name; ?>"
										value="<?php echo 'Event Reminder: ' . $event_name; ?>" />
								</div>
								<label for="wpem_bulk_message_container">Type message</label>
								<div class="wpem-bulk-message-variables"> Variables : {{ registration_name }} , {{ event_name }}
								</div>
								<textarea data-id="wpem_bulk_message_container" class="wpem_bulk_message_container" rows="4"
									cols="50" style="font-size: inherit;" id="summernote">Hi {{ registration_name }}, This is a reminder about the upcoming cleanup {{ event_name }} that you registered to attend.
																			</textarea>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default wpem-send-bulk-notification">Send</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- co-host model for single event edit -->
			<div class="modal fade" id="mycohostModal" tabindex="-1" role="dialog" aria-labelledby="myModalTitle"
				aria-hidden="true">
				<div class="modal-dialog mt-5" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">Add Co-Host(s)</h4>
							<button type="button" class="close" data-dismiss="modal">&times;</button>
						</div>
						<div class="modal-body">
							<div class="mail-message" style="display:none;"></div>
							<div class="modal-body-form">
								<div style="margin-bottom: 20px;">
									<?php
									// Co-Host Fields (Multiple Email Input Only)
									if (get_option('enable_event_organizer')): ?>
										<label for="wpem_bulk_message_subject">Add Co-Host(s) Emails (comma-separated):</label>
										<input type="text" class="input-text" name="event_co_host" id="event_co_host"
											placeholder="Enter email addresses, separated by commas"
											value="<?php echo esc_attr(implode(',', (array) get_post_meta($post->ID, '_event_organizer_ids', true))); ?>" />
									<?php endif; ?>		
								</div>								
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default wpem-send-co-host-request">Send Request</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="volunteer-ajax-wrapper" id="volunteer_wait"><img
					src="<?php echo WPEM_VOLUNTEER_URI . '/assets/img/Ajax-loader.gif'; ?>">
			</div>
			<style>
				.mail-message {
					padding: 15px;
					margin-bottom: 20px;
				}

				.wpem-bulk-message-variables {
					margin: 10px 0px;
				}

				.wpem-send-bulk-notification {
					padding: 5px 35px !important;
					/* width: 25%; */
				}

				.wpem-send-bulk-notification img {
					width: 40%;
				}

				.wpem-bulk-message {
					display: flex;
					justify-content: space-between;
					align-items: center;
				}

				#wpem_model_button {
					background: none;
					color: red;
					border: 1px solid red;
					padding: 8px 16px;
					border-radius: inherit;
				}

				#myModal button,
				#myModal button:hover {
					background: var(--wpem-primary-color);
				}

				#volunteer_wait {
					margin: 0px;
					padding: 0px;
					position: fixed;
					right: 0px;
					top: 0px;
					width: 100%;
					height: 100%;
					background-color: rgb(102, 102, 102);
					z-index: 30001;
					opacity: 0.5;
					display: none;
				}

				#volunteer_wait img {
					top: 30%;
					left: 45%;
					position: absolute;
					width: 120px;
				}
			</style>
			<?php
		}
		?>
		<style>
			.fieldset-standard_waiver .field {
				margin: 30px auto;
				padding: 10px;
				border: 1px solid #ccc;
			}

			.wpem-form-wrapper .wpem-form-group textarea#standard_waiver {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Save Event Manager Alert Geolocation.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function volunteer_save_alert_geo_location($post_id, $post, $update)
	{
		if ($post->post_type == 'event_alert' && isset($_POST['alert_location']) && !empty($_POST['alert_location'])) {
			$location = $_POST['alert_location'];
			/*if(isset($_POST['alert_location'])){
													$zipcode_arr = google_maps_geocoder($_POST['alert_location']);
													if(!(is_array($zipcode_arr) && isset($zipcode_arr['zipcode']) && !empty($zipcode_arr['zipcode']) && isset($zipcode_arr['country_short']) && trim($zipcode_arr['country_short']) == 'US')){
														throw new Exception(  __('Invalid Zipcode', 'wp-event-manager-alerts') );
													}
												}else{
													throw new Exception(  __('Location required', 'wp-event-manager-alerts') );
												}*/
			$lat_lng = google_maps_geocoder($location);
			if (is_array($lat_lng) && isset($lat_lng['lat']) && isset($lat_lng['lng'])) {
				foreach ($lat_lng as $key => $value) {
					update_post_meta($post_id, 'geolocation_' . $key, $value);
				}
			}
		}

	}


	/**
	 * get 15 miles nearby events by alert address
	 */
	public function get_volunteer_events_by_address($address = null)
	{
		if ($address) {
			$latitude = $longitude = '';
			// get alert by address
			$alert_data = array(
				'post_type' => 'event_alert',
				'meta_key' => '_alert_location',
				'meta_value' => $address,
			);
			$posts = get_posts($alert_data);
			if (isset($posts[0]) && $posts[0]->ID) {
				$id = $posts[0]->ID;
				$latitude = get_post_meta($id, 'geolocation_lat', true);
				$longitude = get_post_meta($id, 'geolocation_lng', true);
				if ($latitude && $longitude) {
					return $this->get_volunteer_events_by_miles($latitude, $longitude);
				}
			}/*else{
												$lat_lng   = google_maps_geocoder($address);
												$latitude  = isset($lat_lng['lat']) ? $lat_lng['lat'] : null;
												$longitude = isset($lat_lng['lng']) ? $lat_lng['lng'] : null;
											}
											return $this->get_volunteer_events_by_miles($latitude,$longitude);*/
		}
		return null;
	}

	/**
	 * calculation get 15 miles nearby events by alert address
	 */
	public function get_volunteer_events_by_miles($latitude = NULL, $longitude = NULL)
	{
		$result = null;
		global $wpdb;
		if ($latitude != NULL && $longitude != NULL) {
			// Radius of the earth 3959 miles or 6371 kilometers.
			$earth_radius = 3959;
			$distance = 15;
			$where = " 
				$wpdb->posts.post_type IN ('post', 'page', 'attachment', 'e-landing-page', 'event_listing', 'event_organizer', 'event_venue', 'product') AND (wp_posts.post_status = 'publish') AND geolocation_lat.meta_key = 'geolocation_lat' AND geolocation_long.meta_key = 'geolocation_long' GROUP BY $wpdb->posts.ID HAVING distance < $distance ";

			$join = "$wpdb->posts INNER JOIN $wpdb->postmeta geolocation_lat ON ( $wpdb->posts.ID = geolocation_lat.post_id ) INNER JOIN $wpdb->postmeta geolocation_long ON ( $wpdb->posts.ID = geolocation_long.post_id ) ";

			$fields = " $wpdb->posts.*, 
						geolocation_lat.meta_value as latitude, 
						geolocation_long.meta_value as longitude, 
						( $earth_radius * acos(
								cos( radians( $latitude ) )
								* cos( radians( geolocation_lat.meta_value ) )
								* cos( radians( geolocation_long.meta_value ) 
								- radians( $longitude ) )
								+ sin( radians( $latitude ) )
								* sin( radians( geolocation_lat.meta_value ) )
						) )
						AS distance ";

			$orderby = " distance DESC ";

			$result = $wpdb->get_results("SELECT {$fields} FROM {$join} WHERE {$where} ORDER BY {$orderby}");
		}
		return $result;
	}

	/**
	 * Get Result - Event Manager Alert.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function get_volunteer_alert_event_listings($result, $query_args)
	{
		if (isset($query_args['volunteer_search']) && isset($query_args['search_location'])) {
			$address = $query_args['search_location'];
			if ($address) {
				$events = $this->get_volunteer_events_by_address($address);
				if ($events) {
					$event_arr = array();
					foreach ($events as $e) {
						$start_date = $end_date = '';
						$post_arr = get_post_meta($e->ID);
						if (is_array($post_arr) && isset($post_arr['_event_start_date'])) {
							$start_date = strtotime($post_arr['_event_start_date'][0]);
							$end_date = strtotime($post_arr['_event_end_date'][0]);
							$extendDate = strtotime('+3 weeks');
							$today = strtotime(date('Y-m-d'));
							if (($start_date >= $today && $start_date <= $extendDate) || ($end_date >= $today && $start_date <= $today)) {
								$event_arr[] = $e->ID;
							}
						}
					}
					if (!empty($event_arr)) {
						$args = array(
							'post_type' => 'event_listing',
							'post__in' => $event_arr,
							'meta_key' => '_event_start_date',
							'orderby' => 'meta_value',
							'order' => 'ASC',
							'meta_type' => 'DATETIME',
							'posts_per_page' => 25,
							'meta_query' => array(
								'relation' => 'OR',
								array(
									'key' => '_private_event', // Replace with the actual meta key for the checkbox field
									'value' => '1', // Replace with the value representing the checkbox being checked
									'compare' => '!=',
								),
								array(
									'key' => '_private_event',
									'compare' => 'NOT EXISTS',
								)
							)
						);

						$result = new WP_Query($args);
					} else {
						$args = array(
							'post_type' => 'event_listing',
							'post__in' => array(2),
						);
						$result = new WP_Query($args);
					}
				} else {
					$args = array(
						'post_type' => 'event_listing',
						'post__in' => array(2),
					);
					$result = new WP_Query($args);
				}
			}
		}
		return $result;
	}

	/**
	 * Get Arguments - Event Manager Alert.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function get_volunteer_alert_event_listings_args($args)
	{
		$args['volunteer_search'] = true;

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key' => '_private_event', // Replace with the actual meta key for the checkbox field
				'value' => '1', // Replace with the value representing the checkbox being checked
				'compare' => '!=',
			),
			array(
				'key' => '_private_event',
				'compare' => 'NOT EXISTS',
			)
		);

		if (isset($args['meta_query']) && count($args['meta_query']) > 0) {
			$args['meta_query'][] = $meta_query;
		} else {
			$args['meta_query'] = $meta_query;
		}


		return $args;
	}

	/**
	 * Get Arguments - Add Unsubscribe page to Event Manager Settings by overriding filter.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_unsubscribe_page__event_manager_settings_filter($settings_array)
	{
		// Modify the settings array here

		// Example: Add a new key-value pair to the settings array
		$settings_array['event_pages'][1][] = array(

			'name' => 'event_manager_event_unsubscribe_page_id',

			'std' => '',

			'label' => __('Event Unsubscribe Page', 'wp-event-manager'),

			'desc' => __('<span style="color:red;">Custom: </span>Select the page where you have placed the [event_alert_unsusbcribe] shortcode. This lets the plugin know where the unsubscribe page is located.', 'wp-event-manager'),

			'type' => 'page',

		);

		$settings_array['event_pages'][1][] = array(

			'name' => 'event_manager_redirect_after_first_event_registration_page_id',

			'std' => '',

			'label' => __('Redirect Page On First Event Registration', 'wp-event-manager'),

			'desc' => __('<span style="color:red;">Custom: </span>Select the page where you have placed the [event_skip_profile] shortcode. This lets the plugin know where the skip profile page is located after first event registration.', 'wp-event-manager'),

			'type' => 'page',
		);

		$settings_array['general_settings'][1][] = array(
			'name' => 'event_manager_event_from_email',
			'std' => '',
			'label' => __('Event From Email', 'wp-event-manager'),
			'desc' => __('<span style="color:red;">Custom: </span>By default, admin email is used as from email by Alert Plugin to send mail. Input value to override the admin email.', 'wp-event-manager'),
			'attributes' => array(),
		);

		$settings_array['general_settings'][1][] = array(
			'name' => 'event_manager_event_standard_waiver',
			'std' => '',
			'label' => __('Standard Waiver', 'wp-event-manager'),
			'desc' => __('<span style="color:red;">Custom: </span>Add content for standard waiver. Use [standard_waiver] to display.', 'wp-event-manager'),
			'attributes' => array('placeholder' => 'Standard waiver content'),
			'type' => 'textarea',
		);

		// Return the modified settings array
		return $settings_array;
	}

	/**
	 * Get Query Arguments - Event Manager Alert.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function get_volunteer_alert_event_listings_query_args($query_args, $args)
	{
		if (isset($args['volunteer_search'])) {
			$query_args['volunteer_search'] = $args['volunteer_search'];
			if (isset($args['search_location']) && empty($args['search_location'])) {
				if (isset($_GET['alert_id']) && !empty($_GET['alert_id']) && isset($_GET['action']) && ($_GET['action'] == 'view'))
					$alert = get_post($_GET['alert_id']);
				if ($alert) {
					$query_args['search_location'] = get_post_meta($alert->ID, '_alert_location', true);
				}
			} elseif (isset($args['search_location'])) {
				$query_args['search_location'] = $args['search_location'];
			}
		}

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key' => '_private_event', // Replace with the actual meta key for the checkbox field
				'value' => '1', // Replace with the value representing the checkbox being checked
				'compare' => '!=',
			),
			array(
				'key' => '_private_event',
				'compare' => 'NOT EXISTS',
			)
		);

		if (isset($query_args['meta_query']) && count($query_args['meta_query']) > 0) {
			$query_args['meta_query'][] = $meta_query;
		} else {
			$query_args['meta_query'] = $meta_query;
		}

		return $query_args;
	}

	/**
	 * Put Unsubscribe Link - Event Manager Alert.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function get_volunteer_alert_wp_mail($args)
	{
		$message = $args['message'];
		$user_email = $args['to'];
		/*if (strpos($message, 'alert_unsusbcribe_token') !== false) {
									
									global $wpdb;
									$user  =  get_user_by( 'email', $user_email );
									if($user){
										update_user_meta($user->ID , 'send_volunteer_custom_plugin', date('Y-m-d H:i:s') );
										$posttitle = $this->get_string_between($message, '[alert_unsusbcribe_token]', '[/alert_unsusbcribe_token]');
										$postid = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_title LIKE '" . $posttitle . "' AND  post_author = ".$user->ID." AND post_type LIKE 'event_alert'" , ARRAY_A);
										$m_time = microtime();
										$ip = $_SERVER['REMOTE_ADDR'];
										
										if(is_array($postid[0])){
											$token = md5($ip .$user->ID. $m_time . rand(0, time()));
											$table_name = $wpdb->prefix.'wpevents_alert_deactivate_log';
											$data = array('user_id' => $user->ID, 'alert_id' => $postid[0]['ID'],'token' =>$token,'status'=>0) ;
											$result = $wpdb->insert($table_name, $data);
											//
											if($result){
												$search = ('[alert_unsusbcribe_token]'.$posttitle.'[/alert_unsusbcribe_token]');
												$replace = (get_permalink(get_option('event_manager_event_unsubscribe_page_id')).'?token='.$token);
												$message = str_replace($search,$replace,$message);	
												update_post_meta($postid[0]['ID'] , 'send_volunteer_custom_plugin', date('Y-m-d H:i:s') );
											}

										}
									}
								}*/
		//$args['to'] = 'learningdcm@gmail.com';
		//$args['message'] = $message;
		//print_R($args);exit;
		return $args;
	}

	public function get_string_between($string, $start, $end)
	{
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0)
			return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}


	/**
	 * unsubscribe alert when URL containing token is hit
	 */
	public function event_unsubscribe_alert()
	{

		if (is_page(get_option('event_manager_event_unsubscribe_page_id'))) {

			global $wpdb;

			$token = (isset($_REQUEST['token']) ? trim($_REQUEST['token']) : null);
			$table_name = $wpdb->prefix . 'wpevents_alert_deactivate_log';
			$result = $wpdb->get_results("SELECT alert_id, user_id FROM $table_name WHERE token = '" . $token . "' ", ARRAY_A);
			$alert_id = (is_array($result) && !empty($result)) ? $result[0]['alert_id'] : null;
			$user_id = (is_array($result) && !empty($result)) ? $result[0]['user_id'] : null;

			if ($alert_id != null) {
				if ('event_alert' === get_post_type($alert_id)) {
					if (get_post_status($alert_id) == 'publish') {
						$post = array('ID' => $alert_id, 'post_status' => 'draft');
						wp_update_post($post);

						$result_update = $wpdb->query("UPDATE $table_name SET `status`= 1 WHERE alert_id = $alert_id AND user_id = $user_id");
						if ($result_update) {
							return '<div class="success-message"><h5 style=" text-align: center; padding: 50px; margin: 150px 100px; box-shadow: 5px 10px 10px #777fb3; background-color: #162ec9; color: #ffffff; ">You have been successfully unsubscribed from the alert "' . get_the_title($alert_id) . '".</h5></div>';
						}
					} else {
						return '<div class="event--error"><h5 style=" text-align: center; padding: 50px; margin: 150px 100px; box-shadow: 5px 10px 10px #777fb3; background-color: #162ec9; color: #ffffff; ">Invalid Token.</h5></div>';
					}

				}

			}

		}
		return '<div></div>';
	}

	/**
	 * From Email
	 */
	public function volunteer_mail_from_email($email)
	{

		if (get_option('event_manager_event_from_email')) {
			return sanitize_email(get_option('event_manager_event_from_email'));
		} else {
			return $email;
		}
	}


	/**
	 *  remove organizer from menu
	 */
	function volunteer_wpem_dashboard_menu($menus)
	{
		unset($menus['organizer_dashboard']);
		return $menus;
	}

	/** 
	 * add additional role
	 */
	function volunteer_add_role_organizer($post_id)
	{
		$post = get_post($post_id);
		if ($post->post_type == 'event_listing') {

			if ($post->post_status == 'preview') {
				$update = wp_update_post(array(
					'ID' => $post_id,
					'post_status' => 'publish'
				));
			}

			// get user author
			$user_id = $post->post_author;
			$user_email = '';
			$user_role = get_user_meta($user_id, 'wp_capabilities', true);

			// additional user role of Organizer within WordPress
			if (is_array($user_role) && !array_key_exists('organizer', $user_role)) {
				$user_role['organizer'] = 1;
				update_user_meta($user_id, 'wp_capabilities', $user_role);
			}

			// added as Organizer under WP Event Manager

			$user = get_user_by('id', $user_id);
			$user_email = $user->user_email;
			$user_login = $user->user_login;
			$post_arr = array(
				'post_status' => 'publish',
				'author' => $user_id,
				'post_type' => 'event_organizer',
				'meta_key' => '_organizer_email',
				'meta_value' => $user_email,
			);
			$organizer_post = get_posts($post_arr);
			if (is_array($organizer_post) && isset($organizer_post[0])) {
				$arr = array($organizer_post[0]->ID);
				update_post_meta($post_id, '_event_organizer_ids', $arr);
				//	$_POST['_event_organizer_ids'] = $arr;
			} else {
				$user_nickname = get_user_meta($user_id, 'nickname', true);
				if (filter_var($user_nickname, FILTER_VALIDATE_EMAIL)) {
					$user_info = new WP_User($user_id);
					if ($user_info->last_name) {
						$user_nickname = $user_info->first_name . ' ' . $user_info->last_name;
					} elseif ($user_info->first_name) {
						$user_nickname = $user_info->first_name;
					}
				}
				// Create post object
				$my_post = array(
					'post_title' => wp_strip_all_tags($user_nickname),
					'post_status' => 'publish',
					'post_author' => $user_id,
					'post_type' => 'event_organizer',
				);

				// Insert the post into the database
				$organizer_post_id = wp_insert_post($my_post);
				if ($organizer_post_id) {
					update_post_meta($organizer_post_id, '_organizer_email', $user_email);
					update_post_meta($organizer_post_id, '_organizer_name', wp_strip_all_tags($user_nickname));
					$arr = array($organizer_post_id);
					update_post_meta($post_id, '_event_organizer_ids', $arr);
					//	$_POST['_event_organizer_ids'] = $arr;
				}
			}
			// added as Organizer under WP Event Manager - finishes
			// Registration Email and Organizer Email pre-filled
			if (!get_post_meta($post_id, '_event_registration_email', true) || (get_post_meta($post_id, '_event_registration_email', true) && get_post_meta($post_id, '_event_registration_email', true) != $user_email)) {
				update_post_meta($post_id, '_event_registration_email', $user_email);
				//$_POST['_event_registration_email'] = $user_email;
			}
			if (!get_post_meta($post_id, '_registration', true) || (get_post_meta($post_id, '_registration', true) && get_post_meta($post_id, '_registration', true) != $user_email)) {
				update_post_meta($post_id, '_registration', $user_email);
				//$_POST['_registration'] = $user_email;
			}
			// event expiry date
			$end_date = get_post_meta($post_id, '_event_end_date', true);
			if ($end_date) {
				$expiry_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
				if (strtotime(get_post_meta($post_id, '_event_expiry_date', true)) != strtotime($expiry_date)) {
					update_post_meta($post_id, '_event_expiry_date', $expiry_date);
					//	$_POST['_event_expiry_date'] = $expiry_date;
				}
			}
			update_post_meta($post_id, '_event_online', 'no');
			//$_POST['_event_online'] = 'no';
		}


		// co-host
		if (!isset($_POST['event_organizer_ids']) && empty($_POST['unregistered_organizer_email'])) {
			return;
		}

		$cohost_ids = isset($_POST['event_organizer_ids']) ? (array) $_POST['event_organizer_ids'] : [];
		$unregistered_emails = isset($_POST['unregistered_organizer_email']) ? sanitize_text_field($_POST['unregistered_organizer_email']) : '';

		// Process registered co-hosts
		foreach ($cohost_ids as $cohost_id) {
			$user_info = get_userdata($cohost_id);
			if ($user_info) {
				$email = $user_info->user_email;
				$subject = "You have been invited as a Co-Host";
				$message = "Hello " . esc_html($user_info->display_name) . ",<br><br>"
					. "You have been invited to be a Co-Host for an event. Click below to accept or deny:<br>"
					. "<a href='" . site_url("/accept-cohost?event_id=$event_id&user_id=$cohost_id") . "'>Accept</a> | "
					. "<a href='" . site_url("/deny-cohost?event_id=$event_id&user_id=$cohost_id") . "'>Deny</a>";
				$headers = ['Content-Type: text/html; charset=UTF-8'];
				wp_mail($email, $subject, $message, $headers);
			}
		}

		// Process unregistered emails (allow multiple emails separated by commas)
		if (!empty($unregistered_emails)) {
			$emails = array_map('trim', explode(',', $unregistered_emails)); // Split and trim emails

			foreach ($emails as $email) {
				if (is_email($email)) { // Validate each email
					$subject = "You have been invited as a Co-Host";
					$message = "Hello,<br><br>"
						. "You have been invited to be a Co-Host for an event. Please register first and accept the invitation:<br>"
						. "<a href='" . site_url("/register?event_id=$event_id&email=$email") . "'>Register & Accept</a>";
					$headers = ['Content-Type: text/html; charset=UTF-8'];
					wp_mail($email, $subject, $message, $headers);
				}
			}
		}
	}

	/*
	 ** Validate zipcode
	 */
	public function volunteer_alert_form_validate_fields($alert_data)
	{
		if (isset($_POST['alert_location'])) {
			$zipcode_arr = google_maps_geocoder($_POST['alert_location']);
			if (!(is_array($zipcode_arr) && isset($zipcode_arr['zipcode']) && !empty($zipcode_arr['zipcode']) && isset($zipcode_arr['country_short']) && trim($zipcode_arr['country_short']) == 'US')) {
				throw new Exception(__('Invalid Zipcode', 'wp-event-manager-alerts'));
			}
		} else {
			throw new Exception(__('Location required', 'wp-event-manager-alerts'));
		}
		return $alert_data;

	}


	// function to create the DB / Options / Defaults	

	// run the install scripts upon plugin activation

	public function log_table__install()
	{

		global $wpdb;// wordpress predefined class



		/** Create Table For Event Alert Deactivate Log **/

		$ev_log_table_name = $wpdb->prefix . 'wpevents_alert_deactivate_log';



		$ev_log_charset_collate = $wpdb->get_charset_collate();

		$ev_log_sql = "CREATE TABLE IF NOT EXISTS $ev_log_table_name (

			id bigint NOT NULL AUTO_INCREMENT,

			user_id bigint DEFAULT NULL,

			alert_id bigint DEFAULT NULL,

			token varchar(255) DEFAULT NULL,

			status tinyint(1) NOT NULL DEFAULT 0,

			log_status tinyint(1) NOT NULL DEFAULT 0,

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

			PRIMARY KEY  (id)

		) $ev_log_charset_collate;";



		// if changes it will update table

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($ev_log_sql);

		// create a version. Update vesion if necessary

		/** Create Table For Event Alert Log **/

		$ev_alert_log_table_name = $wpdb->prefix . 'wpevents_alert_blast_log';

		$ev_alert_log_sql = "CREATE TABLE IF NOT EXISTS $ev_alert_log_table_name (

			id bigint NOT NULL AUTO_INCREMENT,

			alert_id bigint DEFAULT NULL,

			status varchar(255) DEFAULT NULL,

			reason varchar(255) DEFAULT NULL,

			log_date DATETIME DEFAULT NULL ,

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			PRIMARY KEY  (id)

		) $ev_log_charset_collate;";


		dbDelta($ev_alert_log_sql);

		// create a version. Update vesion if necessary

		/** Create Table For Event Alert Log **/

		$ev_user_activity_log_table_name = $wpdb->prefix . 'volunteer_user_activity_logs';

		$ev_user_activity_log_sql = "CREATE TABLE IF NOT EXISTS $ev_user_activity_log_table_name (

			ID bigint NOT NULL AUTO_INCREMENT,

			a_type varchar(255) DEFAULT NULL,

			activity text DEFAULT NULL,

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			PRIMARY KEY  (ID)

		) $ev_log_charset_collate;";


		dbDelta($ev_user_activity_log_sql);

		/** Create Table For Event Alert Log **/

		$cancel_registration_table_name = $wpdb->prefix . 'wpevents_cancel_registration_log';

		$cancel_registration_sql = "CREATE TABLE IF NOT EXISTS $cancel_registration_table_name (

			ID bigint NOT NULL AUTO_INCREMENT,

			event_id bigint DEFAULT NULL,

			registration_id bigint DEFAULT NULL,

			token varchar(255) DEFAULT NULL,

			status tinyint(1) NOT NULL DEFAULT 0,

			log_status tinyint(1) NOT NULL DEFAULT 0,

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

			PRIMARY KEY  (id)

		) $ev_log_charset_collate;";

		dbDelta($cancel_registration_sql);

	}

}
WPEM_VOLUNTEER::instance();


add_action('admin_footer', 'volunteer_admin_custom_js');
function volunteer_admin_custom_js()
{
	?>
	<script>
		jQuery(document).ready(function () {
			var run = false;
			jQuery(document).ajaxSend(function (evt, request, settings) {
				url = settings.url;
				substring = 'section_provider=volunteer_subscriber_log';
				if (url.indexOf(substring) !== -1) {
					run = true;
				}
			});

			jQuery(document).on("ajaxStop", function () {
				if (run) {
					var elementExists = document.getElementById("volunteer_user_log_datatable");
					if (elementExists) {
						jQuery("#volunteer_user_log_datatable").dataTable().fnDestroy();
					}
					setTimeout(() => {
						jQuery("#volunteer_user_log_datatable").DataTable({
							responsive: true,
							dom: "Bfrtip",
							buttons: [
								"csv",
							]
						});
					}, 500);
					run = false;
				}
			});
		});
	</script>
	<style>
		.widefat .column-event_actions a.button.button-icon.tips.icon-duplicate_event:before {
			content: "\f105";
			font-family: 'dashicons' !important;
		}
	</style>
	<?php
}

// restrict woocommerce to update user fields
add_filter('woocommerce_checkout_update_customer_data', 'volunteer_woocommerce_checkout_update_customer_data', 99);
function volunteer_woocommerce_checkout_update_customer_data()
{
	return false;
}

// 
/*
 ** Tag existing people who have weekly alerts with 'Weekly Alert' in FluentCRM
 */
/*add_action('rest_api_init', 
function () {
	register_rest_route(
		'volunteer-cron/v1', '/bulk-weekly-alert-tag',
		array(
		  'methods'  => 'GET',
		  'callback' => 'volunteer_add_weekly_alert_tag',
		  'permission_callback' => '__return_true',
		)
	);

});*/
function volunteer_add_weekly_alert_tag()
{
	global $wpdb;
	$prefix = $wpdb->prefix;
	$result = $wpdb->get_results("SELECT DISTINCT u.ID,u.user_email FROM `" . $prefix . "users` as u join `" . $prefix . "posts` as p on u.ID = p.post_author WHERE p.post_status = 'publish' and p.post_type = 'event_alert'", ARRAY_A);
	if ($result) {
		$tagIntance = FluentCrmApi('tags')->getInstance();
		$weekly_tags = $tagIntance->where('slug', 'weekly-alert')->get();
		$weekly_tag_id = null;
		if ($weekly_tags) {
			foreach ($weekly_tags as $tag) {
				if ($tag->id) {
					$weekly_tag_id = $tag->id;
				}
			}
		}
		print_R($result);
		exit;
		if ($weekly_tag_id) {
			foreach ($result as $res) {
				$email = $res['user_email'];
				$subscriber = FluentCrmApi('contacts')->getContact($email);
				if ($subscriber) {
					$arr = array();
					if ($weekly_tag_id) {
						$arr[] = $weekly_tag_id;
					}
					$subscriber->attachTags($arr);
				}
			}
		}
	}
}

function enqueue_cohost_scripts()
{
	wp_enqueue_style('select2-css', plugin_dir_url(__FILE__) . 'assets/css/select2.min.css');
	wp_enqueue_script('select2-js', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', ['jquery'], null, true);
	wp_enqueue_script('cohost-admin-js', plugin_dir_url(__FILE__) . 'assets/js/cohost-admin.js', ['jquery', 'select2-js'], '1.0', true);
}

// Hook the function outside
add_action('admin_enqueue_scripts', 'enqueue_cohost_scripts');
add_action('wp_enqueue_scripts', 'enqueue_cohost_scripts');

?>