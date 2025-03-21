<?php
/**
 * WPEM_Alerts_Install class.
 */
class WPEM_Alerts_Install {

	/**
     * install function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.2.4
     */
	public static function install(){
          // Redirect to setup screen for new installs
          if(! get_option('wpem_alerts_version')) {
               set_transient('_event_alerts_activation_redirect', 1, HOUR_IN_SECONDS);
          }
		update_option('wpem_alerts_version', WPEM_ALERTS_VERSION);
	}

	/**
     * install function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.2.4
     */
	public static function update(){

		$email_template_text = get_option('wpem_alerts_email_template');
		$email_template = str_replace('alert_expirey', 'alert_expiry', $email_template_text);
          $email_template = str_replace('<br>', '', $email_template);
          $email_template = str_replace('<p>', '', $email_template);
          $email_template = str_replace('</p>', '', $email_template);
          
		update_option('wpem_alerts_email_template', $email_template);	
		update_option('wpem_alerts_version', WPEM_ALERTS_VERSION);	
	}	
}