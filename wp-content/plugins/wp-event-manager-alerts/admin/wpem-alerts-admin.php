<?php
/*
* This file use for setings at admin site for event alerts settings.
*/
if(! defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WPEM_Alerts_Admin class.
 */
class WPEM_Alerts_Admin {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct(){		
		add_filter('wp_event_manager_shortcode_plugin', array($this, 'add_alert_shortcode_plugin_list'));
		add_action('wp_event_manager_shortcode_list', array($this, 'add_alert_shortcode_list'));
	}

	/**
	 * add_alert_shortcode_plugin_list function.
	 *
	 * @access public
	 * @return array
	 * @since 1.6.18
	 */
	public function add_alert_shortcode_plugin_list($shortcode_plugins) {
		$shortcode_plugins['wp-event-manager-alerts'] =  __('WP Event Manager Alerts', 'wp-event-manager-alerts');
		return $shortcode_plugins;
	}

	/**
	 * add_alert_shortcode_list function.
	 *
	 * @access public
	 * @return void
	 * @since 1.6.18
	 */
	public function add_alert_shortcode_list($detail_link) { ?>
		<tr class="shortcode_list wp-event-manager-alerts">
			<td class="wpem-shortcode-td">[event_alerts]</td>
			<td><?php _e('Event Alert List', 'wp-event-manager-alerts');?></td>
			<td><?php _e('This will return alert list of current logged-in user.', 'wp-event-manager-alerts');?></td>
			<td><a class="button add-field" href="<?php echo $detail_link.'event-alerts/#articleTOC_4';?>" target="_blank"><?php _e('View Details', 'wp-event-manager-alerts');?></a></td>
		</tr>
	<?php
	}

}
new WPEM_Alerts_Admin();