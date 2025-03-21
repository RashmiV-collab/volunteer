<?php

class VGSE_Provider_Custom_table extends VGSE_Provider_Abstract {

	static private $instance = false;
	var $key = 'custom_table';
	var $is_post_type = false;
	var $last_request = null;
	static $data_store = array();
	var $args = array();

	private function __construct() {
		
	}

	function get_arg($key, $post_type) {
		$this->maybe_build_table_schema($post_type);

		return isset($this->args[$post_type][$key]) ? $this->args[$post_type][$key] : false;
	}

	function get_provider_read_capability($post_type_key) {
		return 'manage_options';
	}

	function delete_meta_key($old_key, $post_type) {
		global $wpdb;
		$meta_table_name = VGSE()->helpers->get_current_provider()->get_meta_table_name($post_type);
		if (!$meta_table_name) {
			return 0;
		}

		$result = $wpdb->delete($meta_table_name, array(
			'meta_key' => $old_key
		));
		return $result;
	}

	function rename_meta_key($old_key, $new_key, $post_type) {
		global $wpdb;
		$meta_table_name = $this->get_meta_table_name($post_type);
		if (!$meta_table_name) {
			return 0;
		}
		if (is_string($post_type)) {
			$post_type = array($post_type);
		}
		$modified = $wpdb->query("UPDATE $meta_table_name pm 
SET pm.meta_key = '" . esc_sql($new_key) . "' 
WHERE pm.meta_key = '" . esc_sql($old_key) . "' ");
		return $modified;
	}

	function get_provider_edit_capability($post_type_key) {
		return 'manage_options';
	}

	function get_provider_delete_capability($post_type_key) {
		return 'manage_options';
	}

	function init() {
		
	}

	function get_total($post_type = null) {
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($post_type));
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return  Foo A single instance of this class.
	 */
	static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	function get_post_data_table_id_key($post_type = null) {
		global $wpdb;
		$result = $wpdb->get_row("SHOW KEYS FROM " . esc_sql($post_type) . " WHERE Key_name = 'PRIMARY'", ARRAY_A);

		if (!$result) {
			$int_column = $wpdb->get_row("SHOW COLUMNS FROM " . esc_sql($post_type) . "  WHERE Type LIKE '%int%'");
			if (!empty($int_column)) {
				$result = array('Column_name' => $int_column->Field);
			}
		}

		if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
			$result['Column_name'] = VGSE()->helpers->sanitize_table_key($result['Column_name']);
		}
		return $result['Column_name'];
	}

	function get_meta_table_post_id_key($post_type = null) {
		if (!$post_type) {
			$post_type = VGSE()->helpers->get_provider_from_query_string();
		}

		$post_id_key = apply_filters('vgse_sheet_editor/provider/custom_table/meta_table_post_id_key', null, $post_type);
		if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
			$post_id_key = VGSE()->helpers->sanitize_table_key($post_id_key);
		}
		return $post_id_key;
	}

	function get_meta_table_name($post_type = null) {
		if (!$post_type) {
			$post_type = VGSE()->helpers->get_provider_from_query_string();
		}

		$table_name = apply_filters('vgse_sheet_editor/provider/custom_table/meta_table_name', null, $post_type);
		if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
			$table_name = VGSE()->helpers->sanitize_table_key($table_name);
		}
		return $table_name;
	}

	function prefetch_data($post_ids, $post_type, $spreadsheet_columns) {
		
	}

	function get_item_terms($id, $table_name) {
		$raw_value = '';
		return apply_filters('vg_sheet_editor/provider/custom_table/get_items_terms', $raw_value, $id, $table_name);
	}

	function get_statuses() {
		return array();
	}

	function get_items($query_args) {
		$post_type = $query_args['post_type'];
		$post_keys_to_remove = array(
			'post_status',
			'author',
			'tax_query',
		);
		foreach ($post_keys_to_remove as $post_key_to_remove) {
			if (isset($query_args[$post_key_to_remove])) {
				unset($query_args[$post_key_to_remove]);
			}
		}

		$primary_key = $this->get_post_data_table_id_key($post_type);

		if (isset($query_args['posts_per_page']) && $query_args['posts_per_page'] < 0) {
			$query_args['paginated'] = false;
		}
		if (isset($query_args['post__in'])) {
			$query_args[$primary_key] = $query_args['post__in'];
		}
		if (isset($query_args['post__not_in'])) {
			$query_args[$primary_key . '__not'] = $query_args['post__not_in'];
		}
		if (!empty($query_args['fields']) && $query_args['fields'] === 'ids') {
			$query_args['query_select'] = $primary_key;
		}
		if (!empty($query_args['s'])) {
			$query_args['s'] = $query_args['s'];
		}
		$query_args['count_total'] = false;

		$rows = $this->_get_rows($query_args);

		$request = $this->last_request;
		$total = (int) $this->_get_rows(array_merge($query_args, array(
					'query_select' => 'COUNT(*)',
					'method' => 'get_var',
					'paginated' => false,
		)));

		$out = (object) array();
		$out->found_posts = $total;
		$out->posts = array();
		$out->request = $request;
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$row = $this->_format_item($row, $post_type);
				$out->posts[] = $row;
			}

			if (!empty($query_args['fields']) && $query_args['fields'] === 'ids') {
				$out->posts = wp_list_pluck($out->posts, 'ID');
			}
		}
// $out->posts must contain an array of objects		

		return $out;
	}

	function maybe_build_table_schema($post_type) {
		global $wpdb;
		if (!empty($this->args[$post_type])) {
			return;
		}
		$columns = $wpdb->get_results("SHOW COLUMNS FROM " . esc_sql($post_type), ARRAY_A);

		$schema = array();

		foreach ($columns as $column) {
			$column_key = $column['Field'];
			$type = 'text';
			if (strpos($column['Type'], 'int') !== false) {
				$type = 'numeric';
			} elseif ($column['Type'] === 'datetime') {
				$type = 'dates';
			} elseif (strpos($column['Type'], 'decimal') !== false) {
				$type = 'float';
			}

			$sample_values = $wpdb->get_col("SELECT $column_key FROM $post_type ORDER BY $column_key DESC GROUP BY $column_key LIMIT 4");
			$all_values = implode(', ', $sample_values);
			// Set safe_html type if the sample values contain html
			if ($type === 'text' && wp_strip_all_tags($all_values) !== $sample_values) {
				$type = 'safe_html';
			}

			$schema[$column_key] = array(
				'default_value_insert' => $column['Default'] !== null ? $column['Default'] : '',
				'type' => $type,
				'column_key' => $column_key
			);
		}
		if (empty($schema)) {
			return;
		}
		$searchable_columns = $this->get_searchable_column_keys($post_type);
		$primary_column = $this->get_post_data_table_id_key($post_type);
		$first_column = current($schema);
		if (empty($primary_column) && (empty($first_column) || $first_column['type'] !== 'numeric')) {
			return;
		}
		$this->args[$post_type] = apply_filters('vg_sheet_editor/provider/custom_table/table_schema', array(
			'default_order_by' => (!empty($primary_column)) ? $primary_column : $first_column['column_key'],
			'default_order' => 'DESC',
			'table_name' => $post_type,
			's_columns' => $searchable_columns,
			'columns' => $schema
		));
	}

	function _get_rows($args) {
		global $wpdb;
		if (empty($args['post_type'])) {
			$args['post_type'] = VGSE()->helpers->get_provider_from_query_string();
		}
		$defaults = array(
			's' => '',
			'posts_per_page' => 10,
			'paged' => 1,
			'paginated' => true,
			'query_select' => '*',
			'order_by' => $this->get_arg('default_order_by', $args['post_type']),
			'order' => $this->get_arg('default_order', $args['post_type']),
			'group_by' => '',
			'method' => 'get_results',
		);
		$args = wp_parse_args($args, $defaults);
		// Sort array by key to normalize the cache
		ksort($args);

		extract($args);

		// sanitization
		if (!empty($s)) {
			$s = sanitize_text_field($s);
		}
		if (!empty($paged)) {
			$paged = intval($paged);
		}
		if (!empty($posts_per_page)) {
			$posts_per_page = intval($posts_per_page);
		}


		$sql = "SELECT " . $query_select . " FROM " . esc_sql($this->get_arg('table_name', $args['post_type'])) . " as t ";

		$wheres = array();

		if (!empty($s)) {
			$s = esc_sql($s);

			$s_conditions = array();
			foreach ($this->get_arg('s_columns', $args['post_type']) as $s_column) {
				$s_conditions[] = esc_sql($s_column) . " LIKE '%$s%'";
			}
			$s_sql = '( ' . implode(' OR ', $s_conditions) . ' ) ';

			$wheres[] = $s_sql;
		}

		foreach ($this->get_arg('columns', $args['post_type']) as $column_key => $column) {

			if (!isset($args[$column_key])) {
				continue;
			}
			if (empty($args[$column_key])) {
				$args[$column_key] = $column['default_value_get'];
			}

			if ($column['type'] === 'numeric') {

				if (is_array($args[$column_key])) {
					$wheres[] = "$column_key IN ( " . implode(',', array_map('intval', $args[$column_key])) . " )";
				} else {
					$wheres[] = "$column_key = " . intval($args[$column_key]);
				}
			} elseif ($column['type'] === 'float') {

				if (is_array($args[$column_key])) {
					$wheres[] = "$column_key IN ( " . implode(',', array_map('floatval', $args[$column_key])) . " )";
				} else {
					$wheres[] = "$column_key = " . floatval($args[$column_key]);
				}
			} elseif ($column['type'] === 'dates') {
				if (!empty($args[$column_key])) {
					$wheres[] = "$column_key LIKE '%" . esc_sql($args[$column_key]) . "%' ";
				} else {
					// devolver los que tienen un tripSection futuro
					if (!empty($args[$column_key . '_after'])) {
						$wheres[] = "$column_key > '" . esc_sql($args[$column_key . '_after']) . "'";
					}
					if (!empty($args[$column_key . '_before'])) {
						$wheres[] = "$column_key < '" . esc_sql($args[$column_key . '_before']) . "'";
					}
				}
			} else {

				if (is_array($args[$column_key])) {
					$wheres[] = "$column_key IN ( '" . implode("','", array_map('wp_kses_post', $args[$column_key])) . "' )";
				} else {
					$wheres[] = "$column_key = '" . wp_kses_post($args[$column_key]) . "'";
				}
			}
		}

		// Not clausses
		foreach ($this->get_arg('columns', $args['post_type']) as $column_key => $column) {
			$not_arg_key = $column_key . '__not';
			if (empty($args[$not_arg_key])) {
				continue;
			}

			if ($column['type'] === 'numeric') {

				if (is_array($args[$not_arg_key])) {
					$wheres[] = "$column_key NOT IN ( " . implode(',', array_map('intval', $args[$not_arg_key])) . " )";
				} else {
					$wheres[] = "$column_key != " . intval($args[$not_arg_key]);
				}
			} elseif ($column['type'] === 'float') {

				if (is_array($args[$not_arg_key])) {
					$wheres[] = "$column_key NOT IN ( " . implode(',', array_map('floatval', $args[$not_arg_key])) . " )";
				} else {
					$wheres[] = "$column_key != " . floatval($args[$not_arg_key]);
				}
			} elseif ($column['type'] === 'dates') {
				if (!empty($args[$not_arg_key])) {
					$wheres[] = "$column_key NOT LIKE '%" . esc_sql($args[$not_arg_key]) . "%' ";
				} else {
					// devolver los que tienen un tripSection futuro
					if (!empty($args[$not_arg_key . '_after'])) {
						$wheres[] = "$column_key < '" . esc_sql($args[$not_arg_key . '_after']) . "'";
					}
					if (!empty($args[$not_arg_key . '_before'])) {
						$wheres[] = "$column_key > '" . esc_sql($args[$not_arg_key . '_before']) . "'";
					}
				}
			} else {

				if (is_array($args[$not_arg_key])) {
					$wheres[] = "$column_key NOT IN ( '" . implode("','", array_map('wp_kses_post', $args[$not_arg_key])) . "' )";
				} else {
					$wheres[] = "$column_key != '" . wp_kses_post($args[$not_arg_key]) . "'";
				}
			}
		}

		$sql .= (!empty($wheres) ) ? ' WHERE ' . implode(' AND ', $wheres) : '';
		if (!empty($group_by)) {
			$sql .= ' GROUP BY ' . esc_sql($group_by);
		}

		if (!empty($order_by) && !empty($order)) {
			$sql .= ' ORDER BY t.' . esc_sql($order_by) . ' ' . esc_sql(strtoupper($order));
		}

		if ($paginated && !empty($paged) && !empty($posts_per_page)) {
			$offset = ( $paged < 2 ) ? 0 : ( $paged - 1) * (int) $posts_per_page;
			$sql .= " LIMIT " . esc_sql($offset) . "," . esc_sql($posts_per_page);
		}

		if (strpos($sql, 'GROUP BY') !== false && strpos($sql, 'COUNT(*)') !== false) {
			$sql = 'SELECT COUNT(*) FROM (' . str_replace('COUNT(*)', '*', $sql) . ') tt';
		}

		$sql = apply_filters('vg_sheet_editor/provider/custom_table/get_rows_sql', $sql, $args, $this->args[$args['post_type']]);
		$results = ( $method === 'get_results' ) ? $wpdb->get_results($sql, OBJECT) : $wpdb->get_var($sql);
		$this->last_request = $sql;

		return apply_filters('vg_sheet_editor/provider/custom_table/get_rows_results', $results, $args, $this->args[$args['post_type']], $sql);
	}

	function _insert_row($data) {
		global $wpdb;

		if (empty($data['post_type'])) {
			$data['post_type'] = VGSE()->helpers->get_provider_from_query_string();
		}

		$primary_column_key = $this->get_post_data_table_id_key($data['post_type']);
		$original_data = $data;
		$context = (!empty($data['ID'])) ? 'update' : 'insert';
		$item_id = ( $context === 'update') ? (int) $data['ID'] : null;
		$new_data_format = array();
		$new_data = array();
		foreach ($this->get_arg('columns', $data['post_type']) as $column_key => $column) {
			if (empty($data[$column_key])) {
				$data[$column_key] = $column['default_value_insert'];
			}
			if ($column['type'] === 'numeric') {
				$new_data[$column_key] = (int) $data[$column_key];
				$new_data_format[$column_key] = '%d';
			} elseif ($column['type'] === 'float') {
				$new_data[$column_key] = (float) $data[$column_key];
				$new_data_format[$column_key] = '%s';
			} elseif ($column['type'] === 'slug') {
				$new_data[$column_key] = sanitize_title($data[$column_key]);
				$new_data_format[$column_key] = '%s';
			} elseif ($column['type'] === 'safe_html') {
				$new_data[$column_key] = wp_kses_post($data[$column_key]);
				$new_data_format[$column_key] = '%s';
			} else {
				$new_data[$column_key] = sanitize_text_field($data[$column_key]);
				$new_data_format[$column_key] = '%s';
			}
		}

		if ($context === 'insert') {

			$new_data = apply_filters('saas/db_table_manager/insert_data', $new_data, $original_data, $this->args[$data['post_type']]);
			$new_data_format = apply_filters('saas/db_table_manager/insert_data_format', $new_data_format, $original_data, $this->args[$data['post_type']]);

			if (isset($new_data['ID'])) {
				unset($new_data['ID']);
			}
			if (isset($new_data_format['ID'])) {
				unset($new_data_format['ID']);
			}

			$result = $wpdb->insert(
					$this->get_arg('table_name', $data['post_type']), $new_data, $new_data_format);
		} else {
			// si es una actualización de datos actualizamos solo los datos que fueron definidos
			// durante la solicitud a la API. De esta forma evitamos borrar datos que se omitieron
			// porque no se quieren actualizar , los borramos solo si se llamó a la API con los 
			// valores vacíos
			$new_data = array_intersect_key($new_data, $original_data);
			$new_data_format = array_values(array_intersect_key($new_data_format, $new_data));

			$new_data = apply_filters('saas/db_table_manager/update_data', $new_data, $original_data, $this->args[$data['post_type']]);
			$new_data_format = apply_filters('saas/db_table_manager/update_data_format', $new_data_format, $original_data, $this->args[$data['post_type']]);

			if (!empty($new_data)) {
				if (isset($new_data['ID'])) {
					$new_data[$primary_column_key] = $new_data['ID'];
					unset($new_data['ID']);
				}
				if (isset($new_data_format['ID'])) {
					$new_data_format[$primary_column_key] = $new_data_format['ID'];
					unset($new_data_format['ID']);
				}
				$result = $wpdb->update(
						$this->get_arg('table_name', $data['post_type']), $new_data, array(
					$primary_column_key => (int) $original_data['ID']
						), $new_data_format, array('%d'));
			} else {
				$result = true;
			}
		}

		if ($result === false) {
			return false;
		}

		$id = (!empty($data['ID']) ) ? (int) $data['ID'] : $wpdb->insert_id;

		if (!$id) {
			return false;
		}

		do_action('saas/db_table_manager/after_insert_row', $id, $new_data, $original_data, $this->args[$data['post_type']]);

		return $id;
	}

	function _delete_row($id, $post_type) {
		global $wpdb;

		$result = $wpdb->delete($this->get_arg('table_name', $post_type), array(
			$this->get_post_data_table_id_key($post_type) => (int) $id
				), array(
			'%d'
		));
	}

	function _format_item($row, $post_type) {
		$primary_key = $this->get_post_data_table_id_key($post_type);
		if (is_object($row)) {
			$row->post_type = $post_type;
			$row->provider = $post_type;
			$row->ID = (int) $row->$primary_key;
			$row->$primary_key = (int) $row->$primary_key;
		} else {
			$row['post_type'] = $post_type;
			$row['provider'] = $post_type;
			$row['ID'] = (int) $row[$primary_key];
			$row[$primary_key] = (int) $row[$primary_key];
		}
		return $row;
	}

	function get_item($id, $format = null) {
		$post_type = VGSE()->helpers->get_provider_from_query_string();
		$rows = $this->_get_rows(array(
			'posts_per_page' => 1,
			$this->get_post_data_table_id_key($post_type) => $id
		));

		if (empty($rows)) {
			return false;
		}
		$row = current($rows);
		$row = $this->_format_item($row, $post_type);

		if ($format == OBJECT) {
			$row = (object) $row;
		}
		return apply_filters('vg_sheet_editor/provider/custom_table/get_item', $row, $id, $format);
	}

	function _get_meta_value_column_key($post_type) {
		return VGSE()->helpers->sanitize_table_key(apply_filters('vg_sheet_editor/provider/custom_table/meta_value_column_key', 'meta_value', $post_type));
	}

	function _get_meta_key_column_key($post_type) {
		return VGSE()->helpers->sanitize_table_key(apply_filters('vg_sheet_editor/provider/custom_table/meta_key_column_key', 'meta_key', $post_type));
	}

	function get_item_meta($id, $key, $single = true, $context = 'save', $bypass_cache = false) {
		global $wpdb;
		$value = '';
		$meta_table_name = VGSE()->helpers->get_current_provider()->get_meta_table_name();
		$meta_table_id_column = VGSE()->helpers->get_current_provider()->get_meta_table_post_id_key();
		$post_type = VGSE()->helpers->get_provider_from_query_string();
		$meta_value_column = $this->_get_meta_value_column_key($post_type);
		$meta_key_column = $this->_get_meta_key_column_key($post_type);

		if ($meta_table_id_column && $meta_table_name) {
			$value = $wpdb->get_var($wpdb->prepare("SELECT `$meta_value_column` FROM $meta_table_name WHERE `$meta_key_column` = %s AND `$meta_table_id_column` = %d LIMIT 1", $key, $id));
		}
		return apply_filters('vg_sheet_editor/provider/custom_table/get_item_meta', $value, $id, $key, $single, $context);
	}

	function get_item_data($id, $key) {
		$item = $this->get_item($id);
		$value = (isset($item->$key)) ? $item->$key : '';
		return apply_filters('vg_sheet_editor/provider/custom_table/get_item_data', $value, $id, $key, true, 'read');
	}

	function update_item_data($values, $wp_error = false) {
		global $wpdb;
		$post_type = VGSE()->helpers->get_provider_from_query_string();
		$edit_capability = $this->get_provider_edit_capability($post_type);
		if (!current_user_can($edit_capability)) {
			return false;
		}

		$id = $values['ID'];
		if (!empty($values['wpse_status']) && $values['wpse_status'] === 'delete') {
			$this->_delete_row($id, $post_type);
			VGSE()->deleted_rows_ids[] = (int) $id;
		} else {
			$this->_insert_row($values);
		}


		return $id;
	}

	function update_item_meta($id, $key, $value) {
		global $wpdb;
		$meta_table_name = VGSE()->helpers->get_current_provider()->get_meta_table_name();
		$meta_table_id_column = VGSE()->helpers->get_current_provider()->get_meta_table_post_id_key();

		if (!$meta_table_name || !$meta_table_id_column) {
			return false;
		}
		$post_type = VGSE()->helpers->get_provider_from_query_string();
		$meta_value_column = $this->_get_meta_value_column_key($post_type);
		$meta_key_column = $this->_get_meta_key_column_key($post_type);

		$meta_row_exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $meta_table_name WHERE $meta_key_column = %s AND $meta_table_id_column = %d", $key, $id));

		if ($meta_row_exists) {
			$wpdb->update($meta_table_name, array(
				$meta_value_column => wp_kses_post(apply_filters('vg_sheet_editor/provider/custom_table/update_item_meta', $value, $id, $key))
					), array(
				$meta_key_column => $key,
				$meta_table_id_column => $id
			));
		} else {
			$wpdb->insert($meta_table_name, array(
				$meta_value_column => wp_kses_post(apply_filters('vg_sheet_editor/provider/custom_table/update_item_meta', $value, $id, $key)),
				$meta_key_column => $key,
				$meta_table_id_column => $id
			));
		}

		return true;
	}

	function set_object_terms($post_id, $terms_saved, $key) {
		// Custom tables don't have taxonomies
	}

	function get_object_taxonomies($post_type = null) {
		return get_taxonomies(array(), 'objects');
	}

	function create_item($values) {
		$post_type = VGSE()->helpers->get_provider_from_query_string();
		$edit_capability = $this->get_provider_edit_capability($post_type);
		if (!current_user_can($edit_capability)) {
			return false;
		}

		$new_id = $this->_insert_row($values);

		return $new_id;
	}

	function get_searchable_column_keys($post_type) {
		global $wpdb;

		$all_columns = $wpdb->get_results("SHOW COLUMNS FROM " . esc_sql($post_type), ARRAY_A);
		$out = array();
		foreach ($all_columns as $column) {
			// We only search in date, text, varchar columns (text columns)
			if (!preg_match('/date|text|varchar/', $column['Type'])) {
				continue;
			}
			$out[] = $column['Field'];
		}
		return $out;
	}

	function get_item_ids_by_keyword($keyword, $post_type, $operator = 'LIKE') {
		global $wpdb;
		$operator = ( $operator === 'LIKE') ? 'LIKE' : 'NOT LIKE';

		$primary_key_column = esc_sql($this->get_post_data_table_id_key($post_type));
		$searchable_columns = $this->get_searchable_column_keys($post_type);

		$checks = array();
		$keywords = array_map('trim', explode(';', $keyword));
		foreach ($keywords as $single_keyword) {
			$single_check = array();
			foreach ($searchable_columns as $column) {
				$single_check[] = $column . " LIKE '%" . esc_sql($single_keyword) . "%' ";
			}
			if (!empty($single_check)) {
				$checks[] = ' (' . implode(" OR  ", $single_check) . ' ) ';
			}
		}

		$ids = $wpdb->get_col("SELECT $primary_key_column FROM " . esc_sql($post_type) . " WHERE " . implode(' OR ', $checks));
		return $ids;
	}

	function get_meta_object_id_field($field_key, $column_settings) {
		$id_key = $this->get_meta_table_post_id_key();
		return $id_key;
	}

	function get_table_name_for_field($field_key, $column_settings) {
		global $wpdb;

		$out = VGSE()->helpers->get_provider_from_query_string();

		$meta_table = $this->get_meta_table_name($out);
		$meta_key_column = $this->_get_meta_key_column_key($out);
		if ($meta_table && $wpdb->get_var($wpdb->prepare("SELECT `$meta_key_column` FROM $meta_table WHERE `$meta_key_column` = %s LIMIT 1", $field_key))) {
			$out = $meta_table;
		}
		if (method_exists(VGSE()->helpers, 'sanitize_table_key')) {
			$out = VGSE()->helpers->sanitize_table_key($out);
		}
		return $out;
	}

	function get_meta_field_unique_values($meta_key, $post_type = null) {
		global $wpdb;
		$values = apply_filters('vg_sheet_editor/provider/custom_table/meta_field_unique_values', array(), $meta_key, $post_type);
		return $values;
	}

	function get_all_meta_fields($post_type = null) {
		global $wpdb;
		$pre_value = apply_filters('vg_sheet_editor/provider/custom_table/all_meta_fields_pre_value', null, $post_type);

		if (is_array($pre_value)) {
			return $pre_value;
		}
		$post_meta_table = $this->get_meta_table_name($post_type);
		$post_meta_post_id_key = $this->get_meta_table_post_id_key($post_type);
		$meta_value_column = $this->_get_meta_value_column_key($post_type);
		$meta_key_column = $this->_get_meta_key_column_key($post_type);

		if (!empty($post_meta_table) && !empty($post_meta_post_id_key)) {
			$meta_keys_sql = "SELECT m.$meta_key_column FROM $post_meta_table m WHERE m.$meta_key_column NOT LIKE '%oembed%' AND m.$meta_value_column NOT LIKE 'field_%' GROUP BY m.$meta_key_column LIMIT 2500";
			$meta_keys = $wpdb->get_col($meta_keys_sql);
		} else {
			$meta_keys = array();
		}
		return apply_filters('vg_sheet_editor/provider/custom_table/all_meta_fields', $meta_keys, $post_type);
	}

}
