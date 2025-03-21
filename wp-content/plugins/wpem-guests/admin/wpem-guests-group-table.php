<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WPEM_Guests_Group_Table class.
 * 
 * @extends WP_List_Table
 */
class WPEM_Guests_Group_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'group_name', //Singular label
            'plural'   => 'groups_name', //plural label, also this well be one of the table css class
            'ajax'     => false //We won't support Ajax for this table
        ));
    }

    /**
     * column_default function.
     * 
     * @access public
     * @param mixed $post
     * @param mixed $column_name
     */
    public function column_default( $item, $column_name ) {
        global $wpdb;

        $edit_url = add_query_arg( array(
                        'post_type' => 'event_guests',
                        'page'      => 'event-guests-groups',
                        'edit'      => $item->id,
                    ), admin_url('edit.php') );

        $delete_url = add_query_arg( array(
                        'post_type' => 'event_guests',
                        'page'      => 'event-guests-groups',
                        'delete'      => $item->id,
                    ), admin_url('edit.php') );

        $get_user = get_user_by('ID', $item->user_id);

        $event = get_post( $item->event_id );

        $guests = get_guests($item->id);
        
        switch( $column_name ) {
            case 'id' :
                return '<a href="'.$edit_url.'">'.$item->id.'</a>';
            
            case 'user_id' :
                return isset($get_user->data->display_name) ? $get_user->data->display_name : '';
            
            case 'event_id' :
                if ( $event && $event->post_type === 'event_listing' && $event->post_status === 'publish' ) {
                    return '<a href="' . get_edit_post_link( $event->ID ) . '" class="tips" data-tip="' . sprintf( __( 'Event ID: %d', 'wp-event-manager-guests' ), $event->ID ) . '" >' . $event->post_title . '</a>';
                } else {
                    return ' - ';
                }
            
            // case 'group_name' :
            //     return '<a href="'.$edit_url.'" class="tips" data-tip="' . sprintf( __( 'Group ID: %d', 'wp-event-manager-guests' ), $item->id ) . '" >'.$item->group_name.'</a>';
            case 'group_name' :
                if(!empty($item->group_name))
                {
                    return '<a href="'.$edit_url.'" class="tips" data-tip="' . sprintf( __( 'Group ID: %d', 'wp-event-manager-guests' ), $item->id ) . '" >'.$item->group_name.'</a>';
                }
                else
                {
                    return ' - ';
                }                
            case 'guest' :
                if(!empty($guests))
                {
                    return '<a href="'.admin_url('edit.php?post_type=event_guests&_event_listing=').$item->event_id.'" >'. count($guests) .'</a>';
                }
                else
                {
                    return ' - ';
                }                
            
            case 'group_description' :
                return $item->group_description;

            case 'actions' :
                $actions = [];
                $actions ['edit'] = array (
                                        'label' => __ ( 'Edit', 'wp-event-manager-guests' ),
                                        'url' => $edit_url,
                                        'icon' => 'dashicons dashicons-edit',
                                    );
                $actions ['delete'] = array (
                                        'label' => __ ( 'Delete', 'wp-event-manager-guests' ),
                                        'url' => $delete_url,
                                        'icon' => 'dashicons dashicons-trash',
                                    );

                $actions = apply_filters ( 'event_manager_group_dashboard_admin_actions', $actions, $item );

                $html = '<div class="group-dashboard-actions">';

                foreach ($actions as $key => $action) 
                {
                    $html .= '<a href="'.$action['url'].'" class="button tips group-dashboard-action-'.$key.'" data-tip="'. esc_html( $action['label'] ) .'"><span class="'.$action['icon'].'"></span></a> ';
                }
                $html .= '</div>';

                return $html;
        }
    }

    /**
     * column_cb function.
     * 
     * @access public
     * @param mixed $item
     */
    public function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            'id',
            $item->id
        );
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    public function get_columns()
    {
        return $columns = array(
            'cb'                => '<input type="checkbox" />', 
            'id'                => __('ID', 'wp-event-manager-guests'),
            'user_id'           => __('User', 'wp-event-manager-guests'),
            'event_id'          => __('Event', 'wp-event-manager-guests'),
            'group_name'        => __('Group Name', 'wp-event-manager-guests'),
            'guest'             => __('Guests', 'wp-event-manager-guests'),
            'group_description' => __('Group Description', 'wp-event-manager-guests'),
            'actions'            => __('Actions', 'wp-event-manager-guests'),
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'id'            => array( 'id', true ),     //true means its already sorted
            'group_name'    => array( 'group_name', false ),
        );
        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /** 
     * Process bulk actions
     */
    public function process_bulk_action() {
        global $wpdb;
        
        if ( ! isset( $_POST['id'] ) ) {
            return;
        }
        
        $items = array_map( 'sanitize_text_field', $_POST['id'] );

        if ( $items ) {
            switch ( $this->current_action() ) {
                case 'delete' :
                    foreach ( $items as $id ) {
                        delete_event_guests_group($id);
                    }
                    echo '<div class="updated"><p>' . sprintf( __( '%d group deleted.', 'wp-event-manager-guests' ), sizeof( $items ) ) . '</p></div>';
                break;
            }
        }
    }

    /**
     * prepare_items function.
     * 
     * @access public
     */
    public function prepare_items() {
        global $wpdb;
        
        $current_page   = $this->get_pagenum();
        $per_page       = 20;
        $orderby        = ! empty( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
        $order          = ! empty( $_REQUEST['order'] ) &&  ( $_REQUEST['order'] === 'desc' ) ? 'DESC' : 'ASC';
        $order_id       = ! empty( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : '';
        $search_group_name  = ! empty( $_REQUEST['search_group_name'] ) ? $_REQUEST['search_group_name'] : '';
        $search_event_name  = ! empty( $_REQUEST['search_event_name'] ) ? $_REQUEST['search_event_name'] : '';

        /**
         * Init column headers
         */
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
        
        /**
         * Process bulk actions
         */
        $this->process_bulk_action();

        $where = array( 'WHERE 1=1' );
        if ( $order_id ) {
            $where[] = 'AND order_id=' . $order_id;
        }
        if ( $search_group_name != '' ) {

            $where_search = 'AND "' . $search_group_name . '" IN (group_name)';
            
            $where[] = $where_search;
        }
        if ( $search_event_name != '' ) {

            $where_search = 'AND "' . $search_event_name . '" IN (event_id)';
            
            $where[] = $where_search;
        }
        $where = implode( ' ', $where );
        
        /**
         * Get items
         */
        $max = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}wpem_guests_group $where;" );
        
        $this->items = $wpdb->get_results( $wpdb->prepare( "
            SELECT * FROM {$wpdb->prefix}wpem_guests_group
            $where
            ORDER BY `{$orderby}` {$order} LIMIT %d, %d
        ", ( $current_page - 1 ) * $per_page, $per_page ) );

        /**
         * Pagination
         */
        $this->set_pagination_args( array(
            'total_items' => $max, 
            'per_page'    => $per_page,
            'total_pages' => ceil( $max / $per_page )
        ) );

        /*
        echo '<pre>';
        print_r($wpdb->last_query);
        print_r($wpdb->last_result);
        echo '</pre>' . __FILE__ . ' ( Line Number ' . __LINE__ . ')';
        */
    }

}