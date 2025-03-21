<?php
if ( ! class_exists( 'WP_Sheet_Editor_Users_WooCommerce' ) ) {

	class WP_Sheet_Editor_Users_WooCommerce {

		private static $instance = false;
		var $orders_count_key    = '_order_count';
		var $last_order_date     = 'wc_last_order_date';

		private function __construct() {

		}

		function init() {
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns' ) );
			add_action( 'vg_sheet_editor/provider/user/data_updated', array( $this, 'sync_customer_lookup_table' ), 10, 2 );
			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbars' ) );

			// We flush the table very late to make sure WC has loaded fully
			add_action( 'vg_sheet_editor/editor_page/after_content', array( $this, 'maybe_purge_customer_lookup_table' ) );
			add_action( 'admin_footer', array( $this, 'add_bulk_edit_button_to_customers_page' ) );
			add_filter( 'vg_sheet_editor/load_rows/full_output', array( $this, 'calculate_sales_totals' ), 10, 2 );
			add_filter( 'vg_sheet_editor/advanced_filters/all_fields_groups', array( $this, 'add_orders_count_filter' ), 10, 2 );
			add_action( 'pre_user_query', array( $this, 'filter_users_query_by_orders_count' ) );
			add_action( 'pre_user_query', array( $this, 'filter_users_query_by_last_order_date' ) );
			add_action( 'pre_user_query', array( $this, 'filter_users_query_by_products_bought' ) );
			add_action( 'vg_sheet_editor/filters/after_fields', array( $this, 'add_wc_filters__premium_only' ) );

		}

		function add_wc_filters__premium_only( $post_type ) {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}
			if ( $post_type !== 'user' ) {
				return;
			}
			$nonce = wp_create_nonce( 'bep-nonce' );
			?>
			<li>
				<label><?php _e( 'Find customers who bought these products', vgse_users()->textname ); ?></label>
				<select name="products_bought[]" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo apply_filters( 'vg_sheet_editor/woocommerce/product_post_type_key', 'product' ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"  data-placeholder="<?php _e( 'Select product(s)...', vgse_users()->textname ); ?> " class="select2 individual-product-selector" multiple>
					<option></option>
				</select>
				<br>
				<select name="products_bought_operator">
					<option value="AND"><?php _e( 'Bought all the products selected', vgse_users()->textname ); ?></option>
					<option value="OR"><?php _e( 'Bought at least one product selected', vgse_users()->textname ); ?></option>
					<option value="ONLY"><?php _e( 'Bought only the products selected', vgse_users()->textname ); ?></option>
				</select>
			</li>
			<?php
		}

		function filter_users_query_by_products_bought( $wp_users_query ) {
			global $wpdb;
			if ( empty( $wp_users_query->query_vars['wpse_original_filters'] ) || empty( $wp_users_query->query_vars['wpse_original_filters']['products_bought'] ) ) {
				return;
			}
			$operator        = $wp_users_query->query_vars['wpse_original_filters']['products_bought_operator'];
			$raw_products    = implode( ',', $wp_users_query->query_vars['wpse_original_filters']['products_bought'] );
			$raw_products    = str_replace( 'product--', '', $raw_products );
			$raw_products    = explode( ',', $raw_products );
			$products_bought = array_map( 'intval', $raw_products );
			sort( $products_bought, SORT_NUMERIC );
			$sql = null;
			if ( $operator === 'ONLY' ) {
				$sql = "SELECT user_id FROM {$wpdb->prefix}wc_order_product_lookup op 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup c 
ON c.customer_id = op.customer_id 
WHERE user_id IS NOT NULL 
GROUP BY user_id 
HAVING GROUP_CONCAT(DISTINCT op.product_id ORDER BY op.product_id ASC) = '" . implode( ',', $products_bought ) . "'";
			} elseif ( $operator === 'OR' ) {
				$sql = "SELECT user_id FROM {$wpdb->prefix}wc_order_product_lookup op 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup c 
ON c.customer_id = op.customer_id 
WHERE user_id IS NOT NULL 
AND op.product_id IN (" . implode( ',', $products_bought ) . ')
GROUP BY user_id';
			} elseif ( $operator === 'AND' ) {
				$sql = "SELECT user_id FROM {$wpdb->prefix}wc_order_product_lookup op 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup c 
ON c.customer_id = op.customer_id 
WHERE user_id IS NOT NULL 
AND op.product_id IN (" . implode( ',', $products_bought ) . ")
GROUP BY user_id
HAVING GROUP_CONCAT(DISTINCT op.product_id ORDER BY op.product_id ASC) = '" . implode( ',', $products_bought ) . "'";
			}
			if ( ! $sql ) {
				return;
			}

			$wp_users_query->query_where .= " AND $wpdb->users.ID IN ($sql) ";
		}

		function filter_users_query_by_orders_count( $wp_users_query ) {
			global $wpdb;
			if ( empty( $wp_users_query->query_vars['wpse_original_filters'] ) || empty( $wp_users_query->query_vars['wpse_original_filters']['meta_query'] ) ) {
				return;
			}
			$user_data_filters = wp_list_filter(
				$wp_users_query->query_vars['wpse_original_filters']['meta_query'],
				array(
					'key' => $this->orders_count_key,
				)
			);
			if ( empty( $user_data_filters ) ) {
				return;
			}
			$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

			foreach ( $user_data_filters as $index => $meta_query ) {
				if ( ! in_array( $meta_query['compare'], array( '=', '!=', '>', '>=', '<', '<=' ), true ) ) {
					$meta_query['compare'] = '=';
				}

				if ( $meta_query['compare'] === '=' && (int) $meta_query['value'] === 0 || $meta_query['compare'] === '<' && (int) $meta_query['value'] === 1 ) {
					if ( $hpos_enabled ) {
						$wp_users_query->query_where .= " AND $wpdb->users.ID NOT IN (SELECT c.user_id FROM " . $wpdb->prefix . 'wc_order_stats os 
						LEFT JOIN ' . $wpdb->prefix . 'wc_customer_lookup c 
						ON c.customer_id = os.customer_id 
						WHERE user_id > 0
						GROUP BY user_id) ';
					} else {
						$wp_users_query->query_where .= " AND $wpdb->users.ID NOT IN (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_customer_user' AND meta_value > 0)  ";
					}
				} else {
					if ( $hpos_enabled ) {
						$sql = "SELECT c.user_id, COUNT(*) 'count' FROM " . $wpdb->prefix . 'wc_order_stats os 
						LEFT JOIN ' . $wpdb->prefix . 'wc_customer_lookup c 
						ON c.customer_id = os.customer_id 
						WHERE user_id > 0 
						GROUP BY user_id
						HAVING count ' . $meta_query['compare'] . ' ' . (int) $meta_query['value'] . "
						
						UNION ALL
						
						SELECT u.ID, '0' as count 
						FROM wp_users as u 
							WHERE u.ID  NOT IN (
							SELECT c2.user_id FROM " . $wpdb->prefix . 'wc_customer_lookup c2
							LEFT JOIN ' . $wpdb->prefix . 'wc_order_stats os2
							ON c2.customer_id = os2.customer_id 
							WHERE os2.order_id > 0 AND c2.user_id > 0
							GROUP BY c2.user_id
							) 
						HAVING count ' . $meta_query['compare'] . ' ' . (int) $meta_query['value'];
					} else {

						// We use 2 queries with UNION ALL for performance reasons.
						// the first query will get count the orders of the users who made orders
						// and the second will just append users with a static count 0, this way
						// we don't have to count orders for users without orders
						$sql = "SELECT ID FROM (
SELECT u.ID, 
(
SELECT COUNT(meta_value) 
FROM $wpdb->postmeta  
WHERE meta_key = '_customer_user' AND meta_value = u.ID 
) as count
 
FROM $wpdb->users as u WHERE u.ID IN (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_customer_user' AND meta_value > 0 GROUP BY meta_value) 
HAVING count " . $meta_query['compare'] . ' ' . (int) $meta_query['value'] . "				

UNION ALL
	
SELECT u.ID, '0' as count 
FROM $wpdb->users as u 
	WHERE u.ID  NOT IN (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_customer_user' AND meta_value > 0 GROUP BY meta_value) 
HAVING count " . $meta_query['compare'] . ' ' . (int) $meta_query['value'] . ') tmpUsers';
					}
					$wp_users_query->query_where .= " AND $wpdb->users.ID IN ($sql) ";
				}
			}
		}

		function filter_users_query_by_last_order_date( $wp_users_query ) {
			global $wpdb;
			if ( empty( $wp_users_query->query_vars['wpse_original_filters'] ) || empty( $wp_users_query->query_vars['wpse_original_filters']['meta_query'] ) ) {
				return;
			}
			$user_data_filters = wp_list_filter(
				$wp_users_query->query_vars['wpse_original_filters']['meta_query'],
				array(
					'key' => $this->last_order_date,
				)
			);
			if ( empty( $user_data_filters ) ) {
				return;
			}
			foreach ( $user_data_filters as $index => $meta_query ) {
				if ( ! in_array( $meta_query['compare'], array( '=', '!=', '>', '>=', '<', '<=' ) ) ) {
					$meta_query['compare'] = '=';
				}
				if ( empty( $meta_query['value'] ) ) {
					continue;
				}

				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $meta_query['value'] ) ) {
					$meta_query['compare'] = 'LIKE';
					$date                  = $meta_query['value'] . '%';
				} else {
					$date = date( 'Y-m-d H:i:s', strtotime( $meta_query['value'] ) );
				}

				$sql                          = "SELECT customer.user_id
FROM {$wpdb->prefix}wc_order_stats orders 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup customer 
ON customer.customer_id = orders.customer_id 
WHERE customer.user_id > 0 
GROUP BY orders.customer_id 
HAVING MAX(date_created) " . $meta_query['compare'] . " '" . $date . "' ";
				$wp_users_query->query_where .= " AND $wpdb->users.ID IN ($sql) ";
			}
		}

		function add_orders_count_filter( $groups, $post_type ) {
			if ( $post_type === 'user' && class_exists( 'WooCommerce' ) ) {
				$groups['wc']   = array( $this->orders_count_key, $this->last_order_date );
				$groups['meta'] = array_diff( $groups['meta'], array( '_last_order', $this->orders_count_key ) );
			}
			return $groups;
		}

		function calculate_sales_totals( $data, $qry ) {
			global $wpdb;
			if ( ! class_exists( 'WooCommerce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return $data;
			}
			if ( $qry['post_type'] !== 'user' ) {
				return $data;
			}

			// We use custom queries for performance reasons.

			$main_query_sql    = $GLOBALS['wpse_main_query']->request;
			$main_user_ids_sql = str_replace(
				array(
					'SQL_CALC_FOUND_ROWS',
					"$wpdb->users.*",
				),
				array(
					'',
					"$wpdb->users.ID",
				),
				$main_query_sql
			);
			if ( strpos( $main_user_ids_sql, 'ORDER BY ' ) !== false ) {
				$main_user_ids_sql = substr( $main_user_ids_sql, 0, strpos( $main_user_ids_sql, 'ORDER BY ' ) );
			}
			if ( empty( $main_user_ids_sql ) ) {
				return $data;
			}
			$get_totals_sql = "SELECT SUM(o.net_total) AS 'net', SUM(total_sales) AS 'gross', SUM(o.num_items_sold) AS 'total_items_sold', COUNT(o.order_id) AS 'total_transactions' FROM {$wpdb->prefix}wc_order_stats o 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup c 
ON c.customer_id = o.customer_id 
WHERE c.user_id IN (" . $main_user_ids_sql . ')';

			$totals            = $wpdb->get_row( $get_totals_sql, ARRAY_A );
			$data['wc_totals'] = array(
				str_replace( '.', '', __( 'Gross Sales.', 'woocommerce' ) ) => wc_price( $totals['gross'] ),
				__( 'Net Sales', 'woocommerce' ) => wc_price( $totals['net'] ),
				str_replace( '.', '', __( 'Items sold.', 'woocommerce' ) ) => intval( $totals['total_items_sold'] ),
				str_replace( '.', '', __( 'Number of orders.', 'woocommerce' ) ) => intval( $totals['total_transactions'] ),
			);

			return $data;
		}

		function add_bulk_edit_button_to_customers_page() {
			if ( ! VGSE()->helpers->user_can_edit_post_type( 'user' ) ) {
				return;
			}
			if ( empty( $_GET['page'] ) || empty( $_GET['path'] ) || $_GET['page'] !== 'wc-admin' || $_GET['path'] !== '/customers' ) {
				return;
			}
			?>
			<style>
				.wpse-bulk-edit-wc-customers .button {
					padding: 10px;
				}
			</style>
			<script>
				setTimeout(function () {
					jQuery('.woocommerce-filters__basic-filters').append('<div class="woocommerce-filters-filter"><span class="woocommerce-filters-label">WP Sheet Editor:</span><div class="wpse-bulk-edit-wc-customers"><div class="components-dropdown"><a class="button button-primary" href=<?php echo json_encode( esc_url( VGSE()->helpers->get_editor_url( 'user' ) ) ); ?>>Bulk edit and delete customers</a> . <a class="button wpse-delete-invalid-customers" href=<?php echo json_encode( esc_url( add_query_arg( 'wpse_wc_customer_lookup_purge', 1, VGSE()->helpers->get_editor_url( 'user' ) ) ) ); ?>>Delete invalid customers</a></div></div></div>');

					jQuery('body').on('click', '.wpse-delete-invalid-customers', function (e) {
						e.preventDefault();
						if (!jQuery(this).data('original-text')) {
							jQuery(this).data('original-text', jQuery(this).text());
						}
						if (confirm('We will automatically delete all the customers from this table with empty email address, or associated to orders or user accounts that were deleted previously. This is not reversible and you should make a database backup if you want to restore later. Do you want to continue with the removal?')) {
							jQuery(this).text('Loading...');
							jQuery.get(jQuery(this).attr('href'), function () {
								alert('The process finished... we will reload the page');
								window.location.reload();
							});
						} else {
							return false;
						}
					});
				}, 2000);
			</script>
			<?php
		}

		function maybe_purge_customer_lookup_table( $post_type ) {
			global $wpdb;
			if ( $post_type !== 'user' || ! $this->customer_lookup_exists() || empty( $_GET['wpse_wc_customer_lookup_purge'] ) ) {
				return;
			}

			$main_blog_prefix = $wpdb->get_blog_prefix( 1 );

			// Delete customers with user_id of deleted users
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_customer_lookup WHERE user_id > 0 AND user_id NOT IN (SELECT ID FROM {$main_blog_prefix}users)" );

			// Delete customers with empty email
			$wpdb->delete(
				$wpdb->prefix . 'wc_customer_lookup',
				array(
					'email' => '',
				)
			);

			// Delete customers with email not used on any orders
			$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			if ( $hpos_enabled ) {
				$sql = "DELETE FROM {$wpdb->prefix}wc_customer_lookup WHERE email NOT IN (SELECT billing_email FROM {$wpdb->prefix}wc_orders GROUP BY billing_email)";
			} else {
				$sql = "DELETE FROM {$wpdb->prefix}wc_customer_lookup WHERE email NOT IN (SELECT meta_value FROM {$wpdb->postmeta} pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE p.post_type = 'shop_order' AND pm.meta_key = '_billing_email' AND pm.meta_value <> '' GROUP BY meta_value)";
			}
			$wpdb->query( $sql );

			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
		}

		function customer_lookup_exists() {
			$out = true;
			if ( ! class_exists( 'WooCommerce' ) || version_compare( WC()->version, '4.0.0' ) < 0 ) {
				$out = false;
			}
			return $out;
		}

		function register_toolbars( $editor ) {

			$post_type = 'user';

			if ( $editor->provider->key !== 'user' || ! $this->customer_lookup_exists() ) {
				return;
			}
			$editor->args['toolbars']->register_item(
				'wc_flush_customers_lookup',
				array(
					'type'              => 'button',
					'url'               => esc_url( add_query_arg( 'wpse_wc_customer_lookup_purge', 1 ) ),
					'allow_in_frontend' => false,
					'content'           => __( 'WC Customers: Flush the cache', VGSE()->textname ),
					'icon'              => 'fa fa-reload',
					'toolbar_key'       => 'secondary',
					'parent'            => 'support',
				),
				$post_type
			);
		}

		function sync_customer_lookup_table( $user_id, $values ) {
			global $wpdb;

			if ( ! $this->customer_lookup_exists() ) {
				return;
			}
			$email             = $values['user_email'];
			$lookup_table_name = \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_db_table_name();
			if ( ! empty( $values['wpse_status'] ) && $values['wpse_status'] === 'delete' ) {

				$customer_ids = $wpdb->get_col( $wpdb->prepare( "SELECT customer_id FROM $lookup_table_name WHERE email = %s", $email ) );
				do_action( 'woocommerce_delete_customer', $user_id );
				foreach ( $customer_ids as $customer_id ) {
					if ( is_numeric( $customer_id ) ) {
						$customer_id = (int) $customer_id;
						\Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::delete_customer( $customer_id );
					}
				}
			} else {
				$customer = new WC_Customer( $user_id );
				do_action( 'woocommerce_update_customer', $user_id, $customer );
			}
			\Automattic\WooCommerce\Admin\API\Reports\Cache::invalidate();
		}

		function register_columns( $editor ) {

			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$post_type = 'user';

			if ( $editor->provider->key !== 'user' ) {
				return;
			}

			$countries_obj = new WC_Countries();
			$countries     = $countries_obj->__get( 'countries' );

			$editor->args['columns']->register_item(
				'billing_first_name',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_first_name' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing first name', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_first_name' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_last_name',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_last_name' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing last name', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_last_name' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_company',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_company' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing company', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_company' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_address_1',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_address_1' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing address 1', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_address_1' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_address_2',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_address_2' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing address 2', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_address_2' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_city',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_city' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing city', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_city' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_postcode',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_postcode' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing post code', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_postcode' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_country',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_country' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing country', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array(
						'data'          => 'billing_country',
						'editor'        => 'select',
						'selectOptions' => $countries,
					),
				)
			);
			$editor->args['columns']->register_item(
				'billing_state',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_state' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing state', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_state' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_phone',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_phone' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing phone', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_phone' ),
				)
			);
			$editor->args['columns']->register_item(
				'billing_email',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'billing_email' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Billing email', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'billing_email' ),
					'value_type'        => 'email',
				)
			);

			$editor->args['columns']->register_item(
				'shipping_first_name',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_first_name' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping first name', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_first_name' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_last_name',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_last_name' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping last name', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_last_name' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_company',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_company' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping company', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_company' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_address_1',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_address_1' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping address 1', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_address_1' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_address_2',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_address_2' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping address 2', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_address_2' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_city',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_city' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping city', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_city' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_postcode',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_postcode' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping post code', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_postcode' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_country',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_country' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping country', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array(
						'data'          => 'shipping_country',
						'editor'        => 'select',
						'selectOptions' => $countries,
					),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_state',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_state' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping state', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_state' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_phone',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_phone' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping phone', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_phone' ),
				)
			);
			$editor->args['columns']->register_item(
				'shipping_email',
				$post_type,
				array(
					'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
					'unformatted'       => array( 'data' => 'shipping_email' ), //Array (Valores admitidos por el plugin de handsontable)
					'column_width'      => 210, //int (Ancho de la columna)
					'title'             => __( 'Shipping email', vgse_users()->textname ), //String (Titulo de la columna)
					'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_save'     => true,
					'allow_to_rename'   => true,
					'formatted'         => array( 'data' => 'shipping_email' ),
					'value_type'        => 'email',
				)
			);
			$editor->args['columns']->register_item(
				'wc_last_order_date',
				$post_type,
				array(
					'data_type'          => 'post_data',
					'unformatted'        => array(
						'renderer' => 'html',
						'readOnly' => true,
					),
					'column_width'       => 75,
					'title'              => __( 'Last purchase date', VGSE()->textname ),
					'type'               => '',
					'supports_formulas'  => false,
					'allow_to_hide'      => true,
					'allow_to_save'      => false,
					'allow_to_rename'    => false,
					'is_locked'          => true,
					'formatted'          => array(
						'renderer' => 'html',
						'readOnly' => true,
					),
					'get_value_callback' => array( $this, 'get_last_purchase_date_for_cell' ),
				)
			);
			$editor->args['columns']->register_item(
				'_money_spent',
				$post_type,
				array(
					'data_type'          => 'post_data',
					'column_width'       => 75,
					'title'              => __( 'Total spent', VGSE()->textname ),
					'type'               => '',
					'supports_formulas'  => false,
					'allow_to_hide'      => true,
					'allow_to_save'      => false,
					'allow_to_rename'    => false,
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_total_spent_for_cell' ),
				)
			);
			$editor->args['columns']->register_item(
				'_wc_aov',
				$post_type,
				array(
					'data_type'          => 'post_data',
					'column_width'       => 75,
					'title'              => __( 'Average order value', VGSE()->textname ),
					'type'               => '',
					'supports_formulas'  => false,
					'allow_to_hide'      => true,
					'allow_to_save'      => false,
					'allow_to_rename'    => false,
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_aov_for_cell' ),
				)
			);
			$editor->args['columns']->register_item(
				$this->orders_count_key,
				$post_type,
				array(
					'data_type'          => 'post_data',
					'column_width'       => 75,
					'title'              => __( 'Orders count', VGSE()->textname ),
					'type'               => '',
					'supports_formulas'  => false,
					'allow_to_hide'      => true,
					'allow_to_save'      => false,
					'allow_to_rename'    => false,
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_count_for_cell' ),
				)
			);

			if ( function_exists( 'vgse_columns_manager_init' ) ) {
				$base_args = array(
					'data_type'                => 'meta_data',
					'title'                    => __( 'Last active', VGSE()->textname ),
					'type'                     => '',
					'supports_formulas'        => false,
					'allow_to_hide'            => true,
					'allow_to_save'            => false,
					'allow_to_rename'          => true,
					'is_locked'                => true,
					'columns_manager_settings' => array(
						'field_type'            => 'date_time',
						'date_time_format_save' => 'U',
					),
				);
				$base_args = wp_parse_args( vgse_columns_manager_init()->get_format_settings_for_date_time_column( 'wc_last_active', 'U', 'Y-m-d H:i:s' ), $base_args );
				$editor->args['columns']->register_item( 'wc_last_active', $post_type, $base_args );
			}

		}

		function get_aov_for_cell( $post, $cell_key, $cell_args ) {
			global $wpdb;
			$value = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 
SUM({$wpdb->prefix}wc_order_stats.net_total) / COUNT({$wpdb->prefix}wc_order_stats.order_id) AS avg_order_value 
FROM {$wpdb->prefix}wc_order_stats 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup 
ON {$wpdb->prefix}wc_order_stats.customer_id = {$wpdb->prefix}wc_customer_lookup.customer_id 
WHERE {$wpdb->prefix}wc_customer_lookup.user_id = %d LIMIT 1",
					$post->ID
				)
			);
			return $value;
		}

		function get_total_spent_for_cell( $post, $cell_key, $cell_args ) {
			global $wpdb;
			$value = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 
SUM({$wpdb->prefix}wc_order_stats.net_total) as total
FROM {$wpdb->prefix}wc_order_stats 
LEFT JOIN {$wpdb->prefix}wc_customer_lookup 
ON {$wpdb->prefix}wc_order_stats.customer_id = {$wpdb->prefix}wc_customer_lookup.customer_id 
WHERE {$wpdb->prefix}wc_customer_lookup.user_id = %d LIMIT 1",
					$post->ID
				)
			);
			return $value;
		}

		function get_last_purchase_date_for_cell( $post, $cell_key, $cell_args ) {
			$last_order_data = wc_get_customer_last_order( $post->ID );
			$value           = is_object( $last_order_data ) ? $last_order_data->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
			return $value;
		}
		function get_order_count_for_cell( $post, $cell_key, $cell_args ) {
			$customer = new WC_Customer( $post->ID );
			$value    = $customer ? $customer->get_order_count() : 0;
			return $value;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WP_Sheet_Editor_Users_WooCommerce();
				self::$instance->init();
			}
			return self::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}

	}

}

if ( ! function_exists( 'WP_Sheet_Editor_Users_WooCommerce_Obj' ) ) {

	function WP_Sheet_Editor_Users_WooCommerce_Obj() {
		return WP_Sheet_Editor_Users_WooCommerce::get_instance();
	}
}
add_action( 'vg_sheet_editor/after_init', 'WP_Sheet_Editor_Users_WooCommerce_Obj' );
