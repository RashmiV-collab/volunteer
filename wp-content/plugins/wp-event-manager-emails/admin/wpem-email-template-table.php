<?php
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPEM_Email_Template_List_Table extends WP_List_Table {
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

     /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns(){
        $columns = array(
            'id'          => __('ID','wp-event-managerer-email'),
            'name'       => __('Name','wp-event-manager-emails'),
            'type' => __('Post Type','wp-event-manager-emails'),
            'status_before'        => __('Status Before','wp-event-manager-emails'),
            'status_after'        => __('Status After','wp-event-manager-emails'),
            'status_after'        => __('Status After','wp-event-manager-emails'),
            'date_created'        => __('Date','wp-event-manager-emails'),
            'action'        => __('Action','wp-event-manager-emails'),
            
        );
        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns(){
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns(){
        return array('name' => array('name', false));
    }

     /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data(){
    	global $wpdb;
        $table_name = $wpdb->prefix . 'wpem_email_templates'; // do not forget about tables prefix
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $per_page = 2; // constant, how much records will be shown per page

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d", $total_items), ARRAY_A);

        return $this->items;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'id':
            case 'name':
            case 'post_type':
            case 'status_before':
            case 'status_after':
            case 'date_created':
                return $item[ $column_name ];
            case 'action':
            	$edit = admin_url( 'edit.php?post_type=event_listing&page=event-emails-notifications&tab=event-notification-templates&edit='.$item[ 'id' ]);
            	$delete = admin_url( 'edit.php?post_type=event_listing&page=event-emails-notifications&tab=event-notification-templates&delete_template='.$item[ 'id' ]);

            	return '<a href="'.$edit.'" class="button">Edit</a> <a href="'.$delete.'" class="button">Delete</a>';
            default:
                return '' ;
        }
    }
}