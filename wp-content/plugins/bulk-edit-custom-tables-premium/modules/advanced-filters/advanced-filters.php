<?php
if (!class_exists('WP_Sheet_Editor_Advanced_Filters')) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_Advanced_Filters {

		static private $instance = false;
		var $addon_helper = null;
		var $plugin_url = null;
		var $plugin_dir = null;
		var $favorite_search_fields = 'vgse_favorite_search_fields';

		private function __construct() {
			
		}

		function init() {

			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			add_action('vg_sheet_editor/after_enqueue_assets', array($this, 'register_assets'));
			add_action('vg_sheet_editor/filters/after_fields', array($this, 'add_filters_fields'), 10, 2);
			add_action('vg_sheet_editor/filters/before_form_closing', array($this, 'add_advanced_filters_fields'), 10, 2);
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'filter_posts'), 10, 2);
			add_filter('vg_sheet_editor/filters/allowed_fields', array($this, 'register_filters'), 10, 2);
			add_filter('posts_clauses', array($this, 'exclude_by_keyword'), 10, 2);
			add_filter('posts_clauses', array($this, 'add_advanced_post_data_query'), 10, 2);
			add_filter('posts_clauses', array($this, 'add_advanced_taxonomy_query'), 10, 2);
			add_action('vg_sheet_editor/filters/after_form_closing', array($this, 'render_save_this_search'));
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_items'), 50);
			add_action('wp_ajax_vgse_delete_saved_search', array($this, 'delete_saved_search'));
			add_action('wp_ajax_vgse_mark_search_fields_as_favorite', array($this, 'mark_search_fields_as_favorite'));
			add_filter('vg_sheet_editor/js_data', array($this, 'add_favorite_search_fields_data'), 10, 2);
		}

		function add_favorite_search_fields_data($data, $post_type) {
			$saved_items = get_option($this->favorite_search_fields);

			$data['favorite_search_fields'] = (is_array($saved_items) && isset($saved_items[$post_type])) ? $saved_items[$post_type] : array();
			return $data;
		}

		function mark_search_fields_as_favorite() {
			$data = VGSE()->helpers->clean_data($_REQUEST);
			if (!wp_verify_nonce($data['nonce'], 'bep-nonce') || !current_user_can('manage_options')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			if (empty($data['fields'])) {
				$data['fields'] = array();
			}

			$post_type = $data['post_type'];
			$fields = array_map('sanitize_text_field', $data['fields']);

			$saved_items = get_option($this->favorite_search_fields);
			if (empty($saved_items)) {
				$saved_items = array();
			}

			if (!isset($saved_items[$post_type])) {
				$saved_items[$post_type] = array();
			}

			$saved_items[$post_type] = $fields;
			update_option($this->favorite_search_fields, $saved_items);
			wp_send_json_success();
		}

		function delete_saved_search() {
			$data = VGSE()->helpers->clean_data($_REQUEST);
			if (!wp_verify_nonce($data['nonce'], 'bep-nonce') || !current_user_can('manage_options')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}

			$post_type = $data['post_type'];
			$name = $data['search_name'];

			$saved_items = get_option('vgse_saved_searches');
			if (empty($saved_items)) {
				wp_send_json_success();
			}

			if (!isset($saved_items[$post_type])) {
				wp_send_json_success();
			}

			$same_name = wp_list_filter($saved_items[$post_type], array('name' => $name));
			foreach ($same_name as $index => $same_name_search) {
				unset($saved_items[$post_type][$index]);
			}
			update_option('vgse_saved_searches', $saved_items);
			wp_send_json_success();
		}

		function register_toolbar_items($editor) {

			if (!current_user_can('manage_options')) {
				return;
			}
			$post_types = $editor->args['enabled_post_types'];
			$private_keys = array('name', 'search_name', 'post_type');
			foreach ($post_types as $post_type) {
				$saved_searches = $this->get_saved_searches($post_type);
				foreach ($saved_searches as $index => $saved_search) {
					$name = esc_html($saved_search['name']);
					foreach ($saved_search as $key => $value) {
						if (in_array($key, $private_keys, true)) {
							unset($saved_search[$key]);
						}
					}

					$editor->args['toolbars']->register_item('saved_search' . $index, array(
						'type' => 'button',
						'content' => $name,
						'toolbar_key' => 'secondary',
						'allow_in_frontend' => true,
						'parent' => 'run_filters',
						'extra_html_attributes' => 'data-saved-type="search" data-saved-item data-item-name="' . esc_attr($name) . '" data-start-saved-search="' . esc_attr(json_encode($saved_search)) . '"',
							), $post_type);
				}
			}
		}

		function render_save_this_search() {
			if (!current_user_can('manage_options') || !is_admin()) {
				return;
			}
			?>
			<div class="save-search-wrapper">
				<label class="save-search"><?php _e('Save this search', VGSE()->textname); ?></label>
				<input name="search_name" placeholder="<?php esc_attr_e('Enter a name...', VGSE()->textname); ?>" class="save-search-input">
			</div>
			<?php
		}

		function add_advanced_taxonomy_query($clauses, $wp_query) {
			global $wpdb;
			if (empty($wp_query->query['wpse_original_filters']) || empty($wp_query->query['wpse_original_filters']['meta_query']) || !is_array($wp_query->query['wpse_original_filters']['meta_query'])) {
				return $clauses;
			}
			$post_data_query = wp_list_filter($wp_query->query['wpse_original_filters']['meta_query'], array(
				'source' => 'taxonomy_keys'
			));
			if (empty($post_data_query)) {
				return $clauses;
			}

			$wheres = array(
				'IN' => array(),
				'NOT IN' => array(),
			);
			foreach ($post_data_query as $post_data_parameters) {
				if (empty($post_data_parameters['key']) || empty($post_data_parameters['compare'])) {
					continue;
				}
				if (in_array($post_data_parameters['compare'], array('LIKE', 'NOT LIKE'))) {
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'] . '%';
				}

				if ($post_data_parameters['compare'] === 'length_less') {
					if ((int) $post_data_parameters['value'] < 1) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = 'REGEXP';
					$post_data_parameters['value'] = '^.{0,' . (int) $post_data_parameters['value'] . '}$';
				}
				if ($post_data_parameters['compare'] === 'length_higher') {
					if ((int) $post_data_parameters['value'] < 1) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = 'REGEXP';
					$post_data_parameters['value'] = '^.{' . (int) $post_data_parameters['value'] . ',}$';
				}

				if ($post_data_parameters['compare'] === 'OR') {
					$post_data_parameters['compare'] = 'REGEXP';
					$keywords = array_map('trim', explode(';', $post_data_parameters['value']));
					$post_data_parameters['value'] = '(' . implode('|', $keywords) . ')';
				}
				if ($post_data_parameters['compare'] === 'starts_with') {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value'] = $post_data_parameters['value'] . '%';
				}
				if ($post_data_parameters['compare'] === 'ends_with') {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'];
				}

				$group = 'IN';
				if (in_array($post_data_parameters['compare'], array('NOT LIKE'))) {
					$post_data_parameters['compare'] = 'LIKE';
					$group = 'NOT IN';
				} elseif (empty($post_data_parameters['value']) && $post_data_parameters['compare'] === '=') {
					$group = 'NOT IN';
				}
				if (empty($post_data_parameters['value'])) {
					$sql_where = "tt.taxonomy IN ('" . esc_sql($post_data_parameters['key']) . "')";
				} else {
					$sql_where = "tt.taxonomy IN ('" . esc_sql($post_data_parameters['key']) . "') AND t.name " . esc_sql($post_data_parameters['compare']) . " '" . esc_sql($post_data_parameters['value']) . "' ";
				}
				$sql = "SELECT tr.object_id
FROM $wpdb->terms AS t 
INNER JOIN $wpdb->term_taxonomy AS tt 
ON t.term_id = tt.term_id
INNER JOIN $wpdb->term_relationships AS tr
ON tr.term_taxonomy_id = tt.term_taxonomy_id
INNER JOIN $wpdb->posts AS p 
ON (p.ID = tr.object_id)

WHERE 1 = 1 AND p.post_type = '" . $wp_query->query['post_type'] . "' 

AND " . $sql_where . " 
  
GROUP BY tr.object_id";
				$wheres[$group][] = $sql;
			}

			foreach ($wheres as $operator => $queries) {
				foreach ($queries as $query) {
					$clauses['where'] .= " AND $wpdb->posts.ID $operator (" . $query . ")  ";
				}
			}
			return $clauses;
		}

		function _parse_meta_query_args($meta_query_args, $allowed_source = 'meta', &$query_args = array()) {
			foreach ($meta_query_args as $index => $meta_query) {
				// The JS doesn't send empty field so we ensure the value key exists
				if (!isset($meta_query['value'])) {
					$meta_query['value'] = '';
					$meta_query_args[$index]['value'] = '';
					if (isset($query_args['wpse_original_filters'])) {
						$query_args['wpse_original_filters']['meta_query'][$index]['value'] = '';
					}
				}
				if (is_array($meta_query['key'])) {
					$meta_query['key'] = array_filter($meta_query['key']);
				}
				if (empty($meta_query['key']) || empty($meta_query['compare']) || empty($meta_query['source'])) {
					unset($meta_query_args[$index]);
					if (isset($query_args['wpse_original_filters'])) {
						unset($query_args['wpse_original_filters']['meta_query'][$index]);
					}
				}
				if ($allowed_source && $meta_query['source'] !== $allowed_source) {
					unset($meta_query_args[$index]);
					continue;
				}


				if ($meta_query['compare'] === 'length_less') {
					if ((int) $meta_query['value'] < 1) {
						$meta_query['value'] = 1;
					}
					$meta_query_args[$index]['compare'] = 'REGEXP';
					$meta_query_args[$index]['value'] = '^.{0,' . (int) $meta_query['value'] . '}$';
				}
				if ($meta_query['compare'] === 'length_higher') {
					if ((int) $meta_query['value'] < 1) {
						$meta_query['value'] = 1;
					}
					$meta_query_args[$index]['compare'] = 'REGEXP';
					$meta_query_args[$index]['value'] = '^.{' . (int) $meta_query['value'] . ',}$';
				}
				if ($meta_query['compare'] === 'OR') {
					$meta_query_args[$index]['compare'] = 'REGEXP';
					$keywords = array_map('trim', explode(';', $meta_query['value']));
					$meta_query_args[$index]['value'] = '(' . implode('|', $keywords) . ')';
				}
				if ($meta_query['compare'] === 'starts_with') {
					$meta_query_args[$index]['compare'] = 'REGEXP';
					$meta_query_args[$index]['value'] = '^' . $meta_query['value'];
				}
				if ($meta_query['compare'] === 'ends_with') {
					$meta_query_args[$index]['compare'] = 'REGEXP';
					$meta_query_args[$index]['value'] .= '$';
				}

				if (in_array($meta_query['compare'], array('>', '>=', '<', '<=')) && is_numeric($meta_query['value'])) {
					$meta_query_args[$index]['type'] = 'NUMERIC';
				}
				if (empty($meta_query['value']) && in_array($meta_query['compare'], array('=', 'LIKE')) && $allowed_source === 'meta') {
					$not_exists = $meta_query;
					$not_exists['compare'] = 'NOT EXISTS';
					$meta_query_args[$index] = array(
						'relation' => 'OR',
						$meta_query,
						$not_exists
					);
				}

				$meta_query_args[$index]['value'] = $this->_maybe_convert_username_to_user_id('meta', $meta_query_args[$index]);
			}
			return $meta_query_args;
		}

		function _maybe_convert_username_to_user_id($allowed_source, $meta_query) {
			$post_type = VGSE()->helpers->get_provider_from_query_string();
			$spreadsheet_columns = VGSE()->helpers->get_unfiltered_provider_columns($post_type);
			$cell_key = $meta_query['key'];

			$column_uses_user_search = in_array($allowed_source, array('meta', 'post_data'), true) && !empty($spreadsheet_columns) && isset($spreadsheet_columns[$cell_key]) && !empty($spreadsheet_columns[$cell_key]['formatted']['source']) && $spreadsheet_columns[$cell_key]['formatted']['source'] === 'searchUsers';

			if (!empty($meta_query['value']) && $column_uses_user_search) {
				$column_settings = $spreadsheet_columns[$cell_key];
				$username = $meta_query['value'];
				// If it uses the regular saving (not prepare nor save callback)
				if (empty($column_settings['prepare_value_for_database']) && empty($column_settings['save_value_callback'])) {
					$meta_query['value'] = VGSE()->data_helpers->set_post('post_author', $username);
				} elseif (!empty($column_settings['prepare_value_for_database']) && empty($column_settings['save_value_callback'])) {
					$meta_query['value'] = call_user_func($column_settings['prepare_value_for_database'], null, $cell_key, $username, null, $column_settings, $spreadsheet_columns);
				}
			}
			return $meta_query['value'];
		}

		function _build_sql_wheres_for_data_table($post_data_query, $table_name) {
			$wheres = array();
			foreach ($post_data_query as $post_data_parameters) {
				if (empty($post_data_parameters['key']) || empty($post_data_parameters['compare'])) {
					continue;
				}
				if (!is_string($post_data_parameters['key']) || !is_string($post_data_parameters['compare'])) {
					continue;
				}
				if (in_array($post_data_parameters['compare'], array('LIKE', 'NOT LIKE'))) {
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'] . '%';
				}

				if ($post_data_parameters['compare'] === 'length_less') {
					if ((int) $post_data_parameters['value'] < 1) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = '<';
				}
				if ($post_data_parameters['compare'] === 'length_higher') {
					if ((int) $post_data_parameters['value'] < 1) {
						$post_data_parameters['value'] = 1;
					}
					$post_data_parameters['compare'] = '>';
				}
				if ($post_data_parameters['compare'] === 'OR') {
					$post_data_parameters['compare'] = 'REGEXP';
					$keywords = array_map('trim', explode(';', $post_data_parameters['value']));
					$post_data_parameters['value'] = '(' . implode('|', $keywords) . ')';
				}
				if ($post_data_parameters['compare'] === 'starts_with') {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value'] = $post_data_parameters['value'] . '%';
				}
				if ($post_data_parameters['compare'] === 'ends_with') {
					$post_data_parameters['compare'] = 'LIKE';
					$post_data_parameters['value'] = '%' . $post_data_parameters['value'];
				}
				// If the value is a date like Y-m-d 00:00:00 and uses the CONTAINS (LIKE) operator,
				// remove the time part because they always want to get CONTAINS=date without time
				if ($post_data_parameters['compare'] === 'LIKE' && preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/', $post_data_parameters['value'])) {
					$post_data_parameters['value'] = str_replace(' 00:00:00', '', $post_data_parameters['value']);
				}
				$post_data_parameters['value'] = $this->_maybe_convert_username_to_user_id('post_data', $post_data_parameters);

				if (in_array($post_data_parameters['compare'], array('<', '>'), true)) {
					$wheres[] = " LENGTH($table_name." . esc_sql($post_data_parameters['key']) . ") " . esc_sql($post_data_parameters['compare']) . "  " . (int) $post_data_parameters['value'];
				} else {
					$wheres[] = " $table_name." . esc_sql($post_data_parameters['key']) . " " . esc_sql($post_data_parameters['compare']) . " '" . esc_sql($post_data_parameters['value']) . "' ";
				}
			}
			return $wheres;
		}

		function add_advanced_post_data_query($clauses, $wp_query) {
			global $wpdb;
			if (empty($wp_query->query['wpse_original_filters']) || empty($wp_query->query['wpse_original_filters']['meta_query']) || !is_array($wp_query->query['wpse_original_filters']['meta_query'])) {
				return $clauses;
			}
			$post_data_query = wp_list_filter($wp_query->query['wpse_original_filters']['meta_query'], array(
				'source' => 'post_data'
			));
			if (empty($post_data_query)) {
				return $clauses;
			}

			// Remove the post_status clause added by wp_query automatically because we
			// have an advanced filter for the same field
			if (!empty($wp_query->query['wpse_original_post_statuses'])) {
				$post_type_object = get_post_type_object($wp_query->query['post_type']);

				$statuses_to_remove = $wp_query->query['wpse_original_post_statuses'];
				if ($post_type_object && current_user_can($post_type_object->cap->edit_published_posts)) {
					$allowed_statuses = array_merge(array('trash'), $statuses_to_remove);
				} else {
					$allowed_statuses = $statuses_to_remove;
				}
				$has_allowed_post_status_filter = false;
				foreach ($post_data_query as $index => $post_data_query_single) {
					if ($post_data_query_single['key'] !== 'post_status') {
						continue;
					}
					if (in_array($post_data_query_single['value'], $allowed_statuses, true)) {
						$has_allowed_post_status_filter = true;
					} else {
						unset($post_data_query[$index]);
					}
				}
				if ($has_allowed_post_status_filter) {
					$clauses['where'] = preg_replace("/AND \(\(" . $wpdb->posts . ".post_status[A-Za-z0-9 ='\._\-]+\)\)/", '', $clauses['where']);
				}
			}

			$wheres = $this->_build_sql_wheres_for_data_table($post_data_query, $wpdb->posts);
			if (!empty($wheres)) {
				$clauses['where'] .= ' AND ' . implode(' AND ', $wheres);
			}


			return $clauses;
		}

		function exclude_by_keyword($clauses, $wp_query) {
			if (!empty($wp_query->query['wpse_not_contains_keyword'])) {
				$clauses = WP_Sheet_Editor_Filters::get_instance()->add_search_by_keyword_clause($clauses, $wp_query->query['wpse_not_contains_keyword'], 'NOT LIKE', 'AND');
			}
			return $clauses;
		}

		/**
		 * Register frontend assets
		 */
		function register_assets() {
			wp_enqueue_script('advanced-filters_js', $this->plugin_url . 'assets/js/init.js', array(), VGSE()->version, false);
		}

		function register_filters($filters, $post_type) {
			if (VGSE()->helpers->get_current_provider()->is_post_type) {
				$taxonomies = get_object_taxonomies($post_type);
				if (!empty($taxonomies)) {
					$filters['taxonomy_term'] = array(
						'label' => '',
						'description' => '',
					);
				}
				$filters['date'] = array(
					'label' => '',
					'description' => '',
				);
				if (post_type_supports($post_type, 'page-attributes') && $post_type !== 'attachment') {
					$filters['post_parent'] = array(
						'label' => __('Parent', VGSE()->textname),
						'description' => ''
					);
				}
// Remove the post status field because they can search using the advanced filters
				if (isset($filters['post_status'])) {
					unset($filters['post_status']);
				}
			}
			return $filters;
		}

		function get_saved_searches($post_type) {

			$saved_items = get_option('vgse_saved_searches');
			if (empty($saved_items)) {
				$saved_items = array();
			}

			if (!isset($saved_items[$post_type])) {
				$saved_items[$post_type] = array();
			}
			return $saved_items[$post_type];
		}

		function save_search($data) {
			if (empty($data['name'])) {
				return;
			}
			$post_type = $data['post_type'];
			$saved_items = get_option('vgse_saved_searches');
			if (empty($saved_items)) {
				$saved_items = array();
			}

			if (!isset($saved_items[$post_type])) {
				$saved_items[$post_type] = array();
			}

			$same_name = wp_list_filter($saved_items[$post_type], array('name' => $data['name']));
			foreach ($same_name as $index => $same_name_search) {
				unset($saved_items[$post_type][$index]);
			}
			$saved_items[$post_type][] = $data;
			update_option('vgse_saved_searches', $saved_items);
		}

		/**
		 * Apply filters to wp-query args
		 * @param array $query_args
		 * @param array $data
		 * @return array
		 */
		function filter_posts($query_args, $data) {

			if (!empty($data['filters'])) {
				$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters($data);

				if (!empty($filters['search_name']) && current_user_can('manage_options')) {
					$this->save_search(array_merge($filters, array(
						'name' => $filters['search_name'],
						'post_type' => $query_args['post_type']
					)));
				}
				$query_args['wpse_original_filters'] = $filters;

				if (!empty($filters['apply_to']) && is_array($filters['apply_to'])) {
					$taxonomies_group = array();

					foreach ($filters['apply_to'] as $term) {
						$term_parts = explode('--', $term);
						if (count($term_parts) !== 2) {
							continue;
						}
						$taxonomy = $term_parts[0];
						$term = $term_parts[1];

						if (!isset($taxonomies_group[$taxonomy])) {
							$taxonomies_group[$taxonomy] = array();
						}
						$taxonomies_group[$taxonomy][] = $term;
					}

					$query_args['tax_query'] = array(
						'relation' => 'AND',
					);

					foreach ($taxonomies_group as $taxonomy_key => $terms) {
						$query_args['tax_query'][] = array(
							'taxonomy' => $taxonomy_key,
							'field' => 'slug',
							'terms' => $terms
						);
					}
				}

				if (!empty($filters['keyword_exclude'])) {
					$editor = VGSE()->helpers->get_provider_editor($query_args['post_type']);
					if ($editor->provider->is_post_type) {
						$query_args['wpse_not_contains_keyword'] = $filters['keyword_exclude'];
					} else {
						$post_id_exclude = $editor->provider->get_item_ids_by_keyword($filters['keyword_exclude'], $query_args['post_type'], 'LIKE');
						$query_args['post__not_in'] = $post_id_exclude;
					}
				}

				if (!empty($filters['post_parent'])) {
					$query_args['post_parent'] = (int) str_replace('page--', '', $filters['post_parent']);
				}
				if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
					$query_args['date_query'] = array(
						'inclusive' => true
					);
				}
				if (!empty($filters['post__in'])) {
					$post_ids_parts = preg_split('/\r\n|\r|\n|\t|\s|,/', $filters['post__in']);
					$post_ids = array();
					foreach ($post_ids_parts as $post_ids_part) {
						if (strpos($post_ids_part, '-') !== false) {
							$range_parts = array_filter(explode('-', $post_ids_part));
							if (count($range_parts) === 2) {
								$post_ids = array_merge($post_ids, range($range_parts[0], $range_parts[1]));
							}
						} else {
							$post_ids[] = $post_ids_part;
						}
					}
					$post_ids = array_map('intval', $post_ids);
					$query_args['post__in'] = (!empty($query_args['post__in'])) ? array_intersect($query_args['post__in'], $post_ids) : $post_ids;
				}
				if (!empty($filters['post_name__in'])) {
					$post_slugs = array_unique(array_filter(array_map('basename', array_map('trim', preg_split('/\r\n|\r|\n/', $filters['post_name__in'])))));
					if (!empty($post_slugs)) {
						$query_args['post_name__in'] = $post_slugs;
					}
				}
				if (!empty($filters['date_from'])) {
					$query_args['date_query']['after'] = $filters['date_from'];
				}
				if (!empty($filters['date_to'])) {
					$query_args['date_query']['before'] = $filters['date_to'];
				}
				if (!empty($filters['meta_query']) && is_array($filters['meta_query'])) {

					$all_advanced_filters = json_encode($filters['meta_query']);
					// If there is one advanced filter for post_status, we add a flag
					// and we will remove the post_status clause from the sql query later
					if (strpos($all_advanced_filters, '"key":"post_status"') !== false && isset($query_args['post_status']) && post_type_exists($query_args['post_type'])) {
						$query_args['wpse_original_post_statuses'] = $query_args['post_status'];
					}
					$filters['meta_query'] = $this->_parse_meta_query_args($filters['meta_query'], 'meta', $query_args);
					$query_args['meta_query'] = $filters['meta_query'];
				}
			}

			return $query_args;
		}

		function add_filters_fields($current_post_type, $filters) {
			?>

			<?php
			if (isset($filters['taxonomy_term'])) {
				?>
				<li class="<?php
				$labels = apply_filters('vg_sheet_editor/advanced_filters/taxonomy_labels', VGSE()->helpers->get_post_type_taxonomies_single_data($current_post_type, 'label'), $current_post_type);
				if (empty($labels)) {
					echo ' hidden';
				}

				if (count($labels) > 1) {
					$labels[count($labels) - 1] = ' or ' . end($labels);
				}
				?>">
					<label><?php printf(__('Enter %s', VGSE()->textname), implode(', ', array_unique($labels))); ?> <a href="#" class="tipso" data-tipso="<?php _e('Enter the names of ' . implode(', ', $labels)); ?>">( ? )</a></label>
					<select data-placeholder="<?php _e('Category name...', VGSE()->textname); ?>" name="apply_to[]" class="select2"  multiple data-remote="true" data-action="vgse_search_taxonomy_terms" data-min-input-length="4">

					</select>
				</li>
			<?php } ?>

			<?php if (isset($filters['post_parent'])) { ?>
				<li>
					<label><?php echo $filters['post_parent']['label']; ?>  <?php if (!empty($filters['post_parent']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['post_parent']['description']; ?>">( ? )</a><?php } ?></label>
					<select name="post_parent" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo $current_post_type; ?>" data-nonce="<?php echo wp_create_nonce('bep-nonce'); ?>" data-placeholder="<?php _e('Select...', VGSE()->textname); ?> " class="select2" multiple>
						<option></option>
					</select> 									
				</li>
				<?php
			}
		}

		function get_advanced_filters_fields($current_post_type, $filters) {
			global $wpdb;

			$cache_key = 'vgse_advanced_filter_fields' . $current_post_type;
			$out = get_transient($cache_key);
			if (method_exists(VGSE()->helpers, 'can_rescan_db_fields') && VGSE()->helpers->can_rescan_db_fields($current_post_type)) {
				$out = false;
			}

			if (!$out) {
				$maximum_fields = (!empty(VGSE()->options['maximum_advanced_filters_fields'])) ? (int) VGSE()->options['maximum_advanced_filters_fields'] : 1000;
				$all_meta_keys = apply_filters('vg_sheet_editor/advanced_filters/all_meta_keys', VGSE()->helpers->get_all_meta_keys($current_post_type, $maximum_fields), $current_post_type, $filters);

// post data and taxonomy advanced filters are available for post types only
				if (VGSE()->helpers->get_current_provider()->is_post_type) {
					$taxonomy_keys = VGSE()->helpers->get_post_type_taxonomies_single_data($current_post_type, 'name');
					$item_raw = $wpdb->get_row("SELECT * FROM $wpdb->posts LIMIT 1", ARRAY_A);
					$item = (is_array($item_raw)) ? array_keys($item_raw) : array();
				} else {
					$item = array();
					$taxonomy_keys = array();
				}

				$all_fields = array(
					'meta' => array_unique($all_meta_keys),
					'post_data' => array_unique($item),
					'taxonomy_keys' => array_unique($taxonomy_keys)
				);
				$out = apply_filters('vg_sheet_editor/advanced_filters/all_fields_groups', $all_fields, $current_post_type, $filters);
				set_transient($cache_key, $out, DAY_IN_SECONDS);
			}


			return $out;
		}

		function _render_advanced_filter_row($current_post_type, $filters, $selected_values = array(), $wrapper = 'li') {
			$default_selected_values = array(
				'source' => '',
				'key' => '',
				'value' => '',
				'compare' => '',
			);
			$selected_values = wp_parse_args($selected_values, $default_selected_values);
			?>
			<<?php echo esc_html($wrapper); ?> class="base advanced-field" style="display: none;">
			<div class="fields-wrap">
				<div class="field-wrap search-field-wrap">
					<label><?php _e('Field', VGSE()->textname); ?></label>
					<input type="hidden" name="meta_query[][source]" class="field-source" value="<?php echo esc_attr($selected_values['source']); ?>">
					<select name="meta_query[][key]" data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class="select2 wpse-advanced-filters-field-selector">
						<option value="" <?php selected($selected_values['key'], ''); ?>>- -</option>
						<?php
						$all_fields = $this->get_advanced_filters_fields($current_post_type, $filters);
						if (!empty($all_fields) && is_array($all_fields)) {
							if (method_exists(VGSE()->helpers, 'get_unfiltered_provider_columns')) {
								$columns = VGSE()->helpers->get_unfiltered_provider_columns($current_post_type);
							} else {
								// Backwards compatibility
								$unfiltered_columns = WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns;

								$columns = isset($unfiltered_columns[$current_post_type]) ? $unfiltered_columns[$current_post_type] : array();
							}
							$default_field_labels = array(
								'_edit_last' => __('Last edit', VGSE()->textname),
								'_wp_old_slug' => __('Old slug', VGSE()->textname),
								'post_author' => __('Author', VGSE()->textname),
								'post_date_gmt' => __('Date (GMT)', VGSE()->textname),
								'post_modified_gmt' => __('Modified Date (GMT)', VGSE()->textname),
								'comment_status' => __('Comments', VGSE()->textname),
								'ping_status' => __('Ping status', VGSE()->textname),
								'post_name' => __('URL Slug', VGSE()->textname),
								'post_parent' => __('Page Parent', VGSE()->textname),
								'post_mime_type' => __('Mime type', VGSE()->textname),
								'comment_count' => __('Comment count', VGSE()->textname),
								'edd_variable_prices' => __('EDD Variable Prices', VGSE()->textname),
								'edd_download_files' => __('EDD Download Files', VGSE()->textname),
							);
							foreach ($all_fields as $group_key => $group_fields) {
								foreach ($group_fields as $field_key) {
									$field_label = '';
									if (isset($columns[$field_key]) && isset($columns[$field_key]['title'])) {
										$field_label = $columns[$field_key]['title'];
									} elseif (isset($default_field_labels[$field_key])) {
										$field_label = $default_field_labels[$field_key];
									}
									$label = ( $field_label ) ? $field_label . " ($field_key)" : $field_key;

									echo '<option ' . selected($selected_values['key'], $field_key, false) . ' value="' . esc_attr($field_key) . '" data-source="' . esc_attr($group_key) . '" ';
									echo '>' . esc_html($label) . "</option>";
								}
							}
						}
						?>
					</select>

					<?php if (is_admin() && current_user_can('manage_options') && empty(VGSE()->options['enable_simple_mode'])) { ?>
						<br/><span class="search-tool-missing-column-tip"><small><?php printf(__('A field is missing? <a href="%s">Click here</a>', VGSE()->textname), esc_url(add_query_arg('wpse_rescan_db_fields', $current_post_type))); ?></small></span>
					<?php } ?>
				</div>
				<div class="field-wrap search-operator-wrap">
					<label><?php _e('Operator', VGSE()->textname); ?></label>
					<select name="meta_query[][compare]" data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class=" wpse-advanced-filters-operator-selector">
						<?php $this->render_operator_options($selected_values['compare']); ?>
					</select>
				</div>
				<div class="field-wrap search-value-wrap">
					<label><?php _e('Value', VGSE()->textname); ?></label>
					<input name="meta_query[][value]" class=" wpse-advanced-filters-value-selector" value="<?php echo esc_attr($selected_values['value']); ?>"/>
				</div>

				<div class="fields-wrap search-row-add-new">
					<a href="#" class="button new-advanced-filter"><?php _e('Add new', VGSE()->textname); ?></a>
				</div>
				<div class="fields-wrap search-row-remove-wrap">
					<a href="#" class="button remove-advanced-filter"><?php _e('X', VGSE()->textname); ?></a>
				</div>
			</div>
			</<?php echo esc_html($wrapper); ?>>
			<?php
		}

		function add_advanced_filters_fields($current_post_type, $filters) {
			?>

			<p class="wpse-advanced-filters-toggle"><label><input type="checkbox" class="advanced-filters-toggle"> <?php _e('Enable advanced filters', VGSE()->textname); ?></label></p>
			<div class="advanced-filters"  style="display: none;">
				<?php if (empty(VGSE()->options['enable_simple_mode'])) { ?>
					<h3><?php _e('Advanced search', VGSE()->textname); ?></h3>
				<?php } ?>
				<p class="advanced-filters-message"><?php _e('You can search by any field using operators. I.e. price > 100, image != (empty)', VGSE()->textname); ?></p>
				<ul class="unstyled-list advanced-filters-list">
					<?php
					$this->_render_advanced_filter_row($current_post_type, $filters);
					do_action('vg_sheet_editor/filters/after_advanced_fields', $current_post_type);
					?>
				</ul>

				<div class="fields-wrap" style="display: none;"><a href="#" class="button new-advanced-filter"><?php _e('Add new', VGSE()->textname); ?></a></div>
				<hr>
				<ul class="unstyled-list bottom-advanced-filters">
					<?php
					do_action('vg_sheet_editor/filters/after_advanced_fields_section', $current_post_type);

					if (empty(VGSE()->options['enable_simple_mode'])) {
						?>
						<li class="exclude-keyword">
							<label><?php echo __('NOT Contains this keyword', VGSE()->textname); ?>  <a href="#" class="tipso" data-tipso="<?php echo __('Enter a keyword to exclude posts, separate multiple keywords with a semicolon (;)', VGSE()->textname); ?>">( ? )</a></label>
							<input type="text" name="keyword_exclude">
						</li>
						<li class="post--in">
							<label><?php _e('Find these IDs:', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="<?php _e('Enter IDs separated by commas, spaces, new lines, or tabs. You can use ID ranges like 20-50 as a shortcut.', VGSE()->textname); ?>">( ? )</a></label>
							<textarea name="post__in"></textarea>
						</li>
						<?php if (VGSE()->helpers->get_current_provider()->is_post_type) { ?>
							<li class="post-name--in">
								<label><?php _e('Find these URLs:', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="<?php _e('Enter one URL per line', VGSE()->textname); ?>">( ? )</a></label>
								<textarea name="post_name__in"></textarea>
							</li>
							<li class="date-range">
								<label><?php _e('Date range from', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="<?php _e('Show items published between these dates'); ?>">( ? )</a></label><input type="date" name="date_from" /><br/> <?php _e('to', VGSE()->textname); ?><br/> <input type="date" name="date_to" />
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<?php
		}

		function _get_allowed_operators() {
			return array(
				'=',
				'!=',
				'<',
				'<=',
				'>',
				'>=',
				'OR',
				'LIKE',
				'NOT LIKE',
				'starts_with',
				'ends_with',
				'length_less',
				'length_higher',
				'REGEXP',
			);
		}

		function render_operator_options($selected = '') {
			?>
			<option value="=" <?php selected(in_array($selected, array('', '='))); ?>>=</option>
			<option value="!=" <?php selected($selected, '!='); ?>>!=</option>
			<option value="<" <?php selected($selected, '<'); ?> ><</option>
			<option value="<=" <?php selected($selected, '<='); ?> ><=</option>
			<option value=">"  <?php selected($selected, '>'); ?>>></option>
			<option value=">="  <?php selected($selected, '>='); ?>>>=</option>
			<option value="OR"  <?php selected($selected, 'OR'); ?>data-custom-label="ANY"><?php _e('Any of these values (Enter multiple values separated by ;)', VGSE()->textname); ?></option>
			<option value="LIKE"  <?php selected($selected, 'LIKE'); ?>><?php _e('CONTAINS', VGSE()->textname); ?></option>
			<option value="NOT LIKE"  <?php selected($selected, 'NOT LIKE'); ?>><?php _e('NOT CONTAINS', VGSE()->textname); ?></option>
			<option value="starts_with" <?php selected($selected, 'starts_with'); ?> ><?php _e('STARTS WITH', VGSE()->textname); ?></option>
			<option value="ends_with"  <?php selected($selected, 'ends_with'); ?>><?php _e('ENDS WITH', VGSE()->textname); ?></option>
			<option value="length_less"  <?php selected($selected, 'length_less'); ?>><?php _e('CHARACTER LENGTH <', VGSE()->textname); ?></option>
			<option value="length_higher"  <?php selected($selected, 'length_higher'); ?>><?php _e('CHARACTER LENGTH >', VGSE()->textname); ?></option>
			<option value="REGEXP"  <?php selected($selected, 'REGEXP'); ?>><?php _e('REGEXP', VGSE()->textname); ?></option>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Advanced_Filters::$instance) {
				WP_Sheet_Editor_Advanced_Filters::$instance = new WP_Sheet_Editor_Advanced_Filters();
				WP_Sheet_Editor_Advanced_Filters::$instance->init();
			}
			return WP_Sheet_Editor_Advanced_Filters::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_advanced_filters_init');

	function vgse_advanced_filters_init() {
		return WP_Sheet_Editor_Advanced_Filters::get_instance();
	}

}
