<?php
/*
* This file use for setings at admin site for event alerts settings.
*/
if(! defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WPEM_Alerts_Settings class.
 */
class WPEM_Alerts_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct(){		
			add_filter('event_manager_settings', array($this, 'wp_event_alerts_settings'));
			add_filter('event_manager_google_recaptcha_settings', array($this, 'google_recaptcha_settings'));
	}

	/**
	 * Return the default email content for alerts
	 * @since 1.0.0
	 */
	public function get_default_email() {
	    return apply_filters(
			'wpen_event_alert_mail_default_content', "Hello {display_name},
				The following events were found matching your \"{alert_name}\" event alert.
				----
				{events}
				Your next alert for this search will be sent {alert_next_date}. To manage your alerts please login and visit your alerts page here: {alert_page_url}.
				{alert_expiry}");
	}
	
	/**
	 * wp_event_alerts_settings function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function wp_event_alerts_settings($settings){
		if(! get_option('wpem_alerts_email_template')) {
			delete_option('wpem_alerts_email_template');
		}
		$settings['event_alerts'] = array(
			__('Event Alerts', 'wp-event-manager-alerts'),
			apply_filters(
				'wp_event_manager_alerts_settings',
				array(
					array(
						'name' 		=> 'wpem_alerts_email_template',
						'std' 		=> $this->get_default_email(),
						'label' 	=> __('Alert Email Content', 'wp-event-manager-alerts'),
						'desc'		=> __('Enter the content for your email alerts. Leave blank to use the default message. The following tags can be used to insert data dynamically:', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{display_name}</code>' . ' - ' . __('The users display name in WP.', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{alert_name}</code>' . ' - ' . __('The name of the alert being sent.', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{alert_expiry}</code>' . ' - ' . __('A sentance explaining if an alert will be stopped automatically.', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{alert_next_date}</code>' . ' - ' . __('The date this alert will next be sent.', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{alert_page_url}</code>' . ' - ' . __('The url to your alerts page.', 'wp-event-manager-alerts') . '<br/>' .
							'<code>{events}</code>' . ' - ' . __('The name of the events being sent.', 'wp-event-manager-alerts') . '<br/>' .
							'',
						'type'      => 'textarea',
						'required'  => true
					),
					array(
						'name' 		=> 'wpem_alerts_auto_disable',
						'std' 		=> '90',
						'label' 	=> __('Alert Duration', 'wp-event-manager-alerts'),
						'desc'		=> __('Enter the number of days before alerts are automatically disabled, or leave blank to disable this feature. By default, alerts will be turned off for a search after 90 days.', 'wp-event-manager-alerts'),
						'type'      => 'input'
					),
					array(
						'name' 		=> 'wpem_alerts_matches_only',
						'std' 		=> 'no',
						'label' 	=> __('Alert Matches', 'wp-event-manager-alerts'),
						'cb_label' 	=> __('Send alerts with matches only', 'wp-event-manager-alerts'),
						'desc'		=> __('Only send an alert when events are found matching its criteria. When disabled, an alert is sent regardless.', 'wp-event-manager-alerts'),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'wpem_alerts_page_id',
						'std' 		=> '',
						'label' 	=> __('Alerts Page Name', 'wp-event-manager-alerts'),
						'desc'		=> __('So that the plugin knows where to link users to view their alerts, you must select the page where you have placed the [event_alerts] shortcode.', 'wp-event-manager-alerts'),
						'type'      => 'page'
					),
					array(
						'name' 		=> 'wpem_alerts_delete_data_on_uninstall',
						'std' 		=> '0',
						'label' 	=> __('Delete Data On Uninstall', 'wp-event-manager-alerts'),
						'cb_label' 	=> __('Delete WP Event Manager Alert data when the plugin is deleted. Once removed, this data cannot be restored.', 'wp-event-manager-alerts'),
						'desc'		=> '',
						'type'      => 'checkbox'
					),
				)
			)
		);
		return $settings;
	} 

	/**
	 * wp_event_alerts_settings for google recpatcha function.
	 *
	 * @access public
	 * @return void
	 * @since 1.2.5
	 */
	public function google_recaptcha_settings($settings) {

		$settings[1][] = array(
                    'name'       => 'enable_event_manager_google_recaptcha_alert_form',
                    'std'        => '1',
                    'label'      => __('Enable reCAPTCHA for Alert Form', 'wp-event-manager-alerts'),
                    'cb_label'   => __('Disable this to remove reCAPTCHA for Alert Form.', 'wp-event-manager-alerts'),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
               );

		return $settings;
	}
}
new WPEM_Alerts_Settings();