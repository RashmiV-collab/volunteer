<?php
if (!class_exists('WP_Sheet_Editor_Columns_Manager')) {

	/**
	 * Rename the columns of the spreadsheet editor to something more meaningful.
	 */
	class WP_Sheet_Editor_Columns_Manager {

		static private $instance = false;
		var $key = 'vgse_columns_manager';

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Columns_Manager::$instance) {
				WP_Sheet_Editor_Columns_Manager::$instance = new WP_Sheet_Editor_Columns_Manager();
				WP_Sheet_Editor_Columns_Manager::$instance->init();
			}
			return WP_Sheet_Editor_Columns_Manager::$instance;
		}

		function init() {

			if (version_compare(VGSE()->version, '2.8.1') < 0) {
				return;
			}

			require __DIR__ . '/inc/column-groups.php';

			// Allow to manage the columns formatting
			// UI
			if (current_user_can('manage_options')) {
				add_action('vg_sheet_editor/columns_visibility/enabled/after_column_action', array($this, 'render_settings_button'), 30, 2);
				add_action('vg_sheet_editor/after_enqueue_assets', array($this, 'enqueue_assets'));
				add_action('vg_sheet_editor/columns_visibility/after_options_saved', array($this, 'save_column_settings'), 10, 2);
				add_action('vg_sheet_editor/frontend/metabox/after_fields_saved', array($this, 'save_column_settings_from_frontend_sheet'), 10, 2);
				add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_items'));
				add_action('vg_sheet_editor/columns_visibility/after_instructions', array($this, 'render_instructions'));
				add_filter('vg_sheet_editor/custom_columns/columns_detected_settings_before_cache', array($this, 'maybe_detect_column_type_automatically'), 10, 2);
			}

			// Apply formatting settings
			add_filter('vg_sheet_editor/columns/all_items', array($this, 'apply_settings'), 10, 2);
			add_filter('vg_sheet_editor/serialized_addon/column_settings', array($this, 'apply_settings_to_serialized_column'), 10, 5);
			add_filter('vg_sheet_editor/infinite_serialized_column/column_settings', array($this, 'apply_settings_to_infinitely_serialized_column'), 10, 3);
		}

		function are_values_dates($values) {
			$out = array(
				'possible_dates' => array(),
				'is_date' => false,
				'display_format' => 'YYYY-MM-DD', // moment.js format used by the cell's calendar
				'save_format' => false
			);
			$values = array_filter(array_unique($values));
			if (!empty($values)) {
				foreach ($values as $value) {
					if (!is_scalar($value)) {
						continue;
					}
					if (empty($value) || preg_match('/^(\d{4}-\d{2}-\d{2}|\d{10}|\d{8})$/', $value)) {
						$out['possible_dates'][] = $value;
					}
				}

				$out['is_date'] = count($values) === count($out['possible_dates']);
				if (!empty($out['possible_dates'])) {
					$first_value = $out['possible_dates'][0];
					if ($out['is_date']) {
						if (is_numeric($first_value) && strlen($first_value) === 8) {
							$out['save_format'] = 'Ymd';
						} elseif (is_numeric($first_value) && strlen($first_value) === 10) {
							$out['save_format'] = 'U';
						} elseif (preg_match('/^(\d{4}-\d{2}-\d{2})$/', $value)) {
							$out['save_format'] = 'Y-m-d';
						}
					}
				}
			}
			return $out;
		}

		function are_values_media_files($values) {
			$out = array(
				'possible_files' => array(),
				'is_file' => false
			);
			$values = array_filter(array_unique($values));
			if (!empty($values)) {
				foreach ($values as $value) {
					if (!is_scalar($value)) {
						continue;
					}
					if (is_numeric($value) && get_post_type($value) === 'attachment') {
						$out['possible_files'][] = $value;
					} elseif (strpos($value, WP_CONTENT_URL . '/uploads/') === 0) {
						$out['possible_files'][] = $value;
					}
				}

				$out['is_file'] = count($values) === count($out['possible_files']);
			}
			return $out;
		}

		function maybe_detect_column_type_automatically($columns_detected, $post_type) {
			if (!empty(VGSE()->options['disable_automatic_formatting_detection'])) {
				return $columns_detected;
			}

			$new_formatting = array();
			if (isset($columns_detected['normal'])) {
				foreach ($columns_detected['normal'] as $column_key => $column_settings) {
					if ($column_settings['detected_type']['type'] !== 'text') {
						continue;
					}

					// If we have defined formatting previously, don't overwrite it automatically
					$current_format_settings = $this->get_formatted_column_settings($column_key, $post_type);
					if (!empty($current_format_settings)) {
						continue;
					}

					if (!isset($new_formatting[$column_key])) {
						$date_detection = $this->are_values_dates($column_settings['detected_type']['sample_values']);
						if ($date_detection['is_date']) {
							$new_formatting[$column_key] = array(
								'field_type' => 'date',
								'date_format_save' => $date_detection['save_format'],
							);
						}
					}
					if (!isset($new_formatting[$column_key])) {
						$files_detection = $this->are_values_media_files($column_settings['detected_type']['sample_values']);
						if ($files_detection['is_file']) {
							$new_formatting[$column_key] = array(
								'field_type' => 'file',
								'file_saved_format' => is_numeric($files_detection['possible_files'][0]) ? 'id' : 'url',
								'allow_multiple_files' => strpos($files_detection['possible_files'][0], ',') !== false,
								'multiple_files_format' => 'comma',
							);
						}
					}
				}
			}

			if (!empty($new_formatting)) {
				$this->save_column_settings($post_type, array(
					'column_settings' => $new_formatting
				));
			}
			return $columns_detected;
		}

		function render_instructions() {
			_e(' Some columns have the <i class="fa fa-cog"></i> button to change the formatting', VGSE()->textname);
		}

		/**
		 * Register toolbar item to edit columns visibility live on the spreadsheet
		 */
		function register_toolbar_items($editor) {
			$post_types = $editor->args['enabled_post_types'];
			foreach ($post_types as $post_type) {
				$editor->args['toolbars']->register_item('columns_manager', array(
					'type' => 'button',
					'allow_in_frontend' => false,
					'content' => __('Columns manager', VGSE()->textname),
					'toolbar_key' => 'secondary',
					'extra_html_attributes' => 'data-remodal-target="modal-columns-visibility"',
						), $post_type);
			}
		}

		function maybe_apply_settings_to_serialized_column($column_args, $post_type) {
			if (!empty($column_args['key'])) {
				$new_settings = $this->get_formatted_column_settings($column_args['key'], $post_type);
				$column_args = wp_parse_args($new_settings, $column_args);
			}

			return $column_args;
		}

		function apply_settings_to_infinitely_serialized_column($column_args, $serialized_column, $post_type) {
			return $this->maybe_apply_settings_to_serialized_column($column_args, $post_type);
		}

		function apply_settings_to_serialized_column($column_args, $first_set_keys, $field, $key, $post_type) {
			return $this->maybe_apply_settings_to_serialized_column($column_args, $post_type);
		}

		function apply_settings($columns) {
			$options = $this->get_settings();

			if (empty($options)) {
				return $columns;
			}
			foreach ($columns as $post_type_key => $post_type_columns) {
				// Skip if special formatting not defined for this post type
				if (!isset($options[$post_type_key])) {
					continue;
				}
				foreach ($post_type_columns as $key => $column) {
					// Skip if special formatting not defined for this column
					if (empty($options[$post_type_key][$key])) {
						continue;
					}
					// Skip if custom format is not explicitely enabled for this column
					if (empty($column['allow_custom_format'])) {
						continue;
					}
					$new_settings = $this->get_formatted_column_settings($key, $post_type_key);
					$columns[$post_type_key][$key] = wp_parse_args($new_settings, $column);
				}
			}

			return $columns;
		}

		function get_formatted_column_settings($key, $post_type) {
			$column_settings = $this->get_column_settings($key, $post_type);
			$out = array();
			// Skip if field type = automatic
			if (empty($column_settings['field_type'])) {
				return $out;
			}

			if ($column_settings['field_type'] === 'text') {
				$out['formatted'] = array(
					'data' => $key
				);
			} elseif ($column_settings['field_type'] === 'text_editor') {
				$out['formatted'] = array(
					'data' => $key,
					'renderer' => 'wp_tinymce'
				);
			} elseif ($column_settings['field_type'] === 'select' && !empty($column_settings['allowed_values'])) {
				$lines = array_map('trim', preg_split('/\r\n|\r|\n/', $column_settings['allowed_values']));
				$column_options = array();
				foreach ($lines as $line) {
					$line_parts = array_map('trim', explode(':', $line));
					$label = isset($line_parts[1]) ? $line_parts[1] : $line_parts[0];
					$option_key = $line_parts[0];
					$column_options[$option_key] = $label;
				}
				$out['formatted'] = array(
					'data' => $key,
					'editor' => 'select', 'selectOptions' => $column_options
				);
				if (empty(VGSE()->options['enable_plain_select_cells'])) {
					$out['formatted']['renderer'] = 'wp_friendly_select';
				}
			} elseif ($column_settings['field_type'] === 'multi_select' && !empty($column_settings['multi_select_allowed_values'])) {
				$lines = array_map('trim', preg_split('/\r\n|\r|\n/', $column_settings['multi_select_allowed_values']));
				$column_options = array();
				foreach ($lines as $line) {
					$line_parts = array_map('trim', explode(':', $line));
					$label = isset($line_parts[1]) ? $line_parts[1] : $line_parts[0];
					$option_key = $line_parts[0];
					$column_options[] = array(
						'id' => $option_key,
						'label' => $label
					);
				}
				$out['formatted'] = array(
					'renderer' => 'wp_chosen_dropdown',
					'data' => $key,
					'editor' => 'chosen',
					'width' => 150,
					'source' => $column_options,
					'chosenOptions' => array(
						'multiple' => true,
						'search_contains' => true,
//						'skip_no_results' => true,
						'data' => $column_options
					),
				);
				$out['prepare_value_for_database'] = array($this, 'prepare_multi_select_for_database');
				$out['prepare_value_for_display'] = array($this, 'prepare_multi_select_for_display');
				$out['columns_manager_settings'] = $column_settings;
			} elseif ($column_settings['field_type'] === 'checkbox' && !empty($column_settings['checked_template'])) {
				$out['formatted'] = array(
					'data' => $key,
					'type' => 'checkbox',
					'checkedTemplate' => $column_settings['checked_template'],
					'uncheckedTemplate' => $column_settings['unchecked_template'],
				);
				$out['default_value'] = $column_settings['unchecked_template'];
			} elseif ($column_settings['field_type'] === 'date' && !empty($column_settings['date_format_save'])) {
				$out = $this->get_format_settings_for_date_column($key, $column_settings['date_format_save']);
			} elseif ($column_settings['field_type'] === 'file') {
				$out['type'] = $column_settings['allow_multiple_files'] ? 'boton_gallery_multiple' : 'boton_gallery';
				$out['formatted'] = array(
					'data' => $key,
					'renderer' => 'wp_media_gallery'
				);
				$out['wp_media_multiple'] = true;
				$out['prepare_value_for_database'] = array($this, 'prepare_files_for_database');
				$out['prepare_value_for_display'] = array($this, 'prepare_files_for_display');
			} elseif ($column_settings['field_type'] === 'currency') {
				$out['formatted'] = array(
					'data' => $key
				);
				$out['prepare_value_for_database'] = array($this, 'prepare_currency_for_database');
			} elseif ($column_settings['field_type'] === 'term') {
				$taxonomy_filter = ( empty($column_settings['taxonomy_filter'])) ? $post_type : $column_settings['taxonomy_filter'];
				if (!empty(VGSE()->options['be_enable_fancy_taxonomy_cell'])) {
					$formatted = array(
						'data' => $key,
						'editor' => 'chosen',
						'width' => 150,
						'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($taxonomy_filter),
						'chosenOptions' => array(
							'multiple' => !empty($column_settings['allow_multiple_terms']),
							'search_contains' => true,
							'create_option' => true,
							'skip_no_results' => true,
							'persistent_create_option' => true,
							'data' => array())
					);
				} else {
					$hierarchy_tip = is_taxonomy_hierarchical($taxonomy_filter) ? __('. Add child categories using this format: Parent > child1 > child2', VGSE()->textname) : '';
					$formatted = array(
						'data' => $key,
						'type' => 'autocomplete',
						'source' => 'loadTaxonomyTerms'
					);

					$multiple_tip = '';
					if (!empty($column_settings['allow_multiple_terms'])) {
						$multiple_tip = __('Enter multiple terms separated by commas', VGSE()->textname);
					}
					$formatted['comment'] = array('value' => $multiple_tip . $hierarchy_tip);
				}
				$out['formatted'] = $formatted;
				$out['columns_manager_settings'] = $column_settings;
				$out['prepare_value_for_database'] = array($this, 'prepare_terms_for_database');
				$out['prepare_value_for_display'] = array($this, 'prepare_terms_for_display');
				$out['default_value'] = '';
				$out['formatted']['taxonomy_key'] = $taxonomy_filter;
			} elseif ($column_settings['field_type'] === 'post') {
				$out['formatted'] = array(
					'data' => $key,
					'type' => 'autocomplete',
					'source' => 'searchPostByKeyword',
					'searchPostType' => ( empty($column_settings['post_type_filter'])) ? $post_type : $column_settings['post_type_filter'],
				);
				if ($column_settings['allow_multiple_posts']) {
					$out['formatted']['comment'] = array('value' => __('Enter multiple post titles separated by commas', VGSE()->textname));
				}
				$out['columns_manager_settings'] = $column_settings;
				$out['prepare_value_for_database'] = array($this, 'prepare_posts_for_database');
				$out['prepare_value_for_display'] = array($this, 'prepare_posts_for_display');
				$out['default_value'] = '';
			} elseif ($column_settings['field_type'] === 'user') {
				$out['formatted'] = array(
					'data' => $key,
					'type' => 'autocomplete',
					'source' => 'searchUsers'
				);
				$out['columns_manager_settings'] = $column_settings;
				$out['prepare_value_for_database'] = array($this, 'prepare_user_for_database');
				$out['prepare_value_for_display'] = array($this, 'prepare_user_for_display');
				$out['default_value'] = '';
			}
			return apply_filters('vg_sheet_editor/columns_manager/column_settings', $out, $key, $post_type, $column_settings);
		}

		function prepare_user_for_database($post_id, $cell_key, $data_to_save, $post_type, $column_settings, $spreadsheet_columns) {
			if (empty($data_to_save)) {
				return $data_to_save;
			}

			$manager_settings = $column_settings['columns_manager_settings'];
			$user = get_user_by('login', $data_to_save);
			if (!$user) {
				return '';
			}

			$out = '';
			if ($manager_settings['user_saved_format'] === 'ID') {
				$out = $user->ID;
			} elseif ($manager_settings['user_saved_format'] === 'user_login') {
				$out = $user->user_login;
			} elseif ($manager_settings['user_saved_format'] === 'user_email') {
				$out = $user->user_email;
			}
			return $out;
		}

		function prepare_user_for_display($value, $post, $column_key, $column_settings) {
			if (empty($value)) {
				return '';
			}

			$manager_settings = $column_settings['columns_manager_settings'];
			$user = get_user_by(str_replace('user_', '', $manager_settings['user_saved_format']), $value);
			if (!$user) {
				return '';
			}

			$out = $user->user_login;
			return $out;
		}

		function prepare_terms_for_display($value, $post, $column_key, $column_settings) {
			global $wpdb;
			$out = '';
			if (empty($value)) {
				return $out;
			}
			$separator = VGSE()->helpers->get_term_separator();
			if (is_string($value)) {
				$terms = array_map('trim', explode($separator, $value));
			} elseif (is_array($value)) {
				$terms = $value;
			}
			$manager_settings = $column_settings['columns_manager_settings'];
			$save_format = $manager_settings['term_saved_format'];
			if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
				$save_format = VGSE()->helpers->sanitize_table_key($save_format);
			}
			if (!in_array($save_format, array('term_id', 'name', 'slug'), true)) {
				return $out;
			}
			if (empty($manager_settings['taxonomy_filter'])) {
				$manager_settings['taxonomy_filter'] = $post_type;
			}
			$args = array(
				'hide_empty' => false,
				'taxonomy' => $manager_settings['taxonomy_filter'],
				'update_term_meta_cache' => false
			);
			if ($save_format == 'term_id') {
				$args['include'] = $terms;
			} elseif ($save_format === 'slug') {
				$args['slug'] = $terms;
			} elseif ($save_format === 'name') {
				$term_ids = VGSE()->data_helpers->prepare_post_terms_for_saving(implode($separator, $terms), $manager_settings['taxonomy_filter']);
				$args['include'] = $term_ids;
			} else {
				return $out;
			}

			$term_objects = get_terms($args);
			$out = VGSE()->data_helpers->prepare_post_terms_for_display($term_objects);
			return $out;
		}

		function prepare_terms_for_database($post_id, $cell_key, $data_to_save, $post_type, $column_settings, $spreadsheet_columns) {
			if (empty($data_to_save)) {
				return $data_to_save;
			}

			$manager_settings = $column_settings['columns_manager_settings'];
			$save_format = $manager_settings['term_saved_format'];

			if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
				$save_format = VGSE()->helpers->sanitize_table_key($save_format);
			}
			if (!in_array($save_format, array('term_id', 'name', 'slug'), true)) {
				return '';
			}
			if (empty($manager_settings['taxonomy_filter'])) {
				$manager_settings['taxonomy_filter'] = $post_type;
			}
			$separator = VGSE()->helpers->get_term_separator();
			$raw_term_names = array_map('trim', explode($separator, $data_to_save));

			if (empty($manager_settings['allow_multiple_terms'])) {
				$term_names = array($raw_term_names[0]);
			} else {
				$term_names = $raw_term_names;
			}



			if ($save_format === 'name') {
				$values = $term_names;
			} elseif ($save_format === 'term_id') {
				$values = VGSE()->data_helpers->prepare_post_terms_for_saving(implode($separator, $term_names), $manager_settings['taxonomy_filter']);
			} elseif ($save_format === 'slug') {
				$term_ids = VGSE()->data_helpers->prepare_post_terms_for_saving(implode($separator, $term_names), $manager_settings['taxonomy_filter']);
				$args = array(
					'hide_empty' => false,
					'include' => $term_ids,
					'taxonomy' => $manager_settings['taxonomy_filter'],
					'fields' => 'slugs',
					'update_term_meta_cache' => false
				);
				$values = get_terms($args);
			}
			if ($manager_settings['multiple_terms_format'] === 'comma') {
				$out = implode($separator, $values);
			} else {
				$out = $values;
			}
			return $out;
		}

		function prepare_posts_for_database($post_id, $cell_key, $data_to_save, $post_type, $column_settings, $spreadsheet_columns) {
			global $wpdb;
			if (empty($data_to_save)) {
				return $data_to_save;
			}

			$manager_settings = $column_settings['columns_manager_settings'];
			$save_format = $manager_settings['post_saved_format'];

			if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
				$save_format = VGSE()->helpers->sanitize_table_key($save_format);
			}
			if (!in_array($save_format, array('ID', 'post_title', 'post_name'))) {
				return '';
			}
			if (empty($manager_settings['post_type_filter'])) {
				$manager_settings['post_type_filter'] = $post_type;
			}
			$post_titles = array_map('html_entity_decode', array_map('trim', explode(',', $data_to_save)));

			$posts_in_query_placeholders = implode(', ', array_fill(0, count($post_titles), '%s'));
			$values = $wpdb->get_col($wpdb->prepare("SELECT $save_format FROM $wpdb->posts WHERE post_type = %s AND post_title IN ($posts_in_query_placeholders) ", array_merge(array($manager_settings['post_type_filter']), $post_titles)));

			if ($manager_settings['multiple_posts_format'] === 'comma') {
				$out = implode(',', $values);
			} else {
				$out = $values;
			}
			return $out;
		}

		function prepare_multi_select_for_database($post_id, $cell_key, $data_to_save, $post_type, $column_settings, $spreadsheet_columns) {
			if (empty($data_to_save)) {
				return $data_to_save;
			}
			$manager_settings = $column_settings['columns_manager_settings'];
			$save_format = $manager_settings['multi_select_saved_format'];

			if ($save_format === 'serialized') {
				$data_to_save = array_map('trim', explode(',', $data_to_save));
			}
			return $data_to_save;
		}

		function prepare_multi_select_for_display($value, $post, $column_key, $column_settings) {
			$out = '';
			if (empty($value)) {
				return $out;
			}
			if (is_string($value)) {
				$out = $value;
			} elseif (is_array($value)) {
				$out = implode(', ', $value);
			}
			return $out;
		}

		function prepare_posts_for_display($value, $post, $column_key, $column_settings) {
			global $wpdb;
			$posts = '';
			if (empty($value)) {
				return $posts;
			}
			if (is_string($value)) {
				$posts = array_map('trim', explode(',', $value));
			} elseif (is_array($value)) {
				$posts = $value;
			}
			$manager_settings = $column_settings['columns_manager_settings'];
			$save_format = $manager_settings['post_saved_format'];
			if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
				$save_format = VGSE()->helpers->sanitize_table_key($save_format);
			}
			if (!in_array($save_format, array('ID', 'post_title', 'post_name'))) {
				return $posts;
			}
			if (empty($manager_settings['post_type_filter'])) {
				$manager_settings['post_type_filter'] = $post->post_type;
			}
			if ($save_format == 'ID') {
				$post_ids = $posts;
			} else {
				$posts_in_query_placeholders = implode(', ', array_fill(0, count($posts), '%s'));
				$post_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = %s AND $save_format IN ($posts_in_query_placeholders) ", array_merge(array($manager_settings['post_type_filter']), $posts)));
			}
			$post_titles = array();
			foreach ($post_ids as $single_post) {
				$post_titles[] = html_entity_decode(get_post_field('post_title', (int) $single_post, 'raw'));
			}
			$out = implode(', ', array_filter($post_titles));
			return $out;
		}

		function prepare_files_for_display($value, $post, $column_key, $column_settings) {
			$value = VGSE()->helpers->get_gallery_cell_content($post->ID, $column_key, $column_settings['data_type'], $value);
			return $value;
		}

		function get_format_settings_for_date_column($key, $date_format_save) {
			$settings = array();
			$settings['formatted'] = array(
				'data' => $key,
				'type' => 'date',
				'customDatabaseFormat' => $date_format_save,
				'dateFormat' => 'YYYY-MM-DD',
				'correctFormat' => true,
				'defaultDate' => '',
				'datePickerConfig' => array('firstDay' => 0, 'showWeekNumber' => true, 'numberOfMonths' => 1),
			);
			$settings['prepare_value_for_database'] = array($this, 'prepare_date_for_database');
			$settings['prepare_value_for_display'] = array($this, 'format_date_for_cell');
			return $settings;
		}

		function format_date_for_cell($value, $post, $cell_key, $cell_args) {
			$column_settings = $this->get_column_settings($cell_key, $post->post_type);
			if ($column_settings['field_type'] !== 'date') {
				return $value;
			}
			$value = VGSE()->helpers->get_current_provider()->get_item_meta($post->ID, $cell_key, true, 'read');
			if (!empty($value)) {
				$timestamp = preg_match('/^\d{10}$/', $value) ? (int) $value : strtotime($value);
				$value = date('Y-m-d', $timestamp);
			}
			return $value;
		}

		function prepare_date_for_database($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {
			$column_settings = $this->get_column_settings($cell_key, $post_type);
			if ($column_settings['field_type'] !== 'date') {
				return $data_to_save;
			}
			if (!empty($data_to_save)) {
				$data_to_save = date($column_settings['date_format_save'], strtotime($data_to_save));
			}
			return $data_to_save;
		}

		function prepare_files_for_database($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {
			$column_settings = $this->get_column_settings($cell_key, $post_type);
			if ($column_settings['field_type'] !== 'file') {
				return $data_to_save;
			}
			if (!empty($data_to_save)) {
				$urls = array_map('trim', explode(',', $data_to_save));
				if ($column_settings['file_saved_format'] === 'id') {
					$file_ids = VGSE()->helpers->maybe_replace_urls_with_file_ids($urls, $post_id);
				} else {
					$file_ids = $urls;
				}
				if ($column_settings['allow_multiple_files']) {
					$data_to_save = ($column_settings['multiple_files_format'] === 'comma') ? implode(',', $file_ids) : $file_ids;
				} else {
					$data_to_save = current($file_ids);
				}
			}
			return $data_to_save;
		}

		function prepare_currency_for_database($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {
			$column_settings = $this->get_column_settings($cell_key, $post_type);
			if (!empty($data_to_save) && is_numeric($data_to_save)) {
				$data_to_save = number_format($data_to_save, $column_settings['currency_decimals'], $column_settings['decimal_separator'], $column_settings['thousands_separator']);
			}
			return $data_to_save;
		}

		function get_settings($post_type = '') {

			$existing = get_option($this->key);
			if (empty($existing)) {
				$existing = array();
			}
			if ($post_type && empty($existing[$post_type])) {
				$existing[$post_type] = array();
			}
			return $existing;
		}

		function key_to_regex($column_key) {
			$regex = false;
			if (!empty($column_key) && preg_match('/\d/', $column_key)) {
				$regex = '/' . str_replace('/', '', preg_replace('/[0-9]+/', '\d+', $column_key)) . '/';
			}
			return $regex;
		}

		function save_column_settings_from_frontend_sheet($post_id, $data) {
			$this->save_column_settings($data['vgse_post_type'], $data);
		}

		function save_column_settings($post_type, $data) {
			if (!isset($data['column_settings'])) {
				return;
			}
			$settings = $data['column_settings'];
			$existing = $this->get_settings($post_type);
			$existing[$post_type] = wp_parse_args($settings, $existing[$post_type]);

			foreach ($existing[$post_type] as $column_key => $column_settings) {
				if (empty($column_settings['field_type'])) {
					unset($existing[$post_type][$column_key]);
				}
			}
			$existing = VGSE()->helpers->array_remove_empty($existing);

			update_option($this->key, apply_filters('vg_sheet_editor/columns_manager/save_settings', $existing, $settings, $post_type));
		}

		/**
		 * Enqueue frontend assets
		 */
		function enqueue_assets() {
			wp_enqueue_script('wp-sheet-editor-columns-manager', plugins_url('/assets/js/init.js', __FILE__), array(), VGSE()->version);
		}

		function get_column_settings($column_key, $post_type) {

			$existing_settings = $this->get_settings($post_type);
			if (isset($existing_settings[$post_type][$column_key])) {
				$column_settings = $existing_settings[$post_type][$column_key];
			} else {
				$regex_key = $this->key_to_regex($column_key);
				if ($regex_key) {
					foreach ($existing_settings[$post_type] as $column_key => $raw_column_settings) {
						if (preg_match($regex_key, $column_key)) {
							$column_settings = $raw_column_settings;
							break;
						}
					}
				}
			}
			if (empty($column_settings)) {
				$column_settings = array();
			}

			$default_settings = array(
				'field_type' => '',
				'allowed_values' => '',
				'multi_select_allowed_values' => '',
				'multi_select_saved_format' => '',
				'checked_template' => '',
				'unchecked_template' => '',
				'user_saved_format' => '',
				'post_saved_format' => '',
				'post_type_filter' => '',
				'allow_multiple_posts' => '',
				'multiple_posts_format' => '',
				'term_saved_format' => '',
				'taxonomy_filter' => '',
				'allow_multiple_terms' => '',
				'multiple_terms_format' => '',
				'thousands_separator' => '',
				'decimal_separator' => '',
				'currency_decimals' => '',
				'file_saved_format' => '',
				'allow_multiple_files' => '',
				'multiple_files_format' => '',
				'date_format_save' => '',
			);
			$column_settings = wp_parse_args($column_settings, $default_settings);
			return $column_settings;
		}

		function render_settings_button($column, $post_type) {
			if (empty($column['allow_custom_format'])) {
				return;
			}
			if (!apply_filters('vg_sheet_editor/columns_manager/can_render_button', true, $column, $post_type)) {
				return;
			}
			$column_key = $column['key'];
			$column_settings = $this->get_column_settings($column_key, $post_type);
			?>
			<button class="settings-column column-action" title="<?php echo esc_attr(__('Settings', VGSE()->textname)); ?>"><i class="fa fa-cog"></i></button>
			<div class="column-settings"> 
				<div class="column-settings-field field-type">
					<label><?php _e('Column format', VGSE()->textname); ?></label>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][field_type]">
						<option value="" <?php selected(empty($column_settings['field_type'])); ?>><?php _e('Automatic', VGSE()->textname); ?></option>
						<option value="text" <?php selected($column_settings['field_type'], 'text'); ?>><?php _e('Text', VGSE()->textname); ?></option>
						<option value="text_editor" <?php selected($column_settings['field_type'], 'text_editor'); ?>><?php _e('Text editor (tinymce)', VGSE()->textname); ?></option>
						<option value="select" <?php selected($column_settings['field_type'], 'select'); ?>><?php _e('Single selection dropdown', VGSE()->textname); ?></option>
						<option value="multi_select" <?php selected($column_settings['field_type'], 'multi_select'); ?>><?php _e('Multi select dropdown', VGSE()->textname); ?></option>
						<option value="checkbox" <?php selected($column_settings['field_type'], 'checkbox'); ?>><?php _e('Checkbox', VGSE()->textname); ?></option>
						<option value="file" <?php selected($column_settings['field_type'], 'file'); ?>><?php _e('File upload', VGSE()->textname); ?></option>
						<option value="date" <?php selected($column_settings['field_type'], 'date'); ?>><?php _e('Date', VGSE()->textname); ?></option>
						<option value="user" <?php selected($column_settings['field_type'], 'user'); ?>><?php _e('User dropdown', VGSE()->textname); ?></option>
						<option value="post" <?php selected($column_settings['field_type'], 'post'); ?>><?php _e('Post  dropdown', VGSE()->textname); ?></option>
						<option value="term" <?php selected($column_settings['field_type'], 'term'); ?>><?php _e('Taxonomy term  dropdown', VGSE()->textname); ?></option>
						<option value="currency" <?php selected($column_settings['field_type'], 'currency'); ?>><?php _e('Currency', VGSE()->textname); ?></option>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-select">
					<label><?php _e('Allowed values', VGSE()->textname); ?></label>
					<p><?php _e('Enter each choice on a new line. For more control, you may specify both a value and label like this:<br>red : Red', VGSE()->textname); ?></p>
					<textarea name="column_settings[<?php echo esc_attr($column_key); ?>][allowed_values]"><?php echo $column_settings['allowed_values']; ?></textarea>
				</div>
				<div class="column-settings-field settings-for-type settings-for-multi_select">
					<label><?php _e('Allowed values', VGSE()->textname); ?></label>
					<p><?php _e('Enter each choice on a new line. For more control, you may specify both a value and label like this:<br>red : Red', VGSE()->textname); ?></p>
					<textarea name="column_settings[<?php echo esc_attr($column_key); ?>][multi_select_allowed_values]"><?php echo $column_settings['multi_select_allowed_values']; ?></textarea>
					<label><?php _e('How are the multiple values saved in the database?', VGSE()->textname); ?></label>	
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][multi_select_saved_format]">
						<option <?php selected($column_settings['multi_select_saved_format'], 'comma'); ?> value="comma"><?php _e('Separated with commas', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['multi_select_saved_format'], 'serialized'); ?> value="serialized"><?php _e('Serialized array', VGSE()->textname); ?></option>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-checkbox">
					<label><?php _e('What valued is saved when the checkbox is checked?', VGSE()->textname); ?></label>					
					<input value="<?php echo esc_attr($column_settings['checked_template']); ?>" type="text" name="column_settings[<?php echo esc_attr($column_key); ?>][checked_template]">
					<label><?php _e('What valued is saved when the checkbox is unchecked?', VGSE()->textname); ?></label>					
					<input value="<?php echo esc_attr($column_settings['unchecked_template']); ?>" type="text" name="column_settings[<?php echo esc_attr($column_key); ?>][unchecked_template]">
				</div>
				<div class="column-settings-field settings-for-type settings-for-user">	
					<p><?php _e('You will be able to type the username in the cell and the cell will show a dropdown with suggestions.', VGSE()->textname); ?></p>
					<label><?php _e('How is the user saved in the database?', VGSE()->textname); ?></label>	
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][user_saved_format]">
						<option <?php selected($column_settings['user_saved_format'], 'ID'); ?> value="ID"><?php _e('ID', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['user_saved_format'], 'user_login'); ?> value="user_login"><?php _e('Username', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['user_saved_format'], 'user_email'); ?> value="user_email"><?php _e('Email', VGSE()->textname); ?></option>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-term">	
					<p><?php _e('You will be able to type the term name in the cell and the cell will show a dropdown with suggestions.', VGSE()->textname); ?></p>
					<label><?php _e('How is the taxonomy term saved in the database?', VGSE()->textname); ?></label>	
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][term_saved_format]">
						<option <?php selected($column_settings['term_saved_format'], 'term_id'); ?> value="term_id"><?php _e('Term ID', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['term_saved_format'], 'name'); ?> value="name"><?php _e('Name', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['term_saved_format'], 'slug'); ?> value="slug"><?php _e('Slug', VGSE()->textname); ?></option>
					</select>
					<br>
					<label><input  <?php checked($column_settings['allow_multiple_terms'], 'yes'); ?> value="yes" type="checkbox" name="column_settings[<?php echo esc_attr($column_key); ?>][allow_multiple_terms]"> <?php _e('Allow multiple terms per field?', VGSE()->textname); ?></label>

					<label><?php _e('How do you want to save the multiple terms?', VGSE()->textname); ?></label>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][multiple_terms_format]">
						<option <?php selected($column_settings['multiple_terms_format'], 'comma'); ?> value="comma"><?php _e('Saved them separated by comma', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['multiple_terms_format'], 'array'); ?> value="array"><?php _e('Save them as serialized array', VGSE()->textname); ?></option>
					</select>
					<label><?php _e('Accept terms from this taxonomy', VGSE()->textname); ?></label>	
					<p><?php _e('For example, if you select the blog categories, we will only accept blog categories in this column.', VGSE()->textname); ?></p>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][taxonomy_filter]">
						<option <?php selected($column_settings['taxonomy_filter'], ''); ?> value=""><?php _e('Same as the spreadsheet taxonomy', VGSE()->textname); ?></option>
						<?php
						$all_taxonomies = get_taxonomies(array(), 'objects');
						foreach ($all_taxonomies as $taxonomy) {
							?>
							<option <?php selected($column_settings['taxonomy_filter'], $taxonomy->name); ?> value="<?php echo esc_attr($taxonomy->name) ?>"><?php echo esc_html($taxonomy->label); ?></option>
						<?php }
						?>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-currency">	
					<p><?php _e('You will be able to type numbers without formatting, for example: 999999.88 or 100, and we will automatically save them in the formatted way. This conversion will happen when you save and not live when you edit in the cells and you will see the modified values on the next spreadsheet reload.', VGSE()->textname); ?></p>
					<label><?php _e('Number of decimals', VGSE()->textname); ?></label>	
					<input name="column_settings[<?php echo esc_attr($column_key); ?>][currency_decimals]" value="<?php echo (int) $column_settings['currency_decimals']; ?>">
					<br>
					<label><?php _e('Decimals separator', VGSE()->textname); ?></label>	
					<input name="column_settings[<?php echo esc_attr($column_key); ?>][decimal_separator]" value="<?php echo esc_attr($column_settings['decimal_separator']); ?>">
					<br>
					<label><?php _e('Thousands separator', VGSE()->textname); ?></label>	
					<input name="column_settings[<?php echo esc_attr($column_key); ?>][thousands_separator]" value="<?php echo esc_attr($column_settings['thousands_separator']); ?>">
				</div>
				<div class="column-settings-field settings-for-type settings-for-post">	
					<p><?php _e('You will be able to type the post title in the cell and the cell will show a dropdown with suggestions.', VGSE()->textname); ?></p>
					<label><?php _e('How is the post saved in the database?', VGSE()->textname); ?></label>	
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][post_saved_format]">
						<option <?php selected($column_settings['post_saved_format'], 'ID'); ?> value="ID"><?php _e('Post ID', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['post_saved_format'], 'post_title'); ?> value="post_title"><?php _e('Title', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['post_saved_format'], 'post_name'); ?> value="post_name"><?php _e('Slug', VGSE()->textname); ?></option>
					</select>
					<br>
					<label><input  <?php checked($column_settings['allow_multiple_posts'], 'yes'); ?> value="yes" type="checkbox" name="column_settings[<?php echo esc_attr($column_key); ?>][allow_multiple_posts]"> <?php _e('Allow multiple posts per field?', VGSE()->textname); ?></label>

					<label><?php _e('How do you want to save the multiple posts?', VGSE()->textname); ?></label>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][multiple_posts_format]">
						<option <?php selected($column_settings['multiple_posts_format'], 'comma'); ?> value="comma"><?php _e('Saved them separated by comma', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['multiple_posts_format'], 'array'); ?> value="array"><?php _e('Save them as serialized array', VGSE()->textname); ?></option>
					</select>
					<label><?php _e('Accept post from this post type', VGSE()->textname); ?></label>	
					<p><?php _e('For example, if you select the post type "product", we will only accept product titles.', VGSE()->textname); ?></p>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][post_type_filter]">
						<option <?php selected($column_settings['post_type_filter'], ''); ?> value=""><?php _e('Same as the spreadsheet post type', VGSE()->textname); ?></option>
						<?php
						$all_post_types = VGSE()->helpers->get_all_post_types();
						foreach ($all_post_types as $post_type_option) {
							?>
							<option <?php selected($column_settings['post_type_filter'], $post_type_option->name); ?> value="<?php echo esc_attr($post_type_option->name) ?>"><?php echo esc_html($post_type_option->label); ?></option>
						<?php }
						?>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-file">
					<label><?php _e('How is the file saved in the database?', VGSE()->textname); ?></label>	
					<p><?php _e('The cell will display the values as URLs and you can edit in the cells using full URLs, file ID, or file name.<br>External URLs are automatically imported into the media library.<br>We will save the value in the format selected here', VGSE()->textname); ?></p>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][file_saved_format]">
						<option <?php selected($column_settings['file_saved_format'], 'id'); ?> value="id"><?php _e('File ID', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['file_saved_format'], 'url'); ?> value="url"><?php _e('File URL', VGSE()->textname); ?></option>
					</select>
					<br>
					<label><input  <?php checked($column_settings['allow_multiple_files'], 'yes'); ?> value="yes" type="checkbox" name="column_settings[<?php echo esc_attr($column_key); ?>][allow_multiple_files]"> <?php _e('Allow multiple files per field?', VGSE()->textname); ?></label>
					<label><?php _e('How do you want to save the multiple files?', VGSE()->textname); ?></label>
					<select name="column_settings[<?php echo esc_attr($column_key); ?>][multiple_files_format]">
						<option <?php selected($column_settings['multiple_files_format'], 'comma'); ?> value="comma"><?php _e('Saved them separated by comma', VGSE()->textname); ?></option>
						<option <?php selected($column_settings['multiple_files_format'], 'array'); ?> value="array"><?php _e('Save them as serialized array', VGSE()->textname); ?></option>
					</select>
				</div>
				<div class="column-settings-field settings-for-type settings-for-date">					
					<p><?php _e('The cells will display the date in the format: YYYY-MM-DD', VGSE()->textname); ?></p>
					<label><?php _e('What date format do you want to save in the database?', VGSE()->textname); ?></label>	
					<p><?php _e('Enter a date format. <a href="https://www.php.net/date#refsect1-function.date-parameters" target="_blank">List of formats</a>. Example: Y-m-d', VGSE()->textname); ?></p>
					<input value="<?php echo esc_attr($column_settings['date_format_save']); ?>" type="text" name="column_settings[<?php echo esc_attr($column_key); ?>][date_format_save]">
				</div>
				<?php do_action('vg_sheet_editor/columns_manager/after_settings_fields_rendered', $column, $post_type, $column_settings); ?>
			</div>
			<?php
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_columns_manager_init');

	function vgse_columns_manager_init() {
		return WP_Sheet_Editor_Columns_Manager::get_instance();
	}

}