<?php
if(! defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WPEM_Alerts_Notifier class.
 */
class WPEM_Alerts_Notifier {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('event-manager-alert', array($this, 'event_manager_alert'), 10, 2);
		add_filter('cron_schedules', array($this, 'add_cron_schedules'));
	}

	/**
	 * Add custom cron schedules
	 * @param array $schedules
	 * @return array
	 */
	public function add_cron_schedules(array $schedules) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __('Once Weekly', 'wp-event-manager-alerts')
	 	);
	 	$schedules['fortnightly'] = array(
			'interval' => 604800 * 2,
			'display'  => __('Once every fortnight', 'wp-event-manager-alerts')
	 	);
		return $schedules;
	}

	/**
	 * Send an alert
	 */
	public function event_manager_alert($alert_id, $force = false) {
		$alert = get_post($alert_id);

		if(! $alert || $alert->post_type !== 'event_alert')
			return;

		if($alert->post_status !== 'publish' && ! $force)
			return;

		$user  = get_user_by('id', $alert->post_author);
		$events  = $this->get_matching_events($alert, $force);

		if($events->found_posts || ! get_option('wpem_alerts_matches_only') == 'no') {
			$email = $this->format_email($alert, $user, $events);
			$headers =  apply_filters("create_event_alert_notification_header", get_wpem_email_headers($alert_id), $alert_id);
			if($email) {
				wp_mail($user->user_email, apply_filters('event_manager_alerts_subject', sprintf(__('Event Alert Results Matching "%s"', 'wp-event-manager-alerts'), $alert->post_title), $alert), $email,$headers);
			}
		}

		if(($days_to_disable = get_option('wpem_alerts_auto_disable')) > 0) {
			$days = (strtotime('NOW') - strtotime($alert->post_modified)) / (60 * 60 * 24);
			if($days > $days_to_disable) {
				$update_alert = array();
				$update_alert['ID'] = $alert->ID;
				$update_alert['post_status'] = 'draft';
				wp_update_post($update_alert);
				wp_clear_scheduled_hook('event-manager-alert', array($alert->ID));
				return;
			}
		}
		// Inc sent count
		update_post_meta($alert->ID, '_alert_send_count', 1 + absint(get_post_meta($alert->ID, '_alert_send_count', true)));
	}

	/**
	 * Match events to an alert
	 */
	public static function get_matching_events($alert, $force) {
		if(method_exists(__CLASS__, 'filter_' . $alert->alert_frequency) && ! $force)
			add_filter('posts_where', array(__CLASS__, 'filter_' . $alert->alert_frequency));

		if(taxonomy_exists('event_listing_category')) {
			$cats  = array_filter((array) wp_get_post_terms($alert->ID, 'event_listing_category', array('fields' => 'slugs')));
		} else {
			$cats = '';
		}

		$types = array_filter((array) wp_get_post_terms($alert->ID, 'event_listing_type', array('fields' => 'slugs')));
      
		$args = apply_filters('event_manager_alerts_get_event_listings_args', array(
			'search_location'   => $alert->alert_location,
			'search_keywords'   => $alert->alert_keyword,
			'search_categories' => sizeof($cats) > 0 ? $cats : '',
			'search_event_types' => sizeof($types) > 0 ? $types : '',
			'orderby'           => 'date',
			'order'             => 'desc',
			'offset'            => 0,
			'posts_per_page'    => 50
		));

		$events = get_event_listings($args);
    
		if(method_exists(__CLASS__, 'filter_' . $alert->alert_frequency) && ! $force) {
			remove_filter('posts_where', array(__CLASS__, 'filter_' . $alert->alert_frequency));
		}

		return $events;
	}

	/**
	 * Filter posts from the last day
	 */
	public static function filter_daily($where = '') {
		$where .= " AND post_date >= '" . date('Y-m-d', strtotime('-1 days')) . "' ";
		return $where;
	}

	/**
	 * Filter posts from the last week
	 */
	public static function filter_weekly($where = '') {
		$where .= " AND post_date >= '" . date('Y-m-d', strtotime('-1 week')) . "' ";
		return $where;
	}

	/**
	 * Filter posts from the last 2 weeks
	 */
	public static function filter_fortnightly($where = '') {
		$where .= " AND post_date >= '" . date('Y-m-d', strtotime('-2 weeks')) . "' ";
		return $where;
	}

	/**
	 * Format the email
	 */
	public function format_email($alert, $user, $events) {

		$template = get_option('wpem_alerts_email_template');

		if(! $template) {
			$template = $GLOBALS['event_manager_alerts']->get_default_email();
		}

		if($events && $events->have_posts()) {
			ob_start();
			while ($events->have_posts()) {
				$events->the_post();

				get_event_manager_template('content-email_event_listing.php', array(), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
			} 
			wp_reset_postdata();
			$event_content = ob_get_clean();
		} else {
			$event_content = __('No events were found matching your search. Login to your account to change your alert criteria', 'wp-event-manager-alerts');
		}

		// Reschedule next alert
		switch ($alert->alert_frequency) {
			case 'daily' :
				$next = strtotime('+1 day');
			break;
			case 'fortnightly' :
				$next = strtotime('+2 week');
			break;
			default :
				$next = strtotime('+1 week');
			break;
		}

		if(get_option('wpem_alerts_auto_disable') > 0) {
			$alert_expiry = sprintf(__('This event alert will automatically stop sending after %s.', 'wp-event-manager-alerts'), date_i18n(get_option('date_format'), strtotime('+' . absint(get_option('wpem_alerts_auto_disable')) . ' days', strtotime($alert->post_modified))));
		} else {
			$alert_expiry = '';
		}

		$replacements = array(
			'{display_name}'    => $user->display_name,
			'{alert_name}'      => $alert->post_title,
			'{alert_expiry}'   => $alert_expiry,
			'{alert_next_date}' => date_i18n(get_option('date_format'), $next),
			'{alert_page_url}'  => !empty(get_option('wpem_alerts_page_id')) ? get_the_permalink(get_option('wpem_alerts_page_id')) : '',
			'{events}'            => $event_content
		);

		$template = str_replace(array_keys($replacements), array_values($replacements), $template);

		return apply_filters('event_manager_alerts_template', $template);
	}
}
new WPEM_Alerts_Notifier();