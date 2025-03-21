<?php

namespace WPDataAccess\API {

	use stdClass;
	use WPDataAccess\Connection\WPDADB;
	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Access;
	use WPDataAccess\Data_Dictionary\WPDA_List_Columns_Cache;
	use WPDataAccess\Plugin_Table_Models\WPDA_Media_Model;
	use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
	use WPDataAccess\Utilities\WPDA_WP_Media;
	use WPDataAccess\WPDA;

	class WPDA_Table {

		static private $user_roles = null;
		static private $user_login = null;

		/**
		 * Perform query and return result as JSON response.
		 *
		 * @param string $schema_name Schema name (database).
		 * @param string $table_name Table Name.
		 * @param array $column_name Column name.
		 * @return \WP_Error|\WP_REST_Response
		 */
		public static function lov(
			$schema_name,
			$table_name,
			$column_name
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				// Connected, perform queries.
				$suppress = $wpdadb->suppress_errors( true );

				$dataset = $wpdadb->get_results(
					$wpdadb->prepare("select distinct `%1s` from `%1s` order by 1",
						array(
							$column_name,
						$table_name,
						)
					),
					'ARRAY_N'
				);

				$wpdadb->suppress_errors( $suppress );

				// Send response.
				if ( '' === $wpdadb->last_error ) {
					return WPDA_API::WPDA_Rest_Response( '', $dataset );
				} else {
					return new \WP_Error( 'error', $wpdadb->last_error, array( 'status' => 420 ) );
				}
			}
		}

		/**
		 * Perform query and return result as JSON response.
		 *
		 * @param string $schema_name Schema name (database).
		 * @param string $table_name Table Name.
		 * @param array $primary Primary (key|value pairs.
		 * @param array $media_columns Media columns.
		 * @return \WP_Error|\WP_REST_Response
		 */
		public static function get(
			$schema_name,
			$table_name,
			$primary_key,
			$media_columns
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				// Connected, perform queries.
				$suppress = $wpdadb->suppress_errors( true );

				// Check primary key and sanitize key values.
				$sanitized_primary_key_values = self::sanitize_primary_key( $schema_name, $table_name, $primary_key );
				if ( false === $sanitized_primary_key_values ) {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}

				$where = '';
				foreach ( $sanitized_primary_key_values as $primary_key_column => $sanitized_primary_key_value ) {
					$where = '' === $where ? ' where ' : $where . ' and ';
					$where .= $wpdadb->prepare(
						" `%1s` like %s ",
						array(
							$primary_key_column,
							$sanitized_primary_key_value
						)
					);
				}

				$dataset = $wpdadb->get_results(
					$wpdadb->prepare("
							select *
							from `%1s`
							{$where}
						",
						array(
							$table_name,
						)
					),
					'ARRAY_A'
				);

				$wpdadb->suppress_errors( $suppress );

				// Send response.
				if ( 0 === $wpdadb->num_rows ) {
					return new \WP_Error( 'error', "No data found", array( 'status' => 420 ) );
				} elseif ( 1 === $wpdadb->num_rows ) {

					$context = array();
					if ( 0 < count( $media_columns ) ) {
						foreach ( $media_columns as $media_column_name => $media_column_type ) {
							if ( isset( $dataset[0][ $media_column_name ] ) ) {
								if (
									in_array(
										$media_column_type,
										[
											'WP-Image',
											'WP-Attachment',
											'WP-Audio',
											'WP-Video',
										]
									)

								) {
									$context[ $media_column_name ] =
										WPDA_WP_Media::get_media_url( $dataset[ 0 ][ $media_column_name ] );
								}
							}
						}
					}

					return WPDA_API::WPDA_Rest_Response(
						'',
						$dataset,
						array(
							'media' => $context
						)
					);
				} else {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}
			}
		}

		public static function insert(
			$schema_name,
			$table_name,
			$column_values
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				// Sanitize column names and values.
				$sanitized_column_values = self::sanitize_column_values(
					$schema_name,
					$table_name,
					$column_values
				);
				if ( false === $sanitized_column_values ) {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}

				// Insert row.
				$rows_inserted = $wpdadb->insert(
					$table_name,
					$sanitized_column_values
				);

				// Send response.
				if ( 1 === $rows_inserted ) {
					return WPDA_API::WPDA_Rest_Response(
						'',
						'Row successfully inserted',
						array(
							'insert_id' => $wpdadb->insert_id
						)
					);
				} else {
					if ( '' !== $wpdadb->last_error ) {
						return new \WP_Error( 'error', $wpdadb->last_error, array( 'status' => 420 ) );
					} else {
						return new \WP_Error( 'error', 'Insert failed', array( 'status' => 420 ) );
					}
				}
			}
		}

		public static function update(
			$schema_name,
			$table_name,
			$primary_key,
			$column_values
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				// Check primary key and sanitize key values.
				$sanitized_primary_key_values = self::sanitize_primary_key( $schema_name, $table_name, $primary_key );
				if ( false === $sanitized_primary_key_values ) {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}

				// Sanitize column names and values.
				$sanitized_column_values = self::sanitize_column_values( $schema_name, $table_name, $column_values );
				if ( false === $sanitized_column_values ) {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}

				// Update row.
				$rows_inserted = $wpdadb->update(
					$table_name,
					$sanitized_column_values,
					$sanitized_primary_key_values
				);

				// Send response.
				if ( 0 === $rows_inserted ) {
					return WPDA_API::WPDA_Rest_Response( '', 'Nothing to update' );
				} elseif ( 1 === $rows_inserted ) {
					return WPDA_API::WPDA_Rest_Response( '', 'Row successfully updated' );
				} else {
					if ( '' !== $wpdadb->last_error ) {
						return new \WP_Error( 'error', $wpdadb->last_error, array( 'status' => 420 ) );
					} else {
						return new \WP_Error( 'error', 'Update failed', array( 'status' => 420 ) );
					}
				}
			}
		}

		public static function delete(
			$schema_name,
			$table_name,
			$primary_key
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				// Check primary key and sanitize key values.
				$sanitized_primary_key_values = self::sanitize_primary_key( $schema_name, $table_name, $primary_key );
				if ( false === $sanitized_primary_key_values ) {
					return new \WP_Error( 'error', "Invalid arguments", array( 'status' => 420 ) );
				}

				// Delete row.
				$rows_deleted = $wpdadb->delete(
					$table_name,
					$sanitized_primary_key_values
				);

				// Send response.
				if ( 0 === $rows_deleted ) {
					return WPDA_API::WPDA_Rest_Response( '', 'No data found' );
				} elseif ( 1 === $rows_deleted ) {
					return WPDA_API::WPDA_Rest_Response( '', 'Row successfully deleted' );
				} else {
					if ( '' !== $wpdadb->last_error ) {
						return new \WP_Error( 'error', $wpdadb->last_error, array( 'status' => 420 ) );
					} else {
						return new \WP_Error( 'error', 'Delete failed', array( 'status' => 420 ) );
					}
				}
			}
		}

		/**
		 * Perform query and return result as JSON response.
		 *
		 * @param string $schema_name Schema name (database).
		 * @param string $table_name Table Name.
		 * @param string $column_names Column Names.
		 * @param string $pageIndex Page number.
		 * @param string $pageSize Rows per page.
		 * @param string $search Filter.
		 * @param string $searchColumns Column search filter.
		 * @param string $Sorting Order by.
		 * @param integer $lastRowCount Row count previous request.
		 * @param string $media_columns Media columns.
		 * @return \WP_Error|\WP_REST_Response
		 */
		public static function select(
			$schema_name,
			$table_name,
			$column_names,
			$pageIndex,
			$pageSize,
			$search,
			$searchColumns,
			$sorting,
			$lastRowCount,
			$media_columns
		) {
			$wpdadb = WPDADB::get_db_connection( $schema_name );
			if ( null === $wpdadb ) {
				// Error connecting.
				return new \WP_Error( 'error', "Error connecting to database {$schema_name}", array( 'status' => 420 ) );
			} else {
				$suppress = $wpdadb->suppress_errors( true );
				$where    = '';

				// Global search.
				$where_global = array();
				if ( null !== $search && "" !== $search ) {
					foreach ( $column_names as $column_name => $queryable ) {
						if ( $queryable ) {
							$where_global[] =
								$wpdadb->prepare(
									" `{$column_name}` like '%s' ",
									'%' . esc_sql( $search ) . '%'
								);
						}
					}
				}
				if ( 0 < count( $where_global ) ) {
					$where = 'where ' . WPDA_Table::add_condition( $where_global, 'or' );
				}

				// Inline search.
				if ( 0 < count( $searchColumns ) ) {
					$where_columns = array();

					foreach ( $searchColumns as $searchColumn ) {
						if ( isset( $searchColumn['id'], $searchColumn['value'] ) ) {
							$where_columns[] =
								$wpdadb->prepare(
									" {$searchColumn['id']} like %s ",
									"%{$searchColumn['value']}%"
								);
						}
					}

					if ( 0 < count( $where_columns ) ) {
						$where .=
							( '' === $where  ? ' where ' : ' and ' ) .
							' (' . implode( ' and ', $where_columns ) . ') ';
					}
				}

				// Order by.
				$sqlorder = '';
				if ( null !== $sorting && 0 < count( $sorting ) ) {
					if ( isset( $sorting['id'], $sorting['desc'] ) ) {
						if ( '' === $sqlorder ) {
							$sqlorder = 'order by ';
						} else {
							$sqlorder .= ',';
						}
						$sqlorder .= sanitize_sql_orderby( $sorting['id'] ) . ' ' . $sorting['desc'];
					}
				}

				// Pagination.
				if ( ! is_numeric( $pageSize ) ) {
					$pageSize = 10;
				}
				$offset = ( $pageIndex ) * $pageSize; // Calculate offset.
				if ( ! is_numeric( $offset ) ) {
					$offset = 0;
				}

				// Prepare query.
				$sql = "
					select `" . implode("`,`", array_keys( $column_names ) ). "`
					from `%1s`
					{$where}
					{$sqlorder}
				" .
				( 0 < $pageSize ? " limit {$pageSize} offset {$offset} " : '' );

				// Prepare debug info.
				if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ) {
					$debug = array(
						'debug' => array(
							'sql'      => preg_replace( "/\s+/", " ", $sql ),
							'where'    => $where,
							'order by' => $sqlorder,
						)
					);
				} else {
					$debug = null;
				}

				// Query.
				$dataset = $wpdadb->get_results(
					$wpdadb->prepare(
						$sql,
						array(
							$table_name,
						)
					),
					'ARRAY_A'
				);

				if ( $wpdadb->last_error ) {
					// Handle SQL errors.
					return new \WP_Error(
						'error',
						$wpdadb->last_error,
						array(
							'status' => 420,
							'debug'  => $debug
						)
					);
				}

				if ( is_numeric( $lastRowCount ) and 0 <= $lastRowCount ) {
					// Prevents an extra query.
					$rowcount = $lastRowCount;
				} else {
					// (Re)Count rows.
					$countrows = $wpdadb->get_results(
						$wpdadb->prepare("
								select count(1) as rowcount
								from `%1s`
								{$where}
							",
							array(
								$table_name,
							)
						),
						'ARRAY_A'
					);

					if ( $wpdadb->last_error ) {
						// Handle SQL errors.
						return new \WP_Error( 'error', $wpdadb->last_error, array( 'status' => 420 ) );
					}

					$rowcount  = isset( $countrows[0]['rowcount'] ) ? $countrows[0]['rowcount'] : 0;
				}

				// Add context node to response
				$context = array();
				$context['debug'] = $debug['debug'];

				if ( 0 < count( $media_columns ) ) {
					// Handle WP media library
					$media = array();

					for ( $i = 0; $i < count( $dataset ); $i++ ) {
						$media_row = array();
						foreach ( $media_columns as $media_column_name => $media_column_type ) {
							if ( isset( $dataset[$i][ $media_column_name ] ) ) {
								$media_row[ $media_column_name ] =
									WPDA_WP_Media::get_media_url( $dataset[ $i ][ $media_column_name ] );
							}
						}
						$media[] = $media_row;
					}

					// Add media to context node
					$context['media'] = $media;
				}


				$wpdadb->suppress_errors( $suppress );

				// Send response.
				$response = WPDA_API::WPDA_Rest_Response(
					'',
					$dataset,
					$context,
					array(
						'rowCount' => $rowcount,
					)
				);

				$response->header( 'X-WP-Total', $rowcount ); // Total rows for this query.
				if ( 0 < $pageSize ) {
					$pagecount = floor( $rowcount / $pageSize );
					if ( $pagecount != $rowcount / $pageSize ) { // phpcs:ignore WordPress.PHP.StrictComparisons
						$pagecount++;
					}
				} else {
					// Prevent division by zero
					$pagecount = 0;
				}
				$response->header( 'X-WP-TotalPages', $pagecount ); // Total pages for this query.

				return $response;
			}
		}

		private static function add_condition(
			$where_lines,
			$operand = 'and'
		) {
			if ( 0 < count( array_filter( $where_lines ) ) ) {
				// Apply all searches.
				return ' ( (' . implode( ") {$operand} (", array_filter( $where_lines ) ) . ') ) ';
			} else {
				return "";
			}
		}

		/**
		 * Get table meta data.
		 *
		 * @param string $schema_name Database schema name.
		 * @param string $table_name Database table name.
		 * @return array\object
		 */
		public static function get_table_meta_data( $schema_name, $table_name ) {
			$access   = WPDA_Table::get_table_access( $schema_name, $table_name );
			$settings = new stdClass();
			if ( null !== $access ) {
				$columns     = WPDA_List_Columns_Cache::get_list_columns( $schema_name, $table_name );
				$settings_db = WPDA_Table_Settings_Model::query( $table_name, $schema_name );
				if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
					$settings = json_decode( (string) $settings_db[0]['wpda_table_settings'] );

					// Remove old settings from response.
					unset( $settings->form_labels );
					unset( $settings->list_labels );
					unset( $settings->custom_settings );
				}
				$settings->ui = WPDA_Settings::get_admin_settings( $schema_name, $table_name );
				$rest_api = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
				if ( isset( $rest_api[ $schema_name ][ $table_name ] ) ) {
					$settings->rest_api = $rest_api[ $schema_name ][ $table_name ];
				}

				$media = array();
				foreach ( $columns->get_table_columns() as $column ) {
					$media_type = WPDA_Media_Model::get_column_media( $table_name, $column['column_name'], $schema_name );
					switch( $media_type ) {
						case "ImageURL":
							$media[ $column['column_name'] ] = $media_type;
							break;
						case "Hyperlink":
							if ( isset( $settings->table_settings->hyperlink_definition ) &&
								'text' === $settings->table_settings->hyperlink_definition ) {
								$media[ $column['column_name'] ] = "HyperlinkURL";
							} else {
								$media[ $column['column_name'] ] = "HyperlinkObject";
							}
							break;
						default:
							if ( false !== $media_type ) {
								// Handle WordPress Media Library integration
								$media[ $column['column_name'] ] = "WP-{$media_type}";
							}
					}
				}
			}

			return array(
				'columns'      => $columns->get_table_columns(),
				'table_labels' => $columns->get_table_header_labels(),
				'form_labels'  => $columns->get_table_column_headers(),
				'primary_key'  => $columns->get_table_primary_key(),
				'access'       => $access,
				'settings'     => $settings,
				'media'        => $media,
				'table_type'   => WPDA_Table::get_table_type( $schema_name, $table_name )
			);
		}

		private static function get_table_type( $dbs, $tbl ) {

			$wpdadb = WPDADB::get_db_connection( $dbs );
			if ( $wpdadb === null ) {
				return WPDA_API::WPDA_Rest_Response(
					'',
					array()
				);
			}

			$query = $wpdadb->prepare("
					select table_type
					  from information_schema.tables
					 where table_schema = %s
					   and table_name   = %s
					 order by table_name
				",
				array(
					$dbs,
					$tbl,
				)
			);

			$resultset = $wpdadb->get_results( $query, 'ARRAY_N' ); // phpcs:ignore Standard.Category.SniffName.ErrorCode
			if ( count( $resultset ) === 1 ) {
				return $resultset[0][0];
			} else {
				return null;
			}

		}

		private static function get_table_access( $dbs, $tbl ) {
			if ( current_user_can( 'manage_options') ) {
				// Check administrator rights
				if ( is_admin() ) {
					$access = WPDA_Dictionary_Access::check_table_access_backend( $dbs, $tbl, $done );
				} else {
					$access = WPDA_Dictionary_Access::check_table_access_frontend( $dbs, $tbl, $done );
				}

				if ( $access ) {
					// Administrator access granted
					return array(
						'select' => array('POST'),
						'insert' => array('POST'),
						'update' => array('POST'),
						'delete' => array('POST'),
					);
				}
			}

			$tables = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );

			if (
				false !== $tables &&
				isset( $tables[ $dbs ][ $tbl ] ) &&
				is_array( $tables[ $dbs ][ $tbl ] )
			) {
				$table = $tables[ $dbs ][ $tbl ];

				$table_access = new \stdClass();

				$table_access->select = WPDA_Table::get_table_access_action( $table, 'select');
				$table_access->insert = WPDA_Table::get_table_access_action( $table, 'insert');
				$table_access->update = WPDA_Table::get_table_access_action( $table, 'update');
				$table_access->delete = WPDA_Table::get_table_access_action( $table, 'delete');

				return $table_access;
			}

			return false;
		}

		private static function get_table_access_action( $table, $action ) {
			if (
				isset( $table[ $action ]['authorization'], $table[ $action ]['methods'] ) &&
				is_array( $table[ $action ]['methods'] ) &&
				0 < count( $table[ $action ]['methods'] )
			) {
				if (
					'anonymous' === $table[ $action ]['authorization']
				) {
					return $table[ $action ]['methods'];
				} else {
					// Check authorized users
					if (
						isset( $table[ $action ]['authorized_users'] ) &&
						is_array( $table[ $action ]['authorized_users'] ) &&
						0 < count( $table[ $action ]['authorized_users'] ) &&
						in_array(
							(string) WPDA_Table::get_user_login(),
							$table[ $action ]['authorized_users']
						)
					) {
						return $table[ $action ]['methods'];
					}

					// Check authorized roles
					if (
						isset( $table[ $action ]['authorized_roles'] ) &&
						is_array( $table[ $action ]['authorized_roles'] ) &&
						0 < count( $table[ $action ]['authorized_roles'] ) &&
						0 < count(
									array_intersect(
										WPDA_Table::get_user_roles(),
										$table[ $action ]['authorized_roles']
									)
								)
					) {
						return $table[ $action ]['methods'];
					}
				}
			}

			return array();
		}

		private static function get_user_roles() {
			if ( null === WPDA_Table::$user_roles ) {
				WPDA_Table::$user_roles = WPDA::get_current_user_roles();
				if ( false === WPDA_Table::$user_roles ) {
					WPDA_Table::$user_roles = array();
				}
			}

			return WPDA_Table::$user_roles;
		}

		private static function get_user_login() {
			if ( null === WPDA_Table::$user_login ) {
				WPDA_Table::$user_login = WPDA::get_current_user_login();
			}

			return WPDA_Table::$user_login;
		}

		/**
		 * Check if access is grant for requested database/table.
		 *
		 * @param string $dbs Remote or local database connection string.
		 * @param string $tbl Database table name.
		 * @param string $action Possible values: select, insert, update, delete.
		 * @return bool
		 */
		public static function check_table_access( $dbs, $tbl, $request, $action ) {
			if ( current_user_can( 'manage_options' ) ) {
				// Grant access to administrators.
				return true;
			}

			$tables = get_option( WPDA_API::WPDA_REST_API_TABLE_ACCESS );
			if ( false === $tables ) {
				// No tables.
				return false;
			}

			if (
				! (
					isset( $tables[ $dbs ][ $tbl ][ $action ]['methods'] ) &&
					is_array( $tables[ $dbs ][ $tbl ][ $action ]['methods'] )
				)
			) {
				// No methods.
				return false;
			} else {
				if ( ! in_array( $request->get_method(), $tables[ $dbs ][ $tbl ][ $action ]['methods'] ) ) { //phpcs:ignore - 8.1 proof
					return false;
				}
			}

			if ( ! isset( $tables[ $dbs ][ $tbl ][ $action ]['authorization'] ) ) {
				// No authorization.
				return false;
			} else {
				if ( 'anonymous' === $tables[ $dbs ][ $tbl ][ $action ]['authorization'] ) {
					// Access granted to all users.
					return true;
				}
			}

			global $wp_rest_auth_cookie;
			if ( true !== $wp_rest_auth_cookie ) {
				// No anonymous access.
				return false;
			} else {
				if ( 'authorized' !== $tables[ $dbs ][ $tbl ][ $action ]['authorization'] ) {
					// Authorization check.
					return false;
				}

				// Authorized access requires a valid nonce.
				if ( ! wp_verify_nonce( $request->get_header('X-WP-Nonce'), 'wp_rest' ) ) {
					return false;
				}

				if (
					! (
						isset( $tables[ $dbs ][ $tbl ][ $action ]['authorized_users'] ) &&
						is_array( $tables[ $dbs ][ $tbl ][ $action ]['authorized_users'] )
					)
				) {
					// No users.
					return false;
				} else {
					$requesting_user_login = WPDA_Table::get_user_login();

					if (
						0 < count( $tables[ $dbs ][ $tbl ][ $action ]['authorized_users'] ) && //phpcs:ignore - 8.1 proof
						in_array( $requesting_user_login, $tables[ $dbs ][ $tbl ][ $action ]['authorized_users'] ) //phpcs:ignore - 8.1 proof
					) {
						return true;
					}
				}

				if (
					! (
						isset( $tables[ $dbs ][ $tbl ][ $action ]['authorized_roles'] ) &&
						is_array( $tables[ $dbs ][ $tbl ][ $action ]['authorized_roles'] )
					)
				) {
					// No roles.
					return false;
				} else {
					$requesting_user_roles = WPDA_Table::get_user_roles();
					if ( false === $requesting_user_roles ) {
						$requesting_user_roles = array();
					}

					if (
						0 < count( $tables[ $dbs ][ $tbl ][ $action ]['authorized_roles'] ) && //phpcs:ignore - 8.1 proof
						0 < count( array_intersect( $requesting_user_roles, $tables[ $dbs ][ $tbl ][ $action ]['authorized_roles'] ) ) //phpcs:ignore - 8.1 proof
					) {
						return true;
					}
				}

				return false;
			}
		}

		private static function sanitize_primary_key( $schema_name, $table_name, $primary_key ) {
			$wpda_list_columns   = WPDA_List_Columns_Cache::get_list_columns( $schema_name, $table_name );
			$primary_key_columns = $wpda_list_columns->get_table_primary_key();

			$sanitized_primary_key_values = [];
			foreach ( $primary_key_columns as $primary_key_column ) {
				if ( ! isset( $primary_key[ $primary_key_column ] ) ) {
					// Invalid column name.
					return false;
				}

				$sanitized_primary_key_values[ WPDA::remove_backticks( $primary_key_column ) ] =
					sanitize_text_field( wp_unslash( $primary_key[ $primary_key_column ] ) );
			}

			return $sanitized_primary_key_values;
		}

		private static function sanitize_column_values( $schema_name, $table_name, $column_values ) {
			$wpda_list_columns = WPDA_List_Columns_Cache::get_list_columns( $schema_name, $table_name );

			$sanitized_column_values = [];
			foreach ( $column_values as $column_name => $column_value ) {
				$column_value = $column_values[ $column_name ];
				switch ( $wpda_list_columns->get_column_data_type( $column_name ) ) {
					case 'tinytext':
					case 'text':
					case 'mediumtext':
					case 'longtext':
						if ( null !== $column_value ) {
							$column_value = sanitize_textarea_field( wp_unslash( $column_value ) );
						}
						break;
					default:
						if ( null !== $column_value ) {
							$column_value = sanitize_text_field( wp_unslash( $column_value ) );
						}
				}
				$sanitized_column_values[ WPDA::remove_backticks( $column_name ) ] = $column_value;
			}

			return $sanitized_column_values;
		}

	}

}
