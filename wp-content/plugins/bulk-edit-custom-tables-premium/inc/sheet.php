<?php

if (!class_exists('WPSE_Custom_Tables_Sheet')) {

	class WPSE_Custom_Tables_Sheet extends WPSE_Sheet_Factory {

		var $custom_tables = array();
		var $meta_tables = array();
		var $predefined_meta_schemas = array();

		function __construct() {
			if (wpsect_fs()->can_use_premium_code__premium_only()) {
				$this->custom_tables = $this->get_custom_tables();
				parent::__construct(array(
					'fs_object' => wpsect_fs(),
					'post_type' => array($this, 'get_custom_tables_and_labels'),
					'register_default_taxonomy_columns' => false,
					'bootstrap_class' => 'WPSE_Custom_Tables_Spreadsheet_Bootstrap',
					'columns' => array($this, 'get_columns'),
				));
				add_filter('vg_sheet_editor/provider/default_provider_key', array($this, 'set_default_provider'), 10, 2);
				add_filter('vg_sheet_editor/acf/fields', array($this, 'deactivate_acf_fields'), 10, 2);
				add_filter('vgse_sheet_editor/provider/custom_table/meta_table_name', array($this, 'use_different_meta_table'), 10, 2);
				add_filter('vgse_sheet_editor/provider/custom_table/meta_table_post_id_key', array($this, 'use_different_meta_id_column'), 10, 2);
				add_action('vg_sheet_editor/editor/before_init', array($this, '_register_columns'), 1);
				add_filter('vg_sheet_editor/provider/custom_table/meta_value_column_key', array($this, 'get_meta_value_column_key'), 10, 2);
				add_filter('vg_sheet_editor/provider/custom_table/meta_key_column_key', array($this, 'get_meta_key_column_key'), 10, 2);
				add_filter('vg_sheet_editor/provider/custom_table/table_schema', array($this, 'apply_columns_manager_formatting'));
			}
		}

		function apply_columns_manager_formatting($schema) {
			$post_type = $schema['post_type'];
			if (!in_array($post_type, $this->post_type)) {
				return $schema;
			}

			foreach ($schema['columns'] as $column_key => $column) {
				$column_settings = WP_Sheet_Editor_Columns_Manager::get_instance()->get_column_settings($column_key, $post_type);
				if (empty($column_settings)) {
					continue;
				}

				if ($column_settings['field_type'] === 'text_editor') {
					$schema['columns'][$column_key]['type'] = 'safe_html';
				}
			}
			return $schema;
		}

		function get_meta_value_column_key($key, $table_key) {
			if (isset($this->predefined_meta_schemas[$table_key])) {
				$key = $this->predefined_meta_schemas[$table_key]['meta_value'];
			}
			return $key;
		}

		function get_meta_key_column_key($key, $table_key) {
			if (isset($this->predefined_meta_schemas[$table_key])) {
				$key = $this->predefined_meta_schemas[$table_key]['meta_key'];
			}
			return $key;
		}

		function _get_meta_table_name($post_type) {

			if (isset($this->predefined_meta_schemas[$post_type]) && !empty($this->predefined_meta_schemas[$post_type]['meta_table_name'])) {
				return $this->predefined_meta_schemas[$post_type]['meta_table_name'];
			}

			$out = null;
			$meta_table1 = $post_type . 'meta';
			$meta_table2 = preg_replace('/s$/', '', $post_type) . 'meta';
			$meta_tables_keys = array_keys($this->meta_tables);
			if (in_array($meta_table1, $meta_tables_keys, true)) {
				$out = $meta_table1;
			} elseif (in_array($meta_table2, $meta_tables_keys, true)) {
				$out = $meta_table2;
			}
			return $out;
		}

		function use_different_meta_id_column($id_column, $post_type) {
			if (!in_array($post_type, $this->post_type)) {
				return $id_column;
			}

			$meta_table = $this->_get_meta_table_name($post_type);
			if (!$meta_table) {
				return $id_column;
			}

			$id_column = $this->meta_tables[$meta_table]['id'];
			return $id_column;
		}

		function use_different_meta_table($table_name, $post_type) {
			if (!in_array($post_type, $this->post_type)) {
				return $table_name;
			}
			$meta_table = $this->_get_meta_table_name($post_type);
			if ($meta_table) {
				$table_name = $meta_table;
			}
			return $table_name;
		}

		function after_full_core_init() {
			// Zero custom tables found
			if (empty($this->custom_tables)) {
				return;
			}
			parent::after_full_core_init();
//			add_filter('vg_sheet_editor/custom_tables/welcome_sheets_all', array($this, 'show_sheet_in_welcome_page'));
//			add_filter('vg_sheet_editor/custom_tables/welcome_sheets', array($this, 'show_sheet_in_welcome_page_as_enabled'));
			add_filter('vg_sheet_editor/advanced_filters/all_fields_groups', array($this, 'add_fields_to_advanced_filters'), 10, 2);
			add_filter('vg_sheet_editor/provider/custom_table/get_rows_sql', array($this, 'filter_rows_query_post_data'), 10, 3);
			add_filter('vg_sheet_editor/provider/custom_table/get_rows_sql', array($this, 'filter_rows_query_meta'), 10, 3);
			add_action('vg_sheet_editor/editor_page/after_editor_page', array($this, 'customize_search_form'));
		}

		function customize_search_form($post_type) {
			if (!in_array($post_type, $this->custom_tables, true)) {
				return;
			}
			?>
			<style>
				.wpse-advanced-filters-toggle {
					display: none;
				}
				.advanced-filters {
					display: block !important;
				}
			</style>
			<?php

		}

		function filter_rows_query_meta($sql, $args, $settings) {
			global $wpdb;
			$post_type = $settings['table_name'];
			if (!in_array($post_type, $this->custom_tables, true)) {
				return $sql;
			}

			if (empty($args['wpse_original_filters']) || empty($args['wpse_original_filters']['meta_query'])) {
				return $sql;
			}
			$line_meta_query = wp_list_filter($args['wpse_original_filters']['meta_query'], array(
				'source' => 'meta'
			));
			if (empty($line_meta_query)) {
				return $sql;
			}

			$meta_table_id_column = VGSE()->helpers->get_current_provider()->get_meta_table_post_id_key();
			$data_id_column = VGSE()->helpers->get_current_provider()->get_post_data_table_id_key($post_type);
			$meta_table_name = $this->_get_meta_table_name($post_type);

			$query_args = array('meta_query' => $line_meta_query);
			$meta_query = new WP_Meta_Query();
			$meta_query->parse_query_vars($query_args);
			$mq_sql = $meta_query->get_sql(
					'post', 't', $data_id_column, null
			);

			$search = array(
				$wpdb->postmeta,
				$meta_table_name . '.post_id',
				$meta_table_name . '.meta_key',
				$meta_table_name . '.meta_value',
			);
			$replace = array(
				$meta_table_name,
				$meta_table_name . '.' . $this->meta_tables[$meta_table_name]['id'],
				$meta_table_name . '.' . $this->meta_tables[$meta_table_name]['meta_key'],
				$meta_table_name . '.' . $this->meta_tables[$meta_table_name]['meta_value']
			);
			$mq_sql['join'] = str_replace($search, $replace, $mq_sql['join']);
			$mq_sql['where'] = str_replace($search, $replace, $mq_sql['where']);

			// Add left join
			$sql = str_replace(' as t ', ' as t ' . $mq_sql['join'], $sql);
			$meta_where = preg_replace('/^AND /', '', trim($mq_sql['where']));

			// Add where
			if (strpos($sql, ' WHERE ') === false) {
				$where = ' WHERE ' . $meta_where;
			} else {
				$where = ' AND ' . $meta_where;
			}
			$sql = str_replace(' ORDER ', $where . ' ORDER ', $sql);
			return $sql;
		}

		function filter_rows_query_post_data($sql, $args, $settings) {
			$post_type = $settings['table_name'];
			if (!in_array($post_type, $this->custom_tables, true)) {
				return $sql;
			}

			if (empty($args['wpse_original_filters']) || empty($args['wpse_original_filters']['meta_query'])) {
				return $sql;
			}
			$table_data_filters = wp_list_filter($args['wpse_original_filters']['meta_query'], array(
				'source' => 'post_data'
			));
			if (empty($table_data_filters)) {
				return $sql;
			}


			// Replace the ID field key with the real primary key for the search
			$primary_column_key = VGSE()->helpers->get_current_provider()->get_post_data_table_id_key($post_type);
			foreach ($table_data_filters as $index => $table_data_filter) {
				if ($table_data_filter['key'] === 'ID') {
					$table_data_filters[$index]['key'] = $primary_column_key;
				}
			}

			$raw_where = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table($table_data_filters, 't');
			if (empty($raw_where)) {
				return $sql;
			}

			$where = implode(' AND ', $raw_where);
			if (strpos($sql, ' WHERE ') === false) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace(' ORDER ', $where . ' ORDER ', $sql);
			return $sql;
		}

		function add_fields_to_advanced_filters($all_fields, $post_type) {
			if (!in_array($post_type, $this->custom_tables, true)) {
				return $all_fields;
			}

			$columns = VGSE()->helpers->get_current_provider()->get_arg('columns', $post_type);
			$all_fields['post_data'] = array_keys($columns);
			return $all_fields;
		}

		/**
		 * Register toolbar items
		 */
		function _register_columns($editor) {
			$post_types = $editor->args['enabled_post_types'];
			foreach ($post_types as $post_type) {
				if (!in_array($post_type, $this->custom_tables, true)) {
					continue;
				}

				$columns = $editor->provider->get_arg('columns', $post_type);
				if (empty($columns)) {
					continue;
				}
				$primary_column_key = $editor->provider->get_post_data_table_id_key($post_type);
				foreach ($columns as $column_key => $column) {
					// Don't register the primary key column because the bootstrap 
					// automatically registers the ID column
					if ($column_key === $primary_column_key) {
						continue;
					}
					$editor->args['columns']->register_item($column_key, $post_type, array(
						'data_type' => 'post_data',
						'column_width' => 150,
						'title' => VGSE()->helpers->convert_key_to_label($column_key),
						'type' => '',
						'supports_formulas' => true,
						'allow_to_hide' => true,
						'allow_to_rename' => true,
						'allow_custom_format' => true
					));
				}
				$editor->args['columns']->register_item('wpse_status', $post_type, array(
					'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
					'column_width' => 150, //int (Ancho de la columna)
					'title' => __('Status', vgse_custom_tables()->textname), //String (Titulo de la columna)
					'type' => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide' => false,
					'allow_to_save' => true,
					'allow_to_rename' => true,
					'default_value' => 'active',
					'formatted' => array(
						'editor' => 'select',
						'selectOptions' => array(
							'active',
							'delete',
						)),
				));
			}
		}

		function show_sheet_in_welcome_page_as_enabled($sheets) {
			$sheets = array_merge($sheets, $this->custom_tables);
			return $sheets;
		}

		function show_sheet_in_welcome_page($sheets) {
			$sheets['post_types'] = array_merge($sheets['post_types'], $this->get_prop('post_type'));
			$sheets['labels'] = array_merge($sheets['labels'], $this->get_prop('post_type_label'));
			return $sheets;
		}

		function deactivate_acf_fields($fields, $post_type) {
			if (in_array($post_type, $this->custom_tables, true)) {
				$fields = array();
			}
			return $fields;
		}

		function set_default_provider($provider_class_key, $provider) {
			if (in_array($provider, $this->custom_tables, true)) {
				$provider_class_key = 'custom_table';
			}
			return $provider_class_key;
		}

		function get_columns() {
			
		}

		function _get_label($label) {
			$label = VGSE()->helpers->convert_key_to_label($label);
			$label_words = explode(' ', $label);
			$out = array();
			foreach ($label_words as $word) {
				if (strlen($word) === 2) {
					$word = strtoupper($word);
				}
				$out[] = $word;
			}
			return implode(' ', $out);
		}

		function get_custom_tables_and_labels() {
			$out = array(
				'post_types' => $this->custom_tables,
				'labels' => array()
			);

			foreach ($this->custom_tables as $table) {
				$out['labels'][] = $this->_get_label($table);
			}

			return $out;
		}

		function _get_meta_table_structure($meta_table) {
			global $wpdb;
			$columns = $wpdb->get_results("SHOW COLUMNS FROM $meta_table", ARRAY_A);
			$out = array(
				'id' => null,
				'meta_key' => null,
				'meta_value' => null,
			);

			foreach ($columns as $column) {
				if ($column['Field'] === 'meta_key') {
					$out['meta_key'] = 'meta_key';
				} elseif ($column['Field'] === 'meta_value') {
					$out['meta_value'] = 'meta_value';
				} elseif (preg_match('/_id$/', $column['Field']) && $column['Extra'] !== 'auto_increment') {
					$out['id'] = $column['Field'];
				}
			}
			return $out;
		}

		function get_custom_tables() {
			global $wpdb;
			$tables = $wpdb->get_col("SHOW TABLES");

			$out = array(
			);
			$core_tables = array(
				$wpdb->prefix . "yoast_indexable",
				$wpdb->prefix . "yoast_indexable_hierarchy",
				$wpdb->prefix . "yoast_migrations",
				$wpdb->prefix . "yoast_primary_term",
				$wpdb->prefix . "yoast_seo_links",
				$wpdb->prefix . "yoast_seo_meta",
				$wpdb->prefix . "woocommerce_sessions",
				$wpdb->prefix . "woocommerce_payment_tokenmeta",
				$wpdb->prefix . "woocommerce_payment_tokens",
				$wpdb->prefix . "term_relationships",
				$wpdb->prefix . "term_taxonomy",
				$wpdb->prefix . "termmeta",
				$wpdb->prefix . "terms",
				$wpdb->prefix . "postmeta",
				$wpdb->prefix . "posts",
				$wpdb->prefix . "links",
				$wpdb->prefix . "options",
				$wpdb->prefix . "commentmeta",
				$wpdb->prefix . "comments"
			);

			$this->predefined_meta_schemas = apply_filters('vg_sheet_editor/custom_tables/predefined_meta_schema', array(
				$wpdb->prefix . 'my_warehouses' => array(
					'meta_table_name' => $wpdb->prefix . 'warehouse_meta',
					'id' => 'warehouse_id',
					'meta_key' => 'key',
					'meta_value' => 'value',
				),
				$wpdb->prefix . 'bp_groups' => array(
					'meta_table_name' => $wpdb->prefix . 'bp_groups_groupmeta',
					'id' => 'group_id',
					'meta_key' => 'meta_key',
					'meta_value' => 'meta_value',
				),
				$wpdb->prefix . 'wcfm_marketplace_withdraw_request' => array(
					'meta_table_name' => $wpdb->prefix . 'wcfm_marketplace_withdraw_request_meta',
					'id' => 'withdraw_id',
					'meta_key' => 'key',
					'meta_value' => 'value',
				)
					), $tables, $core_tables);
			$predefined_meta_tables = $this->predefined_meta_schemas ? wp_list_pluck($this->predefined_meta_schemas, 'meta_table_name') : array();

			foreach ($tables as $table) {
				// Exclude tables that don't share the site prefix
				if (strpos($table, $wpdb->prefix) === false) {
					continue;
				}
				// Excluded tables that have their own sheet editor plugins
				if (in_array($table, $core_tables, true)) {
					continue;
				}

				if ($predefined_meta_tables && in_array($table, $predefined_meta_tables, true)) {
					$parent_table_key = array_search($table, $predefined_meta_tables, true);
					$this->meta_tables[$table] = $this->predefined_meta_schemas[$parent_table_key];
					continue;
				}


				// Exclude meta tables
				$without_meta = preg_replace('/meta$/', '', $table);
				if ($without_meta !== $table && (in_array($without_meta, $tables, true) || in_array($without_meta . 's', $tables, true))) {
					$meta_table_data = array_filter($this->_get_meta_table_structure($table));
					if (count($meta_table_data) === 3) {
						$this->meta_tables[$table] = $meta_table_data;
					}
					continue;
				}
				$out[] = $table;
			}

			return array_unique($out);
		}

	}

	$GLOBALS['wpse_custom_tables_sheet'] = new WPSE_Custom_Tables_Sheet();
}