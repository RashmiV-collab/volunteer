<?php defined( 'ABSPATH' ) || exit;

if (!class_exists('VGSE_Users_Helpers')) {

	class VGSE_Users_Helpers {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			
		}

		function get_all_the_roles() {
			$roles = wp_roles();
			return wp_list_pluck($roles->roles, 'name');
		}

		function get_available_user_roles() {
			$out = array(
				'subscriber' => __('Subscriber', vgse_users()->textname),
			);
			if (beupis_fs()->can_use_premium_code__premium_only()) {
				// If shop manager, view only subscriber and customers
				if (WP_Sheet_Editor_Helpers::current_user_can('manage_woocommerce') && !VGSE()->helpers->user_can_manage_options()) {

					// Support for the Wholesale Suite Plugin. Allow shop_managers to edit roles 
					// with have_wholesale_price who are not administrators
					$roles = wp_roles();
					foreach ($roles->roles as $role_key => $role) {
						if (!empty($role['capabilities']['have_wholesale_price']) && empty($role['capabilities']['edit_users'])) {
							$out[$role_key] = $role['name'];
						}
					}

					$out['customer'] = __('Customer', 'woocommerce');
					$out['shop_manager'] = __('Shop manager', 'woocommerce');
				} elseif (WP_Sheet_Editor_Helpers::current_user_can('edit_users')) {
					// Empty array so the sheet loads all users regardless of the role
					$out = array();
				}
			}
			return apply_filters('wpse_users_allowed_roles', $out);
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == VGSE_Users_Helpers::$instance) {
				VGSE_Users_Helpers::$instance = new VGSE_Users_Helpers();
				VGSE_Users_Helpers::$instance->init();
			}
			return VGSE_Users_Helpers::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('VGSE_Users_Helpers_Obj')) {

	function VGSE_Users_Helpers_Obj() {
		return VGSE_Users_Helpers::get_instance();
	}

}