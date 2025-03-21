<?php
if (!class_exists('WPSE_Custom_Tables_Spreadsheet_Bootstrap')) {

	class WPSE_Custom_Tables_Spreadsheet_Bootstrap extends WP_Sheet_Editor_Bootstrap {

		public function __construct($args) {
			parent::__construct($args);

			add_action('admin_footer', array($this, 'remove_overloading_menu'));
			add_action('admin_menu', array($this, 'register_menu'));
		}

		function render_quick_access() {
			
		}

		function register_menu() {
			add_submenu_page('vg_sheet_editor_setup', __('Edit custom database tables', vgse_custom_tables()->textname), __('Edit custom database tables', vgse_custom_tables()->textname), 'manage_options', admin_url('admin.php?page=wpsect_welcome_page'), null);
		}

		function remove_overloading_menu() {
			?>
			<script>jQuery(document).ready(function () {
					var spreadsheets = <?php echo json_encode($this->enabled_post_types); ?>;
					spreadsheets.forEach(function (spreadsheet) {
						jQuery('#adminmenu a[href="admin.php?page=vgse-bulk-edit-' + spreadsheet + '"]').parent().remove();
					});

				});</script>

			<?php
		}

		function _register_columns() {
			$post_types = $this->enabled_post_types;

			foreach ($post_types as $post_type) {
				$this->columns->register_item('ID', $post_type, array(
					'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
					'unformatted' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true), //Array (Valores admitidos por el plugin de handsontable)
					'column_width' => 75, //int (Ancho de la columna)
					'title' => __('ID', VGSE()->textname), //String (Titulo de la columna)
					'type' => '', // String (Es para saber si serÃ¡ un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => false,
					'allow_to_hide' => false,
					'allow_to_save' => false,
					'allow_to_rename' => false,
					'is_locked' => true,
					'formatted' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true),
				));
			}
		}

	}

}