<?php
if (!class_exists('WP_List_Table')) {
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**  
 * Create a new table class that will extend the WP_List_Table 
 */
class WPEM_Recurring_Events extends WP_List_Table {
	
	/** * Prepare the items for the table to process 
	 * @since 1.4.1
	 * @param 
	 * @return Void 
	*/
	public function prepare_items() {
		
		$columns = $this->get_columns();
		$this->_column_headers = array(
		$columns//,
		);
		/** Process bulk action */
		$per_page = get_option('event_manager_per_page') ? get_option('event_manager_per_page' ) : 10 ;
		$current_page = $this->get_pagenum();
		$total_items = self::record_count();
		$data = self::get_records($per_page, $current_page);
		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page // WE have to determine how many items to show on a page
		]);
		$this->items = $data;
	}

	/** * Retrieve records data from the database
	 * @since 1.4.1
	 * @param int $per_page
	 * @param int $page_number
	 * @return mixed
	*/
	public static function get_records($per_page, $page_number) {
		  
		$paged = $page_number ? $page_number : 1;   
		$args = array(
			'post_type'   => 'event_listing',
			'post_status' => array( 'publish', 'expired' ),
			'post_parent' =>  0,
			'posts_per_page' => $per_page,												
			'paged' => $paged
		);
		$args['meta_query'] = array( 
			'relation' => 'AND', 
			array(
					'key'     => '_event_recurrence',
					'value'   => 'no', 
					'compare' => 'NOT IN',
				)
	   	); 
		$result = get_posts( $args );
		return $result;
	}

	/** 
	* Override the parent columns method. Defines the columns to use in your listing table 
	* @since 1.4.1
	*  @return Array 
	*/
	function get_columns() {
		$columns = [
		    'ID'                  => __('ID','wp-event-manager-recurring-events'),
			'event_title'         => __('Event Title','wp-event-manager-recurring-events'),
		    'event_start_date'    => __('Start Date','wp-event-manager-recurring-events'),
		    'event_end_date'      => __('End Date','wp-event-manager-recurring-events'),
		    'action'              => __('Action','wp-event-manager-recurring-events')
		];
		return $columns;
	}
	
    /**
     * Define what data to show on each column of the table
     * @since 1.4.1
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ) {

    	wp_enqueue_style('wp-event-manager-recurring-events-backend');
    	wp_enqueue_script( 'jquery-tiptip' );

    	$event_start_date = get_post_meta( $item->ID,'_event_start_date',true );
    	$event_end_date   = get_post_meta( $item->ID,'_event_end_date',true );
    	$registration_expiry_date=get_post_meta( $item->ID, '_event_registration_deadline', true);

        switch( $column_name ) {
			case 'ID':
				echo $item->ID;
				break;
			case 'event_title':
				echo '<a href="' . get_edit_post_link( $item->ID ) . '">' . $item->post_title . '</a>';
				break;
			case 'event_start_date':
				if ($event_start_date) {
					$format = get_option('date_format');
					$datepicker_date_format = WP_Event_Manager_Date_Time::get_datepicker_format();
					if ($datetime = DateTime::createFromFormat("'.$datepicker_date_format.'", "'.$event_start_date.'")) {
						$date = $datetime->format($format);
					} else {
						$date = date_i18n(get_option('date_format'), strtotime($event_start_date));
					}
					printf(__('%s','wp-event-manager-recurring-events'),$date);
				} else {
					printf(__('-'));
				}				
				break;
			case 'event_end_date':
				if ($event_end_date) {
					$format = get_option('date_format');
					$datepicker_date_format = WP_Event_Manager_Date_Time::get_datepicker_format();
					if ($datetime = DateTime::createFromFormat("'.$datepicker_date_format.'", "'.$event_end_date.'")) {
						$date = $datetime->format($format);
					} else {
						$date = date_i18n(get_option('date_format'), strtotime($event_end_date));
					}
					printf(__('%s','wp-event-manager-recurring-events'),$date);
				} else {
					printf(__('-'));
				}
				break;
			case 'action':
			    $check_recurrence = get_post_meta($item->ID, '_check_event_recurrence', true);

			    $recurring_btn_lable = ($check_recurrence>0) ? __('Duplicate all occurrence again','wp-event-manager-recurring-events') : __('Duplicate all occurrence','wp-event-manager-recurring-events');

			    if(get_post_status($item->ID) === 'publish') {
			    	echo '<a href="javascript:void(0);" class="button button-icon tips recurring-now-btn" data-tip="'.__('Duplicate all occurrences','wp-event-manager-recurring-events').'" id="'.$item->ID.'_recurre" data-start-date="'.$event_start_date.'" data-end-date="'.$event_end_date.'" data-registration-expiry-date="'.$registration_expiry_date.'" data-eventid="'.$item->ID.'">'.$recurring_btn_lable.'</a>';
			    }	
			    if($check_recurrence>0 && count(get_children($item->ID))>0) {
			        echo '<a class="button button-icon tips view" data-tip="'.__('View','wp-event-manager-recurring-events').'"  id="'.$item->ID.'_view" href="'.admin_url( 'edit.php?post_type=event_listing&post_parent='.$item->ID, '' ).'">'.esc_html__('View','wp-event-manager-recurring-events').'</a>';
			        echo '<a href="javascript:void(0);" class="button button-icon tips recurring-delete-btn" data-tip="'.__('Delete occurrence','wp-event-manager-recurring-events').'"  id="'.$item->ID.'_recurre_delete" data-eventid="'.$item->ID.'">'. __('Delete occurrence','wp-event-manager-recurring-events'). '</a>';
			    }

			    echo '<span class="spinner"></span>';
				break;
       	}
    }
	
	/** 
	* Text displayed when no record data is available 
	* @since 1.4.1
	* @param
	* @return
	*/
	public function no_items() {
		_e('No record found in the database.', 'wp-event-manager-recurring-events');
	}

	/** 
	* Returns the count of records in the database. 
	* @since 1.4.1 
	* @param 
	* @return null|string 
	*/
	public static function record_count() {
		$args = array(
			'post_type'   => 'event_listing',
			'post_status' => array('publish', 'expired'),
			'post_parent' =>  0,
			'posts_per_page' => -1,	
		);
		$args['meta_query'] = array( 
			'relation' => 'AND', 
			array(
					'key'     => '_event_recurrence',
					'value'   => 'no', 
					'compare' => 'NOT IN',
				)
	   	); 
		$total_post = count(get_posts( $args ));
		return $total_post;
	}
}