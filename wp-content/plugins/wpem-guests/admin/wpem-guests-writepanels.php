<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'WP_Event_Manager_Writepanels' ) && defined( 'EVENT_MANAGER_PLUGIN_DIR' ) ) {
    include( EVENT_MANAGER_PLUGIN_DIR . '/admin/wp-event-manager-writepanels.php' );
}

/**
 * WPEM_Guests_Writepanels class.
 */
if ( class_exists( 'WP_Event_Manager_Writepanels' ) ) 
{
    class WPEM_Guests_Writepanels extends WP_Event_Manager_Writepanels  {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
            add_action( 'event_guests_save_event_guests', array( $this, 'save_guest_lists_group_data' ), 1, 2 );

            add_filter( 'event_manager_admin_screen_ids', array( $this, 'screen_ids' ) );

            add_filter( 'manage_event_guests_posts_columns', array( $this, 'event_guests_columns' ), 10 );
            add_action( 'manage_event_guests_posts_custom_column', array( $this, 'event_guests_columns_data' ), 10, 2 );

           
        }

        /**
         * Add screen ids to Em
         * @param  array $ids
         * @return array
         */
        public function screen_ids( $ids ) {
            $ids[] = 'edit-event_guests';
            $ids[] = 'event_guests';
            $ids[] = 'event_guest_list_page_event-guest-lists-groups';
            return $ids;
        }

        /**
         * add_meta_boxes function.
         *
         * @access public
         * @return void
         */
        public function add_meta_boxes()
        {
             add_meta_box( 'guest_lists_group', __( 'Guest Lists Group', 'wp-event-manager-guests' ), array( $this, 'guest_lists_group' ), 'event_guests', 'side', 'high' );
            add_meta_box( 'guest_lists_group_data', __( 'Guest Lists Group Data', 'wp-event-manager-guests' ), array( $this, 'guest_lists_group_data' ), 'event_guests', 'normal', 'high' );
        }

        /**
         * Publish meta box
         */
        public function guest_lists_group() 
        {
            global $post, $thepostid;

            $thepostid = $post->ID;

            $user_id = get_current_user_id();

            wp_enqueue_script( 'wpem-guests-admin');

            $groups = get_event_guests_group('', $user_id, '');

            $selected_group = get_post_meta($thepostid, '_guests_group', true);

            $args = [
                'post_type'   => 'event_listing',
                'post_status' => 'publish',
                'posts_per_page'    => -1,
                //'author'      => get_current_user_id(),
            ];

            $events = get_posts($args);
            wp_nonce_field( 'save_meta_data', 'wpem_guests_group_nonce' );


            $event_id = $post->post_parent;
            ?>
            <div id="guest_lists_group" class="groupdiv">
                    <!-- Display event -->

                    <!-- Display group -->
                    <div id="guest_lists_group_all" class="tabs-panel">
                         <select name="post_parent" id="dropdown_event_listing" class="form-no-clear">
                            <option value="">Select event</option>
                            <?php if(!empty( $events))
                            {
                                foreach ($events as $event) {
                                    ?>
                                   <option value="<?php echo $event->ID;?>" <?php selected( $event_id, $event->ID ); ?> ><?php echo $event->post_title;?></option>
                                   <?php
                                }
                            } 
                            ?>
                        </select>

                        <select id="groupchecklist" id="_guests_group" name="_guests_group" class="form-no-clear" required>
                            <option value=""><?php _e('Select group', 'wp-event-manager-guests'); ?></option>
                            <?php if( isset($groups) && !empty($groups) ) : ?>
                                <?php foreach ( $groups as $group ) : 
                                    echo '<option value="'.$group->id.'" ' . selected($selected_group, $group->id) . ' >' . $group->group_name . '</option>';
                                endforeach; ?>
                            <?php endif; ?>
                       </select> 
                    </div>
        
                </div>
            
            <?php

            

        }

        /**
         * Event guest lists fields
         *
         * @return array
         */
        public function guest_lists_fields() {
            global $post;

            $fields = get_event_guests_form_fields();
           
            $fields['event_gust_list_author'] = array(
                'label'       => __( 'Posted by', 'wp-event-manager-guests' ),
                'type'        => 'author',
                'placeholder' => ''
            );

            return $fields;
        }

        /**
         * Publish meta box
         */
        public function guest_lists_group_data( $post ) 
        {
            global $post, $thepostid;

            $thepostid = $post->ID;

            $selected_group = get_post_meta($thepostid, '_guests_group', true);

            echo '<div class="wp_event_manager_meta_data guest-lists-group-data">';

            wp_nonce_field( 'save_meta_data', 'wpem_guests_group_nonce' );

            do_action( 'guest_lists_group_data_start', $thepostid );

            $fields = $this->guest_lists_fields();

            if( isset($selected_group) && !empty($selected_group) )
            {
                $group = get_event_guests_group( $selected_group );

                if( isset($group) && !empty($group) )
                {
                    $group_fields = json_decode($group->group_fields, true);
            
                    $group_fields[] = 'event_gust_list_author';

                    foreach ($fields as $key => $field) 
                    {
                        if( in_array($key, $group_fields) )
                        {
                            $type = ! empty( $field['type'] ) ? $field['type'] : 'text';

                            if ( method_exists( $this, 'input_' . $type ) ) 
                            {
                                call_user_func( array( $this, 'input_' . $type ), $key, $field );
                            } 
                            else 
                            {
                                do_action( 'wpem_guests_group_input_' . $type, $key, $field );
                            }    
                        }
                    }
                }
                else
                {
                    foreach ($fields as $key => $field) 
                    {
                        $type = ! empty( $field['type'] ) ? $field['type'] : 'text';

                        if ( method_exists( $this, 'input_' . $type ) ) 
                        {
                            call_user_func( array( $this, 'input_' . $type ), $key, $field );
                        } 
                        else 
                        {
                            do_action( 'wpem_guests_group_input_' . $type, $key, $field );
                        }
                    }
                }
            }
            else
            {
                foreach ($fields as $key => $field) 
                {
                    $type = ! empty( $field['type'] ) ? $field['type'] : 'text';

                    if ( method_exists( $this, 'input_' . $type ) ) 
                    {
                        call_user_func( array( $this, 'input_' . $type ), $key, $field );
                    } 
                    else 
                    {
                        do_action( 'wpem_guests_group_input_' . $type, $key, $field );
                    }
                }
            }

            do_action( 'guest_lists_group_data_end', $post->ID );

            echo '</div>';
        }

        /**
         * Triggered on Save Post
         *
         * @param mixed $post_id
         * @param mixed $post
         */
        public function save_post( $post_id, $post ) {
            if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
            if ( is_int( wp_is_post_revision( $post ) ) ) return;
            if ( is_int( wp_is_post_autosave( $post ) ) ) return;
            if ( empty( $_POST[ 'wpem_guests_group_nonce' ] ) || ! wp_verify_nonce( $_POST['wpem_guests_group_nonce'], 'save_meta_data' ) ) return;
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;
            if ( $post->post_type !== 'event_guests' ) return;

            do_action( 'event_guests_save_event_guests', $post_id, $post );
        }

        /**
         * Save guest list Meta
         *
         * @param mixed $post_id
         * @param mixed $post
         */
        public function save_guest_lists_group_data( $post_id, $post ) {
            global $wpdb;

            update_post_meta( $post_id, '_guests_group', sanitize_text_field( $_POST['_guests_group'] ) );

            do_action('wpem_create_event_guests_meta_update_end', $post_id);

            foreach ( get_event_guests_form_fields() as $key => $field ) 
            {
                $type = ! empty( $field['type'] ) ? $field['type'] : '';

                switch ( $type ) {
                    case 'textarea' :
                        update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
                    break;
                    case 'checkbox' :
                        if ( isset( $_POST[ $key ] ) ) {
                            update_post_meta( $post_id, $key, 1 );
                        } else {
                            update_post_meta( $post_id, $key, 0 );
                        }
                    break;
                    default :
                        if ( ! isset( $_POST[ $key ] ) ) {
                            continue 2;
                        } elseif ( is_array( $_POST[ $key ] ) ) {
                            update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
                        } else {
                            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
                        }
                    break;
                }
            }
            do_action('wpem_create_event_guests_meta_update_end', $post_id);
        }

    /**
     * event_guests_columns function.
     *
     * @access public
     * @param $columns
     * @return 
     * @since 3.1.16
     */
    public function event_guests_columns( $columns ) {

        $columns = array_slice($columns, 0, 2, true) + array('guest_name' => __('Guest Name', 'wp-event-manager-guests')) + array_slice($columns, 2, count($columns)-2, true);
        $columns = array_slice($columns, 0, 3, true) + array('event_id' => __('Event', 'wp-event-manager-guests')) + array_slice($columns, 3, count($columns)-3, true);
        $columns = array_slice($columns, 0, 4, true) + array('group_name' => __('Group Name', 'wp-event-manager-guests')) + array_slice($columns, 4, count($columns)-4, true);
        $columns = array_slice($columns, 0, 5, true) + array('check_in' => __('Check In', 'wp-event-manager-guests')) + array_slice($columns, 5, count($columns)-5, true);

        if(isset($columns['title']))
            unset($columns['title']);

        return $columns;
    }

    /**
     * organizer_columns_data function.
     *
     * @access public
     * @param $column, $post_id
     * @return 
     * @since 3.1.16
     */
    public function event_guests_columns_data( $column, $post_id ) {

        wp_enqueue_script( 'jquery-tiptip' );
        wp_enqueue_script( 'wpem-guests-admin' );

        $post = get_post($post_id);
        switch ( $column ) {
            case 'guest_name' :
                $guest = get_post( $post_id );
                if ( $guest && $guest->post_type === 'event_guests' ) {
                    echo '<a href="' . get_edit_post_link( $guest->ID ) . '" class="tips" data-tip="' . sprintf( __( 'Guest ID: %d', 'wp-event-manager-guests' ), $guest->ID ) . '" ><b>' . $guest->post_title . '<b></a>';
                } else {
                    echo '<span class="na">&ndash;</span>';
                }
                break;

            case 'event_id' :
                $event = get_post( $post->post_parent );
                if ( $event && $event->post_type === 'event_listing' && $event->post_status === 'publish' ) {
                    echo '<a href="' . get_edit_post_link( $event->ID ) . '" class="tips" data-tip="' . sprintf( __( 'Event ID: %d', 'wp-event-manager-guests' ), $event->ID ) . '" >' . $event->post_title . '</a>';
                } else {
                    echo '<span class="na">&ndash;</span>';
                }
                break;

            case 'group_name' :
                $selected_group = get_post_meta($post_id, '_guests_group', true);
                if( !empty($selected_group) )
                {
                    $group = get_event_guests_group($selected_group);

                    if(!empty($group))
                    {
                        echo '<a href="'. admin_url('edit.php?post_type=event_guests&page=event-guests-groups&edit=') . $group->id . '" class="tips" data-tip="' . sprintf( __( 'Group ID: %d', 'wp-event-manager-guests' ), $group->id ) . '" >' . $group->group_name . '</a>';
                    }
                    else
                    {
                        echo " - ";
                    }                    
                }
                else
                {
                    echo " - ";
                }
                break;

            case 'check_in' :
                $check_in = get_post_meta( $post->ID , '_check_in',true );  
                if(isset($check_in) && $check_in == true ){
                      $checkin_hidden =   'hidden';
                      $undo_hidden = '';
                }
                else{
                    $checkin_hidden = '';
                    $undo_hidden = 'hidden';
                }
                echo "<span class='".$checkin_hidden."'><a class='button-secondary guest_checkin' data-value='1' data-source='web' data-post-id='".$post->ID."'>".__('Check in','wp-event-manager-guests')."</a></span>";

                echo "<span class='".$undo_hidden."'><a class='guest_uncheckin'  data-value='0' data-source='' data-post-id='".$post->ID."' href='#'>".__('Undo Check in','wp-event-manager-guests')."</a></span>";
                break;
        }
    }

    }

    new WPEM_Guests_Writepanels();
}
