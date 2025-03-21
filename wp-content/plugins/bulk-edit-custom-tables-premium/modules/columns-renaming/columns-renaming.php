<?php
if (!class_exists('WP_Sheet_Editor_Columns_Renaming')) {

	/**
	 * Rename the columns of the spreadsheet editor to something more meaningful.
	 */
	class WP_Sheet_Editor_Columns_Renaming {

		static private $instance = false;

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Columns_Renaming::$instance) {
				WP_Sheet_Editor_Columns_Renaming::$instance = new WP_Sheet_Editor_Columns_Renaming();
				WP_Sheet_Editor_Columns_Renaming::$instance->init();
			}
			return WP_Sheet_Editor_Columns_Renaming::$instance;
		}

		function init() {
			add_filter('vg_sheet_editor/columns/all_items', array($this, 'filter_columns_for_rename'), 10, 2);
			add_action('vg_sheet_editor/columns_visibility/enabled/after_column_action', array($this, 'render_rename_button'), 10, 2);
			add_action('vg_sheet_editor/after_enqueue_assets', array($this, 'enqueue_assets'));
			add_action('wp_ajax_vgse_rename_column', array($this, 'rename_column'));
		}

		/**
		 * Enqueue frontend assets
		 */
		function enqueue_assets() {
			wp_enqueue_script('wp-sheet-editor-columns-renaming', plugins_url('/assets/js/init.js', __FILE__), array(), VGSE()->version);
		}

		function render_rename_button($column, $post_type) {
			if (!current_user_can('manage_options') || empty($column['allow_to_rename'])) {
				return;
			}
			?>
			<button class="rename-column column-action" title="<?php echo esc_attr(__('Rename column', VGSE()->textname)); ?>"><i class="fa fa-edit"></i></button>
			<?php
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

		function rename_column() {

			$data = VGSE()->helpers->clean_data($_REQUEST);
			if (empty($data['nonce']) || empty($data['post_type']) || empty($data['column_key'])) {
				wp_send_json_error(array('message' => __('Missing parameters.', VGSE()->textname)));
			}

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce') || !current_user_can('manage_options')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to execute this action.', VGSE()->textname)));
			}
			$post_type = $data['post_type'];

			$options = get_option(VGSE()->options_key);
			$option_key = ( taxonomy_exists($data['column_key'])) ? 'be_tax_txt_' . $data['column_key'] . '_' . $post_type : 'be_' . $data['column_key'] . '_txt_' . $post_type;

			$options[$option_key] = $data['new_title'];
			update_option(VGSE()->options_key, $options);
			wp_send_json_success();
		}

		/**
		 * Rename columns
		 * @param array $columns
		 * @return array
		 */
		function filter_columns_for_rename($columns) {
			$options = VGSE()->options;

			if (empty($options)) {
				return $columns;
			}
			foreach ($columns as $post_type_key => $post_type_columns) {
				foreach ($post_type_columns as $key => $column) {
					if ($column['allow_to_rename']) {
						if (isset($options['be_' . $key . '_txt_' . $post_type_key]) && $options['be_' . $key . '_txt_' . $post_type_key]) {
							$columns[$post_type_key][$key]['title'] = $options['be_' . $key . '_txt_' . $post_type_key];
						} elseif (isset($options['be_tax_txt_' . $key . '_' . $post_type_key]) && $options['be_tax_txt_' . $key . '_' . $post_type_key]) {

							$columns[$post_type_key][$key]['title'] = $options['be_tax_txt_' . $key . '_' . $post_type_key];
						}
					}
				}
			}

			return $columns;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_columns_renaming_init');

	function vgse_columns_renaming_init() {
		WP_Sheet_Editor_Columns_Renaming::get_instance();
	}

}