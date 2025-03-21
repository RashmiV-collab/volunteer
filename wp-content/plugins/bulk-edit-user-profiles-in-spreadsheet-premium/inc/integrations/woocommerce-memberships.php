<?php
if (!class_exists('WP_Sheet_Editor_Users_WC_Memberships')) {

	class WP_Sheet_Editor_Users_WC_Memberships {

		static private $instance = false;
		var $memberships = array();

		private function __construct() {
			
		}

		function init() {

			if (!class_exists('WC_Memberships')) {
				return;
			}

			if (!class_exists('WooCommerce')) {
				return;
			}
			if (!beupis_fs()->can_use_premium_code__premium_only()) {
				return;
			}
			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_columns'));
			add_action('vg_sheet_editor/filters/after_fields', array($this, 'add_search_fields'));
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'apply_search_fields'), 15, 2);
			add_filter('vg_sheet_editor/options_page/options', array($this, 'add_settings_page_options'));
			add_filter('vg_sheet_editor/filters/sanitize_request_filters', array($this, 'register_custom_filters'), 10, 2);
		}

		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options($sections) {
			$sections['users']['fields'][] = array(
				'id' => 'users_wc_memberships_by_plan',
				'type' => 'switch',
				'title' => __('Show WooCommerce Membership columns grouped by plan?', VGSE()->textname),
				'desc' => __('By default, we show membership columns by the assignment order (membership 1 appears first, etc.). Activate this option to show columns for each membership plan by name.', VGSE()->textname),
				'default' => false,
			);
			return $sections;
		}

		function register_custom_filters($sanitized_filters, $dirty_filters) {

			if (isset($dirty_filters['wc_active_membership_plan'])) {
				$sanitized_filters['wc_active_membership_plan'] = array_map('intval', $dirty_filters['wc_active_membership_plan']);
			}
			return $sanitized_filters;
		}

		function apply_search_fields($query_args, $data) {
			global $wpdb;
			if ($query_args['post_type'] !== 'user') {
				return $query_args;
			}
			if (empty($data['filters'])) {
				return $query_args;
			}
			$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters($data);

			if (!empty($filters['wc_active_membership_plan'])) {
				$users_per_plan = array();
				foreach ($filters['wc_active_membership_plan'] as $active_plan_id) {
					$active_membership_sql = $wpdb->prepare("SELECT post_author FROM $wpdb->posts WHERE post_type = 'wc_user_membership' AND post_status IN ('wcm-active') AND post_parent = %d", (int) $active_plan_id);
					$users_per_plan[$active_plan_id] = $wpdb->get_col($active_membership_sql);
				}

				if (count($filters['wc_active_membership_plan']) === 1) {
					$users_with_required_active_plans = current($users_per_plan);
				} else {
					$users_with_required_active_plans = call_user_func_array('array_intersect', $users_per_plan);
				}

				if (empty($users_with_required_active_plans)) {
					$query_args['post__in'] = array(time() * 2);
				} else {
					$query_args['post__in'] = ( empty($query_args['post__in'])) ? $users_with_required_active_plans : array_intersect($query_args['post__in'], $users_with_required_active_plans);
				}
			}

			return $query_args;
		}

		function add_search_fields($post_type) {
			if ($post_type !== 'user') {
				return;
			}
			?>
			<li>
				<label><?php _e('Active membership plan', vgse_users()->textname); ?></label>
				<select name="wc_active_membership_plan[]" multiple class="select2">
					<option value=""><?php _e('Any', vgse_users()->textname); ?></option>
					<?php
					$plans = $this->get_membership_plans();
					foreach ($plans as $plan_id => $plan_name) {
						?>
						<option value="<?php echo esc_attr($plan_id); ?>"><?php echo esc_html($plan_name); ?></option>
						<?php
					}
					?>
				</select>
			</li>
			<?php
		}

		function get_membership_plans() {

			$plan_objects = wc_memberships_get_membership_plans();
			$plans = array();

			if (!empty($plan_objects)) {
				foreach ($plan_objects as $plan) {
					$plans[$plan->get_id()] = $plan->get_name();
				}
			}
			return $plans;
		}

		function register_columns($editor) {

			$post_type = 'user';

			if ($editor->provider->key !== 'user') {
				return;
			}

			$plans = $this->get_membership_plans();

			if (!empty(VGSE()->options['users_wc_memberships_by_plan'])) {
				foreach ($plans as $index => $plan_title) {
					$statuses_array = wc_memberships_get_user_membership_statuses();
					$editor->args['columns']->register_item('wc_membership_status' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 230,
						'title' => sprintf(__('Membership : %s : Status', VGSE()->textname), esc_html($plan_title)),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_status_for_cell_by_plan'),
						'save_value_callback' => array($this, 'save_membership_status_from_cell_by_plan'),
						'formatted' => array('editor' => 'select', 'selectOptions' => wp_list_pluck($statuses_array, 'label')),
					));
					$editor->args['columns']->register_item('wc_membership_start_date' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 230,
						'title' => sprintf(__('Membership : %s : Start date', VGSE()->textname), esc_html($plan_title)),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_start_date_for_cell_by_plan'),
						'save_value_callback' => array($this, 'save_membership_start_date_from_cell_by_plan'),
						'value_type' => 'date'
					));
					$editor->args['columns']->register_item('wc_membership_end_date' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 230,
						'title' => sprintf(__('Membership : %s : End date', VGSE()->textname), esc_html($plan_title)),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_end_date_for_cell_by_plan'),
						'save_value_callback' => array($this, 'save_membership_end_date_from_cell_by_plan'),
						'value_type' => 'date'
					));
				}
			} else {
				for ($index = 0; $index < count($plans); $index++) {
					$editor->args['columns']->register_item('wc_membership_name' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 170,
						'title' => sprintf(__('Membership %d : Name', VGSE()->textname), $index + 1),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_name_for_cell'),
						'save_value_callback' => array($this, 'save_membership_name_from_cell'),
						'formatted' => array('editor' => 'select', 'selectOptions' => array_combine(array_values($plans), array_values($plans))),
					));
					$statuses_array = wc_memberships_get_user_membership_statuses();
					$editor->args['columns']->register_item('wc_membership_status' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 170,
						'title' => sprintf(__('Membership %d : Status', VGSE()->textname), $index + 1),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_status_for_cell'),
						'save_value_callback' => array($this, 'save_membership_status_from_cell'),
						'formatted' => array('editor' => 'select', 'selectOptions' => wp_list_pluck($statuses_array, 'label')),
					));
					$editor->args['columns']->register_item('wc_membership_start_date' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 170,
						'title' => sprintf(__('Membership %d : Start date', VGSE()->textname), $index + 1),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_start_date_for_cell'),
						'save_value_callback' => array($this, 'save_membership_start_date_from_cell'),
						'value_type' => 'date'
					));
					$editor->args['columns']->register_item('wc_membership_end_date' . $index, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 170,
						'title' => sprintf(__('Membership %d : End date', VGSE()->textname), $index + 1),
						'type' => '',
						'supports_formulas' => true,
						'supports_sql_formulas' => false,
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
						'get_value_callback' => array($this, 'get_membership_end_date_for_cell'),
						'save_value_callback' => array($this, 'save_membership_end_date_from_cell'),
						'value_type' => 'date'
					));
				}
			}
		}

		function get_memberships_of_user($user_id, $skip_cache = false) {
			if (isset($this->memberships[$user_id]) && !$skip_cache) {
				$out = $this->memberships[$user_id];
			} else {
				$out = array_reverse(wc_memberships()->get_user_memberships_instance()->get_user_memberships($user_id));
				$this->memberships[$user_id] = $out;
			}
			return $out;
		}

		function create_membership($user_id, $plan_name) {
			$plan = get_page_by_title($plan_name, OBJECT, 'wc_membership_plan');
			if (!$plan) {
				return false;
			}
			$post_id = wp_insert_post(array(
				'post_parent' => (int) $plan->ID,
				'post_author' => (int) $user_id,
				'post_type' => 'wc_user_membership',
				'post_status' => 'wcm-active',
				'comment_status' => 'open',
			));
			return $post_id;
		}

		function save_membership_name_from_cell($user_id, $cell_key, $plan_name, $post_type, $cell_args, $spreadsheet_columns) {

			$memberships = array_values($this->get_memberships_of_user($user_id, true));
			$index = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			if ($membership_id && empty($plan_name)) {
				wp_update_post(array(
					'ID' => $membership_id,
					'post_status' => 'wcm-cancelled'
				));
				return;
			}

			if (!$membership_id) {
				$membership_id = $this->create_membership($user_id, $plan_name);
			} else {
				$plan = get_page_by_title($plan_name, OBJECT, 'wc_membership_plan');
				wp_update_post(array(
					'ID' => $membership_id,
					'post_parent' => $plan->ID
				));
			}
		}

		function save_membership_status_from_cell($user_id, $cell_key, $post_status, $post_type, $cell_args, $spreadsheet_columns) {

			$memberships = array_values($this->get_memberships_of_user($user_id, true));
			$index = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			if (!$membership_id) {
				return;
			}

			wp_update_post(array(
				'ID' => $membership_id,
				'post_status' => $post_status
			));
		}

		function save_membership_status_from_cell_by_plan($user_id, $cell_key, $post_status, $post_type, $cell_args, $spreadsheet_columns) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $user_id);

			if (!$membership_id && $post_status !== 'wcm-cancelled') {
				$plans = $this->get_membership_plans();
				$membership_id = $this->create_membership($user_id, $plans[$plan_id]);
				// Clear internal cache
				if (isset($this->memberships[$user_id])) {
					unset($this->memberships[$user_id]);
				}
			}
			if (!$membership_id) {
				return;
			}

			if (empty($post_status)) {
				wp_delete_post($membership_id);
			} else {
				wp_update_post(array(
					'ID' => $membership_id,
					'post_status' => $post_status
				));
			}
		}

		function save_membership_start_date_from_cell($user_id, $cell_key, $date, $post_type, $cell_args, $spreadsheet_columns) {

			$memberships = array_values($this->get_memberships_of_user($user_id, true));
			$index = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			if (!$membership_id) {
				return;
			}

			update_post_meta($membership_id, '_start_date', $date);
		}

		function save_membership_start_date_from_cell_by_plan($user_id, $cell_key, $date, $post_type, $cell_args, $spreadsheet_columns) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $user_id);

			if (!$membership_id) {
				return;
			}

			update_post_meta($membership_id, '_start_date', $date);
		}

		function save_membership_end_date_from_cell($user_id, $cell_key, $date, $post_type, $cell_args, $spreadsheet_columns) {

			$memberships = array_values($this->get_memberships_of_user($user_id, true));
			$index = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			if (!$membership_id) {
				return;
			}

			update_post_meta($membership_id, '_end_date', $date);
		}

		function save_membership_end_date_from_cell_by_plan($user_id, $cell_key, $date, $post_type, $cell_args, $spreadsheet_columns) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $user_id);

			if (!$membership_id) {
				return;
			}

			update_post_meta($membership_id, '_end_date', $date);
		}

		function get_membership_status_for_cell($post, $cell_key, $cell_args) {
			$memberships = array_values($this->get_memberships_of_user($post->ID));
			$index = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			$out = 'wcm-active';

			if ($membership_id) {
				$out = get_post_status($membership_id);
			}
			return $out;
		}

		function get_membership_status_for_cell_by_plan($post, $cell_key, $cell_args) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $post->ID);

			$out = '';

			if ($membership_id) {
				$out = get_post_status($membership_id);
			}
			return $out;
		}

		function get_membership_name_for_cell($post, $cell_key, $cell_args) {
			$memberships = array_values($this->get_memberships_of_user($post->ID));
			$index = preg_replace('/.+(\d+).*/', '$1', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			$out = '';

			if ($membership_id) {
				$out = $memberships[$index]->get_plan()->name;
			}
			return $out;
		}

		function get_membership_start_date_for_cell($post, $cell_key, $cell_args) {
			$memberships = array_values($this->get_memberships_of_user($post->ID));
			$index = preg_replace('/.+(\d+).*/', '$1', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			$out = '';

			if ($membership_id) {
				$out = get_post_meta($membership_id, '_start_date', true);
			}
			return $out;
		}

		function get_membership_start_date_for_cell_by_plan($post, $cell_key, $cell_args) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $post->ID);

			$out = '';

			if ($membership_id) {
				$out = get_post_meta($membership_id, '_start_date', true);
			}
			return $out;
		}

		function get_membership_end_date_for_cell($post, $cell_key, $cell_args) {
			$memberships = array_values($this->get_memberships_of_user($post->ID));
			$index = preg_replace('/.+(\d+).*/', '$1', $cell_key);
			$membership_id = isset($memberships[$index]) ? $memberships[$index]->get_id() : false;

			$out = '';

			if ($membership_id) {
				$out = get_post_meta($membership_id, '_end_date', true);
			}
			return $out;
		}

		function _get_user_membership_id_by_plan_id($plan_id, $user_id) {
			$memberships = array_values($this->get_memberships_of_user($user_id));
			$membership_id = false;
			foreach ($memberships as $membership) {
				$membership_plan_id = (int) $membership->get_plan()->get_id();
				if ($membership_plan_id === $plan_id) {
					$membership_id = $membership->get_id();
					break;
				}
			}
			return $membership_id;
		}

		function get_membership_end_date_for_cell_by_plan($post, $cell_key, $cell_args) {
			$plan_id = (int) preg_replace('/[^0-9]/', '', $cell_key);
			$membership_id = $this->_get_user_membership_id_by_plan_id($plan_id, $post->ID);
			$out = '';

			if ($membership_id) {
				$out = get_post_meta($membership_id, '_end_date', true);
			}
			return $out;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Users_WC_Memberships::$instance) {
				WP_Sheet_Editor_Users_WC_Memberships::$instance = new WP_Sheet_Editor_Users_WC_Memberships();
				WP_Sheet_Editor_Users_WC_Memberships::$instance->init();
			}
			return WP_Sheet_Editor_Users_WC_Memberships::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WP_Sheet_Editor_Users_WC_Memberships_Obj')) {

	function WP_Sheet_Editor_Users_WC_Memberships_Obj() {
		return WP_Sheet_Editor_Users_WC_Memberships::get_instance();
	}

}
add_action('vg_sheet_editor/after_init', 'WP_Sheet_Editor_Users_WC_Memberships_Obj');
