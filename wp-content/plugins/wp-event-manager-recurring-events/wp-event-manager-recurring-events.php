<?php
/**
 * Plugin Name: WP Event Manager - Event Recurring
 * Plugin URI: http://www.wp-eventmanager.com/product-category/plugins/
 * Description: Repeated events after specific time like daily, weekly, monthly or yearly.Automatically relist event.Your event will be republished after a specific time.
 * Author: WP Event Manager
 * Author URI: https://www.wp-eventmanager.com/
 * Text Domain: wp-event-manager-recurring-events
 * Domain Path: /languages
 * Version: 1.4.4
 * Since: 1.0
 * Requires WordPress Version at least: 4.1
 * Copyright: 2017 WP Event Manager
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 **/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
	
if ( ! class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

function pre_check_before_installing_recurring()
{
	/*
	 * Check weather WP Event Manager is installed or not
	 */
	if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
	{
		global $pagenow;
		if( $pagenow == 'plugins.php' ) {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'WP Event Manager is require to use Wp Event Manager - Event Recurring' , 'wp-event-manager-recurring-events');
			echo '</p></div>';
		}
		
	}
}
add_action( 'admin_notices', 'pre_check_before_installing_recurring' );
	
/**
 * WP_Event_Manager_Recurring class.
 */
class WP_Event_Manager_Recurring extends WPEM_Updater {
	
	/**
	 * __construct function.
	 */
	public function __construct() {

		//if wp event manager not active return from the plugin
		if (! is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;
		
		// Define constants
		define( 'WPEM_RECURRING_VERSION', '1.4.4' );
		define( 'WPEM_RECURRING_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_RECURRING_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		
		//forms
		include( 'forms/wpem-recurring-form-submit.php' );
		if(is_admin()){
			include( 'admin/wpem-recurring-writepanels.php');
		}
		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		
		/*validation  */
		add_filter( 'before_submit_event_form_validate_fields',array($this,'validate_recurring_fields') ,10,2 );
		
		add_filter( 'cron_schedules', array( $this,'wp_event_recurrence_add_intervals' ) );
		//update event data
		if(!get_option('event_manager_recurring_events')){
		     add_action( 'event_manager_update_event_data', array( $this,'update_event_recurrence'), 10, 2 );
		}
		
		add_action( 'event_manager_event_recurring', array( $this, 'event_manager_event_recurring' ) );

		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivate' ) );

		// Init updates
		$this->init_updates( __FILE__ );
	}
	
	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-recurring-events';
		$locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-recurring-events/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() { }
		
	/**
	 * Validate recurring fields
	 * @parma $validate , $fields , $values
	 * @return $validate
	 **/
	public function validate_recurring_fields( $fields,$values ){
		if($values['event']['event_recurrence'] == 'no' || $values['event']['event_recurrence'] == '') {
			$fields['event']['recure_every']['required'] 		= false; 
			$fields['event']['recure_time_period']['required'] 	= false; 
			$fields['event']['recure_month_day']['required'] 	= false; 
			$fields['event']['recure_weekday']['required'] 		= false; 
			$fields['event']['recure_untill']['required'] 		= false; 
		} elseif($values['event']['event_recurrence'] == 'daily'){
			$fields['event']['recure_time_period']['required'] 	= false; 
			$fields['event']['recure_month_day']['required'] 	= false; 
			$fields['event']['recure_weekday']['required'] 		= false; 
		} elseif($values['event']['event_recurrence'] == 'weekly'){
			$fields['event']['recure_time_period']['required'] 	= false; 
			$fields['event']['recure_month_day']['required'] 	= false; 
		} elseif($values['event']['event_recurrence'] == 'monthly'){
			if($values['event']['recure_time_period'] == 'same_time'){
				$fields['event']['recure_month_day']['required'] 	= false; 
				$fields['event']['recure_weekday']['required'] 		= false; 
			}	
		} elseif($values['event']['event_recurrence'] == 'yearly'){
			$fields['event']['recure_time_period']['required'] 	= false; 
			$fields['event']['recure_month_day']['required'] 	= false; 
			$fields['event']['recure_weekday']['required'] 		= false; 
		}
		return $fields;
	}
	
	/**
	 * set recurring intervals
	 **/
	function wp_event_recurrence_add_intervals( $schedules ) {
		// add a 'weekly' schedule to the existing set
		$schedules['weekly'] = array(
				'interval' => 604800,
				'display' => __('Once Weekly','wp-event-manager-recurring-events')
		);
		$schedules['monthly'] = array(
				'interval' => 2635200,
				'display' => __('Once a month','wp-event-manager-recurring-events')
		);
		$schedules['yearly'] = array(
				'interval' => 31557600,
				'display' => __('Once Yearly','wp-event-manager-recurring-events')
		);
		
		return $schedules;
	}
	
	public function update_event_recurrence( $event_id, $fields = array()){
		$end_date = get_post_meta( $event_id ,'_event_end_date',true);
		//check if timezone settings is enabled as each event then set current time stamp according to the timezone
		// for eg. if each event selected then Berlin timezone will be different then current site timezone.
		
		//if it called from event manager hook event_manager_update_event_data
		if(isset($fields['event']['event_recurrence'] ) && $fields['event']['event_recurrence'] != 'no' ) {
			$recurrece_frequency = $fields['event']['event_recurrence'];
			$recure_every = $fields['event']['recure_every'];
			$recure_weekday = $fields['event']['recure_weekday'];
			$recure_month_day = $fields['event']['recure_month_day'];
		} else{
			//if it called after cron created from  event_manager_event_recurring function
			$event = get_post( $event_id);
			$recurrece_frequency = get_post_meta( $event_id ,'_event_recurrence',true);			
			$recure_every = get_post_meta( $event_id ,'_recure_every',true);
			$recure_weekday = get_post_meta( $event_id ,'_recure_weekday',true);
			$recure_month_day = get_post_meta( $event_id ,'_recure_month_day',true);	
		}
		
		if(!empty($event_id) && !empty($recurrece_frequency)  && !empty($recure_every) && !empty($recure_weekday) && !empty($recure_month_day) ){
			wp_clear_scheduled_hook( 'event_manager_event_recurring', array( $event_id) );
			
			//get current time
			$current_timestamp = current_time( 'timestamp' ); // If site wise timezone selected
			$current_date = strtotime(date("Y-m-d H:i:s",$current_timestamp));
			$current_date = date("Y-m-d H:i:s",$current_date);
			
			$str_time =  strtotime($end_date) - strtotime($current_date);
			$diff_days = floor($str_time/3600/24);//get the timestamp from start and end date
			$next = strtotime( '+'.$diff_days.' day' );
			
			//Create cron
			wp_schedule_event( $next,$recurrece_frequency,'event_manager_event_recurring', array( $event_id ) );
		}		
	}

	/**
	 * Update event status and event date
	 */
	public function event_manager_event_recurring( $event_id ) {

		$event = get_post($event_id);
		$recure_untill = get_post_meta( $event_id, '_recure_untill',true );
		$event_timezone = get_event_timezone($event_id);
		
		//check if timezone settings is enabled as each event then set current time stamp according to the timezone
		// for eg. if each event selected then Berlin timezone will be different then current site timezone.
		if( WP_Event_Manager_Date_Time::get_event_manager_timezone_setting() == 'each_event'  )
			$current_timestamp = WP_Event_Manager_Date_Time::current_timestamp_from_event_timezone( $event_timezone );
		else
			$current_timestamp = current_time( 'timestamp' ); // If site wise timezone selected

		//get recurring fields
		$recurrece_frequency = get_post_meta( $event_id ,'_event_recurrence',true);
		$recure_every = get_post_meta( $event_id ,'_recure_every',true);
		$recure_weekday = get_post_meta( $event_id ,'_recure_weekday',true);
		$recure_month_day = get_post_meta( $event_id ,'_recure_month_day',true);
		$recure_time_period = get_post_meta( $event_id ,'_recure_time_period',true);
		$recure_untill = strtotime(get_post_meta( $event_id ,'_recure_untill',true));
		if( strtotime( $recure_untill ) < strtotime($current_timestamp) ){
			return false;
		}

		//get event start end datetime
		$start_date = get_post_meta( $event_id, '_event_start_date',true );
		$start_time = get_post_meta( $event_id, '_event_start_time',true );
		$end_date = get_post_meta( $event_id, '_event_end_date',true );
		$end_time = get_post_meta( $event_id, '_event_end_time',true );

		if(!empty($start_date) && !empty($end_date) ){
			$str_time =  strtotime($end_date) - strtotime($start_date);
			$diff_days = floor($str_time/3600/24);//get the timestamp from start and end date
			$diff_days = ' + '.$diff_days.' days';
		}
		$diff_days_deadline = '';
		if(!empty($start_date) && !empty($registration_expiry_date) ){
			$_start_date = explode(" ",$start_date);
			$str_time =  strtotime($registration_expiry_date) - strtotime($_start_date[0]);
			$diff_days_deadline = floor($str_time/3600/24);//get the timestamp from start and end date
			$diff_days_deadline = ' '.$diff_days_deadline .' days';
		}
		switch ( $recurrece_frequency ) {
			case 'daily' :
				$next = ' + '.$recure_every.' day';
				break;
			case 'weekly' :
				$next = ' + '.$recure_every.' week '.$recure_weekday;
				break;
			case 'monthly' :
				if($recure_time_period == 'specific_time'){
					$next = ' '.$recure_month_day.' '.$recure_weekday.' of + '.$recure_every.' month';
				} else{
					$next = ' + '.$recure_every.' month today';
				}
				break;
			case 'yearly' :
				$next = ' + '.$recure_every.' year';
				break;
			default :
				break;
		}

		$start_date=date('Y-m-d', strtotime($start_date. $next));
		$end_date=date('Y-m-d', strtotime($start_date. $diff_days));
		$registration_expiry_date=date('Y-m-d', strtotime($start_date. $diff_days_deadline));

		$recure_every = get_post_meta( $event_id, '_recure_every',true );
		if(!empty($recure_every)) {
			if($recure_every  < 1 ){
				return; //return without updating
			}
		}
		$current_post['ID'] = $event_id;
		$current_post['post_status'] = 'publish';
		wp_update_post($current_post);
				
		//update event meta values
		update_post_meta( $event_id, '_event_start_date',  $start_date.' '.$start_time);
		update_post_meta( $event_id, '_event_end_date',  $end_date.' '.$end_time);
		update_post_meta( $event_id, '_event_expiry_date',  $end_date);
		update_post_meta( $event_id, '_event_registration_deadline', $registration_expiry_date );
        update_post_meta( $event_id, '_recure_every', $recure_every );
        
		do_action('event_manager_event_recurring_update_data', $event_id);
		//set new occurrence for this event
		$this->update_event_recurrence( $event_id, $fields = array());
	}

	/**
	 * update plugin
	 * @parma
	 * @return
	 **/
	public function updater() { }
	
	/**
	 * Remove fields of recurring fields if plugin is deactivated
	 * @parma
	 * @return
	 **/
	public function plugin_deactivate()
	{
		$all_fields = get_option( 'event_manager_form_fields', true );
		if( is_array($all_fields) && !empty($all_fields) ) {
			$recurring_fields = array('event_recurrence','recure_every','recure_time_period','recure_month_day','recure_weekday','recure_untill');
			foreach ($recurring_fields as $value) {
				if(isset($all_fields['event'][$value]))
					unset($all_fields['event'][$value]);
			}

			update_option( 'event_manager_form_fields', $all_fields );
			update_option( 'event_manager_submit_event_form_fields', array('event' => $all_fields['event']) );
		}
	}
}

$GLOBALS['event_manager_recrring'] = new WP_Event_Manager_Recurring();
