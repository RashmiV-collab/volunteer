<?php

if (!class_exists('WP_Sheet_Editor_Users_EDD')) {

	class WP_Sheet_Editor_Users_EDD {

		static private $instance = false;
		var $licenses_count_key = null;

		private function __construct() {
			
		}

		function sync_customer_lookup_table($user_id, $values) {
			global $wpdb;

			$email = $values['user_email'];
			$lookup_table_name = $wpdb->prefix . 'edd_customers';
			if (!empty($values['wpse_status']) && $values['wpse_status'] === 'delete') {

				$customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $lookup_table_name WHERE email = %s", $email));
				$wpdb->delete($lookup_table_name, array(
					'id' => $customer_id
				));
				$wpdb->delete($wpdb->prefix . 'edd_customermeta', array(
					'customer_id' => $customer_id
				));

				if ($user_id) {
					$payment_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type = 'edd_payment' ", $user_id));
					foreach ($payment_ids as $payment_id) {
						wp_delete_post($payment_id, true);
					}
				}
			}
		}

		function init() {
			if (!class_exists('Easy_Digital_Downloads')) {
				return;
			}
			add_action('vg_sheet_editor/provider/user/data_updated', array($this, 'sync_customer_lookup_table'), 10, 2);

			if (class_exists('EDD_Software_Licensing')) {
				$this->licenses_count_key = __('Licenses count', vgse_users()->textname);
				add_filter('vg_sheet_editor/advanced_filters/all_fields_groups', array($this, 'add_licenses_count_filter'), 10, 2);
				add_action('pre_user_query', array($this, 'filter_users_query_by_licenses_count'));
			}
		}

		function filter_users_query_by_licenses_count($wp_users_query) {
			global $wpdb;
			if (empty($wp_users_query->query_vars['wpse_original_filters']) || empty($wp_users_query->query_vars['wpse_original_filters']['meta_query'])) {
				return;
			}
			$user_data_filters = wp_list_filter($wp_users_query->query_vars['wpse_original_filters']['meta_query'], array(
				'key' => $this->licenses_count_key
			));
			if (empty($user_data_filters)) {
				return;
			}

			foreach ($user_data_filters as $index => $meta_query) {
				global $wpdb;
				if (!in_array($meta_query['compare'], array('=', '!=', '>', '>=', '<', '<='))) {
					$meta_query['compare'] = '=';
				}
				$sql = "SELECT ID FROM (SELECT u.ID, COUNT(user_id) as count FROM $wpdb->users u 
LEFT JOIN {$wpdb->prefix}edd_licenses l 
ON l.user_id = u.ID 
GROUP BY u.ID 
 HAVING count " . $meta_query['compare'] . " " . (int) $meta_query['value'] . ") tmp" . (int) $index;

				$wp_users_query->query_where .= " AND $wpdb->users.ID IN ($sql) ";
			}
		}

		function add_licenses_count_filter($groups, $post_type) {
			if ($post_type === 'user') {
				$groups['edd_licensing'] = array($this->licenses_count_key);
			}
			return $groups;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Users_EDD::$instance) {
				WP_Sheet_Editor_Users_EDD::$instance = new WP_Sheet_Editor_Users_EDD();
				WP_Sheet_Editor_Users_EDD::$instance->init();
			}
			return WP_Sheet_Editor_Users_EDD::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WP_Sheet_Editor_Users_EDD_Obj')) {

	function WP_Sheet_Editor_Users_EDD_Obj() {
		return WP_Sheet_Editor_Users_EDD::get_instance();
	}

}
add_action('vg_sheet_editor/initialized', 'WP_Sheet_Editor_Users_EDD_Obj');
