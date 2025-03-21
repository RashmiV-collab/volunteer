<?php
/**
 * This file is use to create a sortcode of wp food manager plugin. 
 * This file include sortcode of alert list etc.
 */

if(! defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * WPEM_Alerts_Shortcodes class.
 */
class WPEM_Alerts_Shortcodes {
	private $alert_message = '';
	private $action = '';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action('wp', array($this, 'shortcode_action_handler'));
		add_shortcode('event_alerts', array($this, 'event_alerts'));
		$this->action = isset($_REQUEST['action']) ? sanitize_title($_REQUEST['action']) : '';

		add_filter('wpem_dashboard_menu', array($this, 'wpem_dashboard_menu_add'));
		add_action('event_manager_event_dashboard_content_wpem_alerts', array($this, 'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_alerts_edit', array($this, 'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_add_alert', array($this, 'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_view', array($this, 'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_email', array($this, 'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_toggle_status', array($this,'wpem_dashboard_content_add'));
		add_action('event_manager_event_dashboard_content_delete', array($this, 'wpem_dashboard_content_add'));
	}

	/**
	 * add dashboard menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpem_dashboard_menu_add($menus) {
		$menus['wpem_alerts'] = [
						'title' => __('Alerts', 'wp-event-manager-alerts'),
						'icon' => 'wpem-icon-bell',
						'query_arg' => ['action' => 'wpem_alerts'],
					];
		return $menus;
	}

	/**
	 * add dashboard content function.
	 */
	public function wpem_dashboard_content_add(){
        echo do_shortcode('[event_alerts]');
	}

	/**
	 * Handle actions which need to be run before the shortcode e.g. post actions
	 */
	public function shortcode_action_handler() {
		global $post;
		if(is_page() && strstr($post->post_content, '[event_alerts')) {
			wp_enqueue_style('chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css');
			$this->event_alerts_handler();
		} elseif(is_page() && strstr($post->post_content, '[event_dashboard')) {
            wp_enqueue_style('chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css');
			$this->event_alerts_handler();
        }
	}

	/**
	 * Handles actions
	 */
	public function event_alerts_handler() {
		if(! empty($_REQUEST['action']) && ! empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'event_manager_alert_actions')) {
			try {
				switch ($this->action) {
					case 'add_alert' :
					case 'alerts_edit' :
						if(isset($_POST['submit-event-alert'])) {
							$alert_name      = sanitize_text_field($_POST['alert_name']);
							$alert_keyword   = sanitize_text_field($_POST['alert_keyword']);
							$alert_location  = sanitize_text_field($_POST['alert_location']);
							$alert_frequency = sanitize_text_field($_POST['alert_frequency']);

							if(empty($alert_name)) {
								throw new Exception(__('Please name your alert.', 'wp-event-manager-alerts'));
							}
							if(! is_admin()) {
								require_once(ABSPATH . 'wp-admin/includes/post.php');
							}

							if($this->action == 'add_alert') {
								$found_post = post_exists($alert_name, '', '', 'event_alert');
								if($found_post > 0) {
									throw new Exception(__('Alert name is already exist. Please choose another name for your alert.', 'wp-event-manager-alerts'));
								}

								$alert_data = array(
									'post_title'     => $alert_name,
									'post_status'    => 'publish',
									'post_type'      => 'event_alert',
									'comment_status' => 'closed',
									'post_author'    => get_current_user_id()
								);
								// Validation hook
								$alert_data = apply_filters('alert_form_validate_fields', $alert_data);
								if($alert_data){
									$alert_id = wp_insert_post($alert_data);
									$alert_message =  sprintf(__('Your %s alert has been added.', 'wp-event-manager-alerts'), stripslashes($alert_name));
								}else{
									throw new Exception( __('Could not create the alert.', 'wp-event-manager-alerts'));
								}
								
							} else {
								
								$alert_id = absint($_REQUEST['alert_id']);
								$alert    = get_post($alert_id);

								// Check ownership
								if($alert->post_author != get_current_user_id())
									throw new Exception(__('Invalid Alert.', 'wp-event-manager-alerts'));

								$update_alert = array();
								$update_alert['ID'] = $alert_id;
								$update_alert['post_title'] = $alert_name;
								$alert_message =  sprintf(__('Your %s alert has been updated.', 'wp-event-manager-alerts'),	stripslashes($alert_name)	);
								$update_alert = apply_filters('alert_form_validate_alert_fields', $update_alert);
								wp_update_post($update_alert);

							}
							if(taxonomy_exists('event_listing_category')) {
								$alert_cats = isset($_POST['alert_cats']) ? array_map('absint', $_POST['alert_cats']) : '';
								wp_set_object_terms($alert_id, $alert_cats, 'event_listing_category');
							}
							if(taxonomy_exists('event_listing_tag')) {
							    $alert_tags = isset($_POST['alert_tags']) ? array_map('absint', $_POST['alert_tags']) : '';
							    wp_set_object_terms($alert_id, $alert_tags, 'event_listing_tag');
							}
							if(taxonomy_exists('event_listing_type')) {
								$alert_event_type = isset($_POST['alert_event_type']) ? array_map('sanitize_title', $_POST['alert_event_type']) : '';
								wp_set_post_terms($alert_id, $alert_event_type, 'event_listing_type');
							}  
							update_post_meta($alert_id, '_alert_frequency', $alert_frequency);
							update_post_meta($alert_id, '_alert_keyword', $alert_keyword);
							update_post_meta($alert_id, '_alert_location', $alert_location);

							wp_clear_scheduled_hook('event-manager-alert', array($alert_id));
						
							// Schedule new alert
							switch ($alert_frequency) {
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
							// Create cron
							wp_schedule_event($next, $alert_frequency, 'event-manager-alert', array($alert_id));
							if($this->action == 'add_alert'){
								wp_redirect(add_query_arg(array(
									'action' => 'wpem_alerts',
									'operation' => 'added',
								), remove_query_arg(array('action', 'alert_id'))));
								exit;
							}else{
								wp_redirect(add_query_arg(array(
									'action' => 'wpem_alerts',
									'operation' => 'updated',
								), remove_query_arg(array('action', 'alert_id'))));
								exit;
							}
						}
					break;
					case 'toggle_status' :
						$alert_id = absint($_REQUEST['alert_id']);
						$alert    = get_post($alert_id);

						// Check ownership
						if($alert->post_author != get_current_user_id())
							throw new Exception(__('Invalid Alert', 'wp-event-manager-alerts'));

						// Handle cron
						wp_clear_scheduled_hook('event-manager-alert', array($alert_id));
						if($alert->post_status == 'draft') {
							// Schedule new alert
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
							// Create cron
							wp_schedule_event($next, $alert->alert_frequency, 'event-manager-alert', array($alert_id));
						}						

						$update_alert = array();
						$update_alert['ID'] = $alert_id;
						$update_alert['post_status'] = $alert->post_status == 'publish' ? 'draft' : 'publish';
						$update_alert = apply_filters('alert_form_validate_toggle_status', $update_alert);
								
						wp_update_post($update_alert);

						// Message
						$this->alert_message = '<div class="event-manager-message wpem-alert wpem-alert-success">' . sprintf(__('%s has been %s', 'wp-event-manager-alerts'), stripslashes($alert->post_title), $alert->post_status == 'draft' ? __('Enabled', 'wp-event-manager-alerts') : __('Disabled', 'wp-event-manager-alerts')) . '</div>';
						break;
					case 'delete' :
						$alert_id = absint($_REQUEST['alert_id']);
						$alert    = get_post($alert_id);

						// Check ownership
						if($alert->post_author != get_current_user_id())
							throw new Exception(__('Invalid Alert.', 'wp-event-manager-alerts'));

						if('trash' === get_post_status($alert_id)) {
							$arr_params = array('action', 'alert_id', 'delete');
							wp_safe_redirect(remove_query_arg($arr_params));
							exit;
						} else {
							$alert_id = apply_filters('alert_form_validate_delete_status', $alert_id);
							// Trash it
							wp_delete_post($alert_id, true);

							// Message
							$this->alert_message = '<div class="event-manager-message wpem-alert wpem-alert-danger">' . sprintf(__('Your %s have been deleted', 'wp-event-manager-alerts'), stripslashes($alert->post_title)) . '</div>';
						}
												
					break;
					case 'email' :
						$alert_id = absint($_REQUEST['alert_id']);
						$alert    = get_post($alert_id);
						do_action('event-manager-alert', $alert_id, true);
						$this->alert_message = '<div class="event-manager-message wpem-alert wpem-alert-success">' . sprintf(__('%s has been triggered. Check your mail.', 'wp-event-manager-alerts'), stripslashes($alert->post_title)) . '</div>';
					break;
				}
			} catch (Exception $e) {
				$this->alert_message = '<div class="event-manager-error wpem-alert wpem-alert-danger">' . $e->getMessage() . '</div>';
			}
		}
	}

	/**
	 * Shortcode for the alerts page
	 */
	public function event_alerts($atts) {
		global $event_manager;
		
		extract(shortcode_atts(array(
			'posts_per_page' => '10',
		), $atts));

		ob_start();

		if(! is_user_logged_in()) {
			do_action('wpem_event_alerts_list_non_loggedin_user_before');
			?>
			<div class="wpem-alert wpem-alert-info"><?php _e('You need to be signed in to manage your alerts.', 'wp-event-manager-alerts'); ?>
		       	<a href="<?php echo apply_filters('event_manager_event_dashboard_login_url', get_option('event_manager_login_page_url',wp_login_url())); ?>"><?php _e('Sign in', 'wp-event-manager-alerts'); ?></a>
		    </div>
			<?php 
			do_action('wpem_event_alerts_list_non_loggedin_user_after');
			return ob_get_clean();
		}

		wp_enqueue_script('event-alerts');
		ob_start();

		if(isset($_GET['operation']) && ! empty($_GET['operation']) && $_GET['operation'] == 'updated')
			echo '<div class="event-manager-message wpem-alert wpem-alert-success">' . __('Your alerts have been updated.', 'wp-event-manager-alerts') . '</div>';
		elseif(isset($_GET['operation']) && ! empty($_GET['operation']) && $_GET['operation'] == 'added')
			echo '<div class="event-manager-message wpem-alert wpem-alert-success">' . __('Your alerts have been added successfully.', 'wp-event-manager-alerts') . '</div>';
		else
			echo $this->alert_message;
		// If doing an action, show conditional content if needed....
		if(! empty($this->action)) {
			$alert_id = isset($_REQUEST['alert_id']) ? absint($_REQUEST['alert_id']) : '';

			switch ($this->action) {
                            
				case 'add_alert' :
					$this->add_alert();
					return;
				case 'alerts_edit' :
					$this->edit_alert($alert_id);
                                    
					return;
				case 'view' :
					$this->view_results($alert_id);
					return;
			}
		}

		$args = apply_filters('event_manager_get_dashboard_events_args', array(
			'post_type'           => 'event_alert',
			'post_status'         => array('publish', 'draft'),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'offset'              => (max(1, get_query_var('paged')) - 1) * $posts_per_page,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'author'              => get_current_user_id()
		));

		$alerts = new WP_Query($args);
		$user   = wp_get_current_user();
		do_action('wpem_event_alerts_list_defore');
		get_event_manager_template('my-alerts.php', array('alerts' => $alerts, 'user' => $user, 'max_num_pages' => $alerts->max_num_pages), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
		do_action('wpem_event_alerts_list_after');
		return ob_get_clean();
	}

	/**
	 * Add alert form
	 */
	public function add_alert() {
		do_action('event_manager_alert_add_form_start');
		get_event_manager_template('alert-form.php', array(
			'alert_id'        => '',
			'alert_name'      => isset($_REQUEST['alert_name']) ? sanitize_text_field(stripslashes($_REQUEST['alert_name'])) : '',
			'alert_keyword'   => isset($_REQUEST['alert_keyword']) ? sanitize_text_field(stripslashes($_REQUEST['alert_keyword'])) : '',
			'alert_location'  => isset($_REQUEST['alert_location']) ? sanitize_text_field(stripslashes($_REQUEST['alert_location'])) : '',
			'alert_frequency' => isset($_REQUEST['alert_frequency']) ? sanitize_text_field(stripslashes($_REQUEST['alert_frequency'])) : '',
			'alert_cats'      => isset($_REQUEST['alert_cats']) ? array_map('absint', $_REQUEST['alert_cats']) : array(),
			'alert_tags'      => isset($_REQUEST['alert_tags']) ? array_filter(array_map('absint', (array) $_REQUEST['alert_tags'])) : array(),
			'alert_event_type'  => isset($_REQUEST['alert_event_type']) ? array_map('sanitize_title', $_REQUEST['alert_event_type']) : array()
		), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
		do_action('event_manager_alert_add_form_end');
	}

	/**
	 * Edit alert form
	 */
	public function edit_alert($alert_id) {
		$alert = get_post($alert_id);
		if($alert->post_author != get_current_user_id())
			return;

		$alert_keyword = isset($alert->_alert_keyword) ? stripslashes($alert->_alert_keyword) : stripslashes($alert->alert_keyword);
		$alert_location = isset($alert->_alert_location) ? stripslashes($alert->_alert_location) : stripslashes($alert->alert_location);
		$alert_frequency = isset($alert->_alert_frequency) ? stripslashes($alert->_alert_frequency) : stripslashes($alert->alert_frequency);
			
		do_action('event_manager_alert_update_form_start', $alert);
		get_event_manager_template('alert-form.php', array(
			'alert_id'        => $alert_id,
			'alert_name'      => isset($_POST['alert_name']) ? sanitize_text_field(stripslashes($_POST['alert_name'])) : stripslashes($alert->post_title),
			'alert_keyword'   => isset($_POST['alert_keyword']) ? sanitize_text_field(stripslashes($_POST['alert_keyword'])) : $alert_keyword,
			'alert_location'  => isset($_POST['alert_location']) ? sanitize_text_field(stripslashes($_POST['alert_location'])) : $alert_location,
			'alert_frequency' => isset($_POST['alert_frequency']) ? sanitize_text_field(stripslashes($_POST['alert_frequency'])) : $alert_frequency,
			'alert_cats'      => isset($_POST['alert_cats']) ? array_map('absint', $_POST['alert_cats']) : wp_get_post_terms($alert_id, 'event_listing_category', array('fields' => 'ids')),
			'alert_tags'      => isset($_POST['alert_tags']) ? array_map('absint', $_POST['alert_tags']) : wp_get_post_terms($alert_id, 'event_listing_tag', array('fields' => 'ids')),
			'alert_event_type'  => isset($_POST['alert_event_type']) ? array_map('sanitize_title', $_POST['alert_event_type']) : wp_get_post_terms($alert_id, 'event_listing_type', array('fields' => 'ids'))
		), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
		do_action('event_manager_alert_update_form_end', $alert);
	}

	/**
	 * View results
	 */
	public function view_results($alert_id) {
		$alert = get_post($alert_id);
		 if(!wp_script_is('wp-event-manager-content-event-listing', 'enqueued')) {
			wp_enqueue_script('wp-event-manager-content-event-listing'); 
		  }
		$events  = WPEM_Alerts_Notifier::get_matching_events($alert, true);
		echo '<p class="wpem-alert wpem-alert-info">'.  sprintf(__('Events matching your "%s" alert:', 'wp-event-manager-alerts'), stripslashes($alert->post_title)) .'</p>'; ?>
		
        <?php
		if($events->have_posts()) : 
			do_action('event_manager_alert_view_start', $alert, $events); ?>		    
			<div id="event-listing-view" class="wpem-main wpem-event-listings event_listings  wpem-event-listing-list-view">	
				<?php while ($events->have_posts()) : $events->the_post(); 

					get_event_manager_template_part('content', 'event_listing'); 

				endwhile; ?>

			</div>

		<?php do_action('event_manager_alert_view_end', $alert, $events); ?>
		<?php else :
			do_action('event_manager_alert_no_event_start', $alert);
			echo '<p class="wpem-alert wpem-alert-info">'. __('No events found', 'wp-event-manager-alerts').'</p>' ;
			do_action('event_manager_alert_no_event_end', $alert);
		endif;

		wp_reset_postdata();
	}
}
new WPEM_Alerts_Shortcodes();