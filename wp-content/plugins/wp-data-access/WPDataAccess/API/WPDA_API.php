<?php

// phpcs:ignore Standard.Category.SniffName.ErrorCode
/**
 * JSON REST API.
 */
namespace WPDataAccess\API;

use  WPDataAccess\WPDA ;
/**
 * JSON REST API main class.
 */
class WPDA_API
{
    const  WPDA_NAMESPACE = 'wpda' ;
    const  WPDA_REST_API_TABLE_ACCESS = 'wpda_rest_api_table_access' ;
    /**
     * Register routes.
     *
     * @return void
     */
    public function init()
    {
        register_rest_route( self::WPDA_NAMESPACE, 'info', array(
            'methods'             => array( \WP_REST_Server::READABLE ),
            'callback'            => function () {
            $license = 'free';
            return self::WPDA_Rest_Response( '', array(
                'license' => $license,
                'version' => WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            ) );
        },
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/dbs', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_dbs' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/tbl', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_tbl' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/vws', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_vws' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/cls', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_cls' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/idx', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_idx' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/trg', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_trg' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/frk', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_frk' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/fnc', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_fnc' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'tree/prc', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'tree_prc' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/lov', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_lov' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'col' => array(
            'required'          => true,
            'description'       => __( 'Column name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/select', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_select' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs'           => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl'           => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'col'           => array(
            'required'          => true,
            'description'       => __( 'Table or view columns', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            $columns = array();
            foreach ( rest_sanitize_object( $param ) as $column_name => $queryable ) {
                $columns[WPDA::remove_backticks( $column_name )] = $queryable === true;
            }
            return $columns;
        },
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
            'pageIndex'     => array(
            'required'          => false,
            'description'       => __( 'Page number', 'wp-data-access' ),
            'default'           => 1,
            'minimum'           => 1,
            'sanitize_callback' => function ( $param ) {
            return sanitize_text_field( wp_unslash( $param ) );
        },
            'validate_callback' => function ( $param ) {
            return is_numeric( $param ) && $param >= 0;
        },
        ),
            'pageSize'      => array(
            'required'          => false,
            'description'       => __( 'Rows per page (0=all)', 'wp-data-access' ),
            'default'           => 10,
            'minimum'           => 0,
            'sanitize_callback' => function ( $param ) {
            return sanitize_text_field( wp_unslash( $param ) );
        },
            'validate_callback' => function ( $param ) {
            return is_numeric( $param ) && $param >= 0;
        },
        ),
            'search'        => array(
            'required'          => false,
            'description'       => __( 'Search filter', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return sanitize_text_field( wp_unslash( $param ) );
        },
        ),
            'searchColumns' => array(
            'required'          => false,
            'description'       => __( 'Column search filter', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            $search = array();
            foreach ( rest_sanitize_array( $param ) as $value ) {
                if ( isset( $value['id'], $value['value'] ) ) {
                    $search[] = array(
                        'id'    => WPDA::remove_backticks( sanitize_text_field( wp_unslash( $value['id'] ) ) ),
                        'value' => sanitize_text_field( wp_unslash( $value['value'] ) ),
                    );
                }
            }
            return $search;
        },
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
            'sorting'       => array(
            'required'          => false,
            'description'       => __( 'Order by (array of { id and desc })', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            $order_by = array();
            foreach ( rest_sanitize_object( $param ) as $value ) {
                
                if ( isset( $value['id'], $value['desc'] ) ) {
                    $order_by['id'] = WPDA::remove_backticks( sanitize_text_field( wp_unslash( $value['id'] ) ) );
                    $order_by['desc'] = ( '1' == $value['desc'] ? 'desc' : 'asc' );
                }
            
            }
            return $order_by;
        },
            'validate_callback' => function ( $param ) {
            if ( !is_array( $param ) ) {
                return false;
            }
            foreach ( $param as $value ) {
                if ( !isset( $value['id'], $value['desc'] ) ) {
                    return false;
                }
            }
            return true;
        },
        ),
            'rowCount'      => array(
            'required'          => false,
            'description'       => __( 'Row count', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return sanitize_text_field( wp_unslash( $param ) );
        },
        ),
            'media'         => array(
            'required'          => false,
            'description'       => __( 'Media columns', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return rest_sanitize_object( $param );
        },
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/get', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_get' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs'   => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl'   => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'key'   => array(
            'required'          => true,
            'description'       => __( 'Primary key', 'wp-data-access' ),
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
            'media' => array(
            'required'          => true,
            'description'       => __( 'Media columns', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return rest_sanitize_object( $param );
        },
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/insert', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_insert' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'val' => array(
            'required'          => true,
            'description'       => __( 'Values to be inserted', 'wp-data-access' ),
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/update', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_update' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'key' => array(
            'required'          => true,
            'description'       => __( 'Primary key', 'wp-data-access' ),
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
            'val' => array(
            'required'          => true,
            'description'       => __( 'Values to be updated', 'wp-data-access' ),
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/delete', array(
            'methods'             => array( \WP_REST_Server::READABLE, 'POST' ),
            'callback'            => array( $this, 'table_delete' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'key' => array(
            'required'          => true,
            'description'       => __( 'Primary key', 'wp-data-access' ),
            'validate_callback' => function ( $param ) {
            return is_array( $param );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'table/meta', array(
            'methods'             => array( 'GET', 'POST' ),
            'callback'            => array( $this, 'table_meta' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'dbs' => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl' => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
        ),
        ) );
        register_rest_route( self::WPDA_NAMESPACE, 'save-settings', array(
            'methods'             => array( 'POST' ),
            'callback'            => array( $this, 'save_settings' ),
            'permission_callback' => '__return_true',
            'args'                => array(
            'action'   => array(
            'required'          => true,
            'description'       => __( 'Setting type', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return sanitize_text_field( wp_unslash( $param ) );
        },
            'validate_callback' => function ( $param ) {
            return 'dashboard_menus' === $param || 'table_settings' === $param || 'column_settings' === $param || 'rest_api' === $param || 'admin_settings' === $param;
        },
        ),
            'dbs'      => array(
            'required'          => true,
            'description'       => __( 'Local database name or remote connection string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'tbl'      => array(
            'required'          => true,
            'description'       => __( 'Table or view name', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            return WPDA::remove_backticks( sanitize_text_field( wp_unslash( $param ) ) );
        },
        ),
            'settings' => array(
            'required'          => true,
            'description'       => __( 'Settings JSON as string', 'wp-data-access' ),
            'sanitize_callback' => function ( $param ) {
            
            if ( is_string( $param ) ) {
                return sanitize_textarea_field( $param );
            } else {
                return $param;
                // Object validation performed in callback.
            }
        
        },
        ),
        ),
        ) );
    }
    
    /**
     * Get table meta info.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_meta( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'select'
        ) ) {
            return $this->WPDA_Rest_Response( '', WPDA_Table::get_table_meta_data( $dbs, $tbl ) );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Save plugin settings.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function save_settings( $request )
    {
        if ( !$this->current_user_can_access() ) {
            // Only admins.
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
        if ( !wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
            // Invalid nonce.
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( 'admin_settings' !== $request->get_param( 'action' ) ) {
            $settings_string = $request->get_param( 'settings' );
            if ( !is_string( $settings_string ) ) {
                return new \WP_Error( 'error', __( 'Bad request', 'wp-data-access' ), array(
                    'status' => 400,
                ) );
            }
            $settings = json_decode( $settings_string );
            if ( false === $settings || is_null( $settings ) ) {
                return new \WP_Error( 'error', __( 'Bad request', 'wp-data-access' ), array(
                    'status' => 400,
                ) );
            }
        } else {
            $settings = $request->get_param( 'settings' );
        }
        
        switch ( $request->get_param( 'action' ) ) {
            case 'dashboard_menus':
                return WPDA_Settings::save_dashboard_menus( $dbs, $tbl, $settings );
            case 'table_settings':
                return WPDA_Settings::save_table_settings( $dbs, $tbl, $settings );
            case 'column_settings':
                return WPDA_Settings::save_column_settings( $dbs, $tbl, $settings );
            case 'rest_api':
                return WPDA_Settings::save_rest_api_settings( $dbs, $tbl, $settings );
            case 'admin_settings':
                return WPDA_Settings::save_admin_settings( $dbs, $tbl, $settings );
        }
        return new \WP_Error( 'error', __( 'Bad request', 'wp-data-access' ), array(
            'status' => 400,
        ) );
    }
    
    private function current_user_can_access()
    {
        // Users allowed to access the Data Explorer
        $wpda_dev_ip_address = get_option( 'wpda_dev_ip_address' );
        if ( $wpda_dev_ip_address === $_SERVER['REMOTE_ADDR'] ) {
            return true;
        }
        return current_user_can( 'manage_options' );
    }
    
    public function tree_dbs()
    {
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_dbs();
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_tbl( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_tbl( $dbs );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_vws( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_vws( $dbs );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_cls( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_cls( $dbs, $tbl );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_idx( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_idx( $dbs, $tbl );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_trg( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_trg( $dbs, $tbl );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_frk( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_frk( $dbs, $tbl );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_fnc( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_fnc( $dbs );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    public function tree_prc( $request )
    {
        $dbs = $request->get_param( 'dbs' );
        
        if ( $this->current_user_can_access() ) {
            return WPDA_Tree::get_prc( $dbs );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Database table query using the full primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_get( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        $media = $request->get_param( 'media' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'select'
        ) ) {
            return WPDA_Table::get(
                $dbs,
                $tbl,
                $key,
                $media
            );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Insert one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_insert( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $val = $request->get_param( 'val' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'insert'
        ) ) {
            return WPDA_Table::insert( $dbs, $tbl, $val );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Update uses primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_update( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        $val = $request->get_param( 'val' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'update'
        ) ) {
            return WPDA_Table::update(
                $dbs,
                $tbl,
                $key,
                $val
            );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Delete uses primary key. Must return exactly one row.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_delete( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $key = $request->get_param( 'key' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'delete'
        ) ) {
            return WPDA_Table::delete( $dbs, $tbl, $key );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Database table query to populate a list of values for a specific table/column.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_lov( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $col = $request->get_param( 'col' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'select'
        ) ) {
            return WPDA_Table::lov( $dbs, $tbl, $col );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Database table query.
     *
     * Supports: searching, ordering and pagination.
     *
     * @param WP_REST_Request $request Rest API request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function table_select( $request )
    {
        // Get arguments (already sanitized and validated).
        $dbs = $request->get_param( 'dbs' );
        $tbl = $request->get_param( 'tbl' );
        $col = $request->get_param( 'col' );
        $pageIndex = $request->get_param( 'pageIndex' );
        $pageSize = $request->get_param( 'pageSize' );
        $search = $request->get_param( 'search' );
        $searchColumns = $request->get_param( 'searchColumns' );
        $sorting = $request->get_param( 'sorting' );
        $rowCount = $request->get_param( 'rowCount' );
        $media = $request->get_param( 'media' );
        
        if ( WPDA_Table::check_table_access(
            $dbs,
            $tbl,
            $request,
            'select'
        ) ) {
            return WPDA_Table::select(
                $dbs,
                $tbl,
                $col,
                $pageIndex,
                $pageSize,
                $search,
                $searchColumns,
                $sorting,
                $rowCount,
                $media
            );
        } else {
            return new \WP_Error( 'error', __( 'Unauthorized', 'wp-data-access' ), array(
                'status' => 401,
            ) );
        }
    
    }
    
    /**
     * Write standard JSON response.
     *
     * @param string $message Response text message.
     * @param mixed  $data Response data.
     * @param mixed  $context Context data.
     * @return \WP_REST_Response
     */
    public static function WPDA_Rest_Response(
        $message = '',
        $data = null,
        $context = null,
        $meta = null
    )
    {
        // Prepare response.
        $response = new \WP_REST_Response( array(
            'code'    => 'ok',
            'message' => $message,
            'data'    => $data,
            'context' => $context,
            'meta'    => $meta,
        ), 200 );
        // Disable caching.
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Expires', '0' );
        return $response;
    }

}