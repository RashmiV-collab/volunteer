<?php defined( 'ABSPATH' ) || exit;

// Fix. If they update one plugin and use an old version of another,
// the Abstract class might not exist and they will get fatal errors.
// So we make sure it loads the class from the current plugin if it's missing
// This can be removed in a future update.
if ( ! class_exists( 'VGSE_Provider_Abstract' ) ) {
	require_once VGSE_USERS_DIR . '/modules/wp-sheet-editor/inc/providers/abstract.php';
}
if ( ! class_exists( 'VGSE_Provider_User' ) ) {

	class VGSE_Provider_User extends VGSE_Provider_Abstract {

		private static $instance = false;
		var $key                 = 'user';
		var $is_post_type        = false;
		static $data_store       = array();

		private function __construct() {

		}

		function get_provider_read_capability( $post_type_key ) {
			return 'list_users';
		}

		function delete_meta_key( $old_key, $post_type ) {
			global $wpdb;
			$meta_table_name = $this->get_meta_table_name( $post_type );
			$modified        = $wpdb->delete(
				$meta_table_name,
				array(
					'meta_key' => $old_key,
				)
			);
			return $modified;
		}

		function rename_meta_key( $old_key, $new_key, $post_type ) {
			global $wpdb;
			$meta_table_name = $this->get_meta_table_name( $post_type );
			$modified        = (int) $wpdb->update(
				$meta_table_name,
				array(
					'meta_key' => $new_key,
				),
				array(
					'meta_key' => $old_key,
				)
			);
			return $modified;
		}

		function get_provider_edit_capability( $post_type_key ) {
			return 'edit_users';
		}

		function get_provider_delete_capability( $post_type_key ) {
			return 'delete_users';
		}

		function init() {

		}

		function get_total( $post_type = null ) {
			$result = count_users();
			return $result['total_users'];
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new VGSE_Provider_User();
				self::$instance->init();
			}
			return self::$instance;
		}

		function get_post_data_table_id_key( $post_type = null ) {
			if ( ! $post_type ) {
				$post_type = VGSE()->helpers->get_provider_from_query_string();
			}

			$post_id_key = apply_filters( 'vgse_sheet_editor/provider/user/post_data_table_id_key', 'ID', $post_type );
			if ( ! $post_id_key ) {
				$post_id_key = 'ID';
			}
			if ( method_exists( VGSE()->helpers, 'sanitize_table_key' ) ) {
				$post_id_key = VGSE()->helpers->sanitize_table_key( $post_id_key );
			}
			return $post_id_key;
		}

		function get_meta_table_post_id_key( $post_type = null ) {
			if ( ! $post_type ) {
				$post_type = VGSE()->helpers->get_provider_from_query_string();
			}

			$post_id_key = apply_filters( 'vgse_sheet_editor/provider/user/meta_table_post_id_key', 'user_id', $post_type );
			if ( ! $post_id_key ) {
				$post_id_key = 'user_id';
			}
			if ( method_exists( VGSE()->helpers, 'sanitize_table_key' ) ) {
				$post_id_key = VGSE()->helpers->sanitize_table_key( $post_id_key );
			}
			return $post_id_key;
		}

		function get_meta_table_name( $post_type = null ) {
			global $wpdb;
			if ( ! $post_type ) {
				$post_type = VGSE()->helpers->get_provider_from_query_string();
			}

			$table_name = apply_filters( 'vgse_sheet_editor/provider/user/meta_table_name', $wpdb->usermeta, $post_type );
			if ( ! $table_name ) {
				$table_name = $wpdb->usermeta;
			}
			if ( method_exists( VGSE()->helpers, 'sanitize_table_key' ) ) {
				$table_name = VGSE()->helpers->sanitize_table_key( $table_name );
			}
			return $table_name;
		}

		function prefetch_data( $post_ids, $post_type, $spreadsheet_columns ) {

		}

		function get_item_terms( $id, $taxonomy ) {
			$value = get_user_meta( $id, $taxonomy, true );

			if ( empty( $value ) ) {
				return false;
			}

			$terms = get_terms(
				array(
					'taxonomy'               => $taxonomy,
					'hide_empty'             => false,
					'include'                => $value,
					'update_term_meta_cache' => false,
				)
			);

			if ( $terms && ! is_wp_error( $terms ) ) {
				$terms = VGSE()->data_helpers->prepare_post_terms_for_display( $terms );
			}

			return $terms;
		}

		function get_statuses() {
			return array();
		}

		function get_items( $query_args ) {
			$post_keys_to_remove = array(
				'post_type',
				'post_status',
				'author',
				'tax_query',
			);
			foreach ( $post_keys_to_remove as $post_key_to_remove ) {
				if ( isset( $query_args[ $post_key_to_remove ] ) ) {
					unset( $query_args[ $post_key_to_remove ] );
				}
			}

			if ( isset( $query_args['posts_per_page'] ) ) {
				$query_args['number'] = $query_args['posts_per_page'];
			}

			if ( isset( $query_args['post__in'] ) ) {
				$query_args['include'] = $query_args['post__in'];
			}
			if ( isset( $query_args['post__not_in'] ) ) {
				$query_args['exclude'] = $query_args['post__not_in'];
			}
			if ( ! empty( $query_args['fields'] ) && $query_args['fields'] === 'ids' ) {
				$query_args['fields'] = 'ID';
			}
			if ( ! empty( $query_args['s'] ) ) {
				$query_args['search'] = '*' . $query_args['s'] . '*';
			}

			// We use WP_User_Query instead of get_users() because we need the full object of the User_Query
			$user_search = new WP_User_Query( $query_args );
			$users       = (array) $user_search->get_results();

			$total_users = $user_search->get_total();

			$out              = (object) array();
			$out->found_posts = $total_users;
			$out->posts       = array();
			$out->request     = $user_search->request;
			if ( ! empty( $users ) ) {
				foreach ( $users as $user ) {
					if ( is_object( $user ) ) {
						$user->data->post_type = 'user';
						$user->data->roles     = $user->roles;
						$user->data->role      = current( $user->roles );
						$out->posts[]          = $user->data;
					} else {
						$out->posts[] = $user;
					}
				}
			}
			return $out;
		}

		function get_item( $id, $format = null ) {
			$user = get_user_by( 'ID', $id );

			if ( ! empty( $user ) ) {
				$user->data->post_type  = 'user';
				$user->data->first_name = $user->first_name;
				$user->data->last_name  = $user->last_name;
				$user->data->roles      = $user->roles;
				$user->data->role       = current( $user->roles );
			}
			if ( $format == ARRAY_A ) {
				$user = (array) $user->data;
			}
			return apply_filters( 'vg_sheet_editor/provider/user/get_item', $user, $id, $format );
		}

		function get_item_meta( $id, $key, $single = true, $context = 'save', $bypass_cache = false ) {
			return apply_filters( 'vg_sheet_editor/provider/user/get_item_meta', get_user_meta( $id, $key, $single ), $id, $key, $single, $context );
		}

		function get_item_data( $id, $key ) {
			$item = $this->get_item( $id );

			if ( isset( $item->$key ) ) {
				return apply_filters( 'vg_sheet_editor/provider/term/get_item_data', $item->$key, $id, $key, true, 'read' );
			}

			return $this->get_item_meta( $id, $key, true, 'read' );
		}

		function update_item_data( $values, $wp_error = false ) {
			if ( ! WP_Sheet_Editor_Helpers::current_user_can( 'edit_users' ) ) {
				return false;
			}

			if ( isset( $values['role'] ) && ! WP_Sheet_Editor_Helpers::current_user_can( 'promote_users' ) ) {
				unset( $values['role'] ); 
			}
			if ( count( $values ) === 1 && isset( $values['ID'] ) ) {
				return true;
			}
			$user_id = (int) $values['ID'];
			unset( $values['ID'] ); 
			$item = $this->get_item( $user_id, ARRAY_A );

			$spreadsheet_columns = VGSE()->helpers->get_provider_columns( 'user' );
			$data                = array();

			foreach ( $values as $key => $value ) {
				// WPSE doesn't save objects
				if ( is_object( $value ) ) {
					continue;
				}
				$allow_safe_html = isset( $spreadsheet_columns[ $key ] ) && ! empty( $spreadsheet_columns[ $key ]['formatted'] ) && ! empty( $spreadsheet_columns[ $key ]['formatted']['renderer'] ) && $spreadsheet_columns[ $key ]['formatted']['renderer'] === 'html';

				if ( ! $allow_safe_html ) {
					$value = is_array( $value ) ? array_map( 'wp_strip_all_tags', $value ) : wp_strip_all_tags( $value );
				}
				if ( isset( $item[ $key ] ) ) {
					$data[ $key ] = $value;
				} else {
					update_user_meta( $user_id, $key, apply_filters( 'vg_sheet_editor/provider/user/update_item_meta', $value, $user_id, $key ) );
				}
			}

			if ( ! empty( $data ) ) {
				$data['ID'] = $user_id;
				$result     = wp_update_user( $data );

				if ( isset( $data['user_login'] ) ) {
					global $wpdb;
					$wpdb->update( $wpdb->users, array( 'user_login' => sanitize_user( $data['user_login'], true ) ), array( 'ID' => $user_id ) );
				}
			}

			if ( ! empty( $values['wpse_status'] ) && $values['wpse_status'] === 'delete' && WP_Sheet_Editor_Helpers::current_user_can( 'delete_users' ) ) {
				if ( ! empty( VGSE()->options['wpmu_delete_account'] ) && is_multisite() ) {
					wpmu_delete_user( $user_id );
				} else {
					wp_delete_user( $user_id );
				}
				VGSE()->deleted_rows_ids[] = (int) $user_id;
			}
			do_action( 'vg_sheet_editor/provider/user/data_updated', $user_id, wp_parse_args( $values, $item ) );
			return $user_id;
		}

		function delete_item_meta($id, $key) {
			delete_user_meta($id, $key);
		}
		function update_item_meta( $id, $key, $value ) {
			return $this->update_item_data(
				array(
					'ID' => $id,
					$key => $value,
				)
			);
		}

		function set_object_terms( $post_id, $terms_saved, $key ) {
			return $this->update_item_meta( $post_id, $key, $terms_saved );
		}

		function get_object_taxonomies( $post_type = null ) {
			return get_taxonomies( array(), 'objects' );
		}

		function create_item( $values ) {
			if ( ! WP_Sheet_Editor_Helpers::current_user_can( 'create_users' ) ) {
				return false;
			}
			$random = $this->get_random_string( 15 );
			if ( empty( $values['user_email'] ) ) {
				$values['user_email'] = 'temporary-remove' . $random . '@tmp.com';
			}
			if ( empty( $values['user_login'] ) ) {
				$values['user_login'] = 'temporary-remove' . $random;
			}
			if ( ! isset( $values['user_pass'] ) ) {
				$values['user_pass'] = '';
			}

			if ( ! WP_Sheet_Editor_Helpers::current_user_can( 'promote_users' ) && isset( $values['role'] ) ) {
				unset( $values['role'] );
			}

			return wp_insert_user( $values );
		}

		function get_item_ids_by_keyword( $keyword, $post_type, $operator = 'LIKE' ) {
			global $wpdb;
			$operator = ( $operator === 'LIKE' ) ? 'LIKE' : 'NOT LIKE';

			$checks        = array();
			$keywords      = array_map( 'trim', explode( ';', $keyword ) );
			$prepared_data = array();
			foreach ( $keywords as $single_keyword ) {
				$checks[] = " user_email $operator %s OR user_login $operator %s OR user_nicename $operator %s OR display_name $operator %s ";

				$prepared_data = array_merge( $prepared_data, array( '%' . $wpdb->esc_like( $single_keyword ) . '%', '%' . $wpdb->esc_like( $single_keyword ) . '%', '%' . $wpdb->esc_like( $single_keyword ) . '%', '%' . $wpdb->esc_like( $single_keyword ) . '%' ) );

				// Allow to search by ID
				if ( is_numeric( $single_keyword ) ) {
					$checks[ count( $checks ) - 1 ] .= " OR $wpdb->users.ID = %d";
					$prepared_data[]                 = (int) $single_keyword;
				}
			}

			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE " . implode( ' OR ', $checks ), $prepared_data ) );
			return $ids;
		}

		function get_meta_object_id_field( $field_key, $column_settings ) {
			$id_key = $this->get_meta_table_post_id_key( $this->key );
			return $id_key;
		}

		function get_table_name_for_field( $field_key, $column_settings ) {
			global $wpdb;

			$user_data  = wp_list_pluck( $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->users;" ), 'Field' );
			$table_name = ( in_array( $field_key, $user_data ) ) ? $wpdb->users : $this->get_meta_table_name();
			if ( method_exists( VGSE()->helpers, 'sanitize_table_key' ) ) {
				$table_name = VGSE()->helpers->sanitize_table_key( $table_name );
			}
			return $table_name;
		}

		function get_meta_field_unique_values( $meta_key, $post_type = null ) {
			global $wpdb;
			$meta_table = $this->get_meta_table_name( $this->key );
			$id_key     = $this->get_meta_table_post_id_key( $this->key );
			$sql        = $wpdb->prepare( "SELECT m.meta_value FROM $wpdb->users p LEFT JOIN $meta_table m ON p.ID = m.$id_key WHERE m.meta_key = %s GROUP BY m.meta_value ORDER BY LENGTH(m.meta_value) DESC LIMIT 4", $meta_key );
			$values     = apply_filters( 'vg_sheet_editor/provider/user/meta_field_unique_values', $wpdb->get_col( $sql ), $meta_key, $post_type );
			return $values;
		}

		function get_all_meta_fields( $post_type = null ) {
			global $wpdb;
			$pre_value = apply_filters( 'vg_sheet_editor/provider/user/all_meta_fields_pre_value', null, $this->key );

			if ( is_array( $pre_value ) ) {
				return $pre_value;
			}
			$max_fields_limit      = VGSE()->get_option( 'meta_fields_scan_limit', 4000 );
			$meta_table    = $this->get_meta_table_name( $this->key );
			$id_key        = $this->get_meta_table_post_id_key( $this->key );
			$meta_keys_sql = $wpdb->prepare( "SELECT m.meta_key FROM $wpdb->users p LEFT JOIN $meta_table m ON p.ID = m.$id_key WHERE m.meta_value NOT LIKE 'field_%' AND m.meta_key NOT LIKE '_oembed%' AND m.meta_key NOT LIKE 'bb_profile_long_slug%' AND m.meta_key NOT LIKE 'bb_profile_slug%' GROUP BY m.meta_key LIMIT %d", $max_fields_limit );
			$meta_keys     = $wpdb->get_col( $meta_keys_sql );
			return apply_filters( 'vg_sheet_editor/provider/user/all_meta_fields', $meta_keys, $this->key );
		}

	}

}
