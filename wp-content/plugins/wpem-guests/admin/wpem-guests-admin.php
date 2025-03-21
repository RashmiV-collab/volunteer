<?php
if (!defined('ABSPATH'))
{
    exit;
}

/**
 * WPEM_Guests_Admin class.
 */
class WPEM_Guests_Admin {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        include( 'wpem-guests-writepanels.php' );
        include( 'wpem-guests-form-editor.php' );
        include( 'wpem-guests-group-table.php' );
        include( 'wpem-add-guests-group.php' );


        add_action('admin_menu', array($this, 'admin_menu'), 12);

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );

        add_filter( 'request', array( $this, 'request' ) );
        add_filter( 'pre_get_posts', array( $this, 'extend_admin_search' ) );

        add_filter( 'event_manager_google_recaptcha_settings', array($this, 'google_recaptcha_settings') );
    }

    /**
     * admin_menu function.
     *
     * @access public
     * @return void
     */
    public function admin_menu()
    {

        add_submenu_page('edit.php?post_type=event_guests', __('All Groups', 'wpem-guests-groups'), __('All Groups', 'wp-event-manager-guests'), 'manage_options', 'event-guests-groups', array($this, 'output'));

        add_submenu_page('edit.php?post_type=event_guests', __('Add Group', 'wpem-guests-groups'), __('Add Group', 'wp-event-manager-guests'), 'manage_options', 'add-group', array($this, 'output'));
    }

    /**
     * admin_enqueue_scripts function.
     *
     * @access public
     * @return void
     */
    public function admin_enqueue_scripts()
    {
        wp_register_style( 'wpem-guests-admin-css', WPEM_GUESTS_PLUGIN_URL . '/assets/css/admin.css', '', WPEM_GUESTS_VERSION );

        wp_register_script( 'wpem-guests-admin', WPEM_GUESTS_PLUGIN_URL . '/assets/js/admin-guests.min.js', array('jquery'), WPEM_GUESTS_VERSION, true);
        wp_localize_script( 'wpem-guests-admin', 'wpem_guests_admin', array( 
                                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                                'wpem_guests_security'  => wp_create_nonce( "_nonce_wpem_guests_security" ),

                                'i18n_confirm_group_delete' => __( 'Are you sure you want to delete this group? If ok, the guests also will be deleted of this group.', 'wp-event-manager-guests' ),
                            )
                        );
    }

    public function output()
    {
        wp_enqueue_script( 'wpem-guests-admin' );
        wp_enqueue_style('wpem-guests-admin-css');

        if ($_GET['page'] == 'add-group')
        {
            ?>
            <div class="wrap">
                <h2><?php _e( 'Add Group', 'wp-event-manager-guests' ); ?></h2>
                <form id="group-add-form" method="post">
                    <?php
                    $add_guests_group = new WPEM_Add_Guests_Group();
                    $add_guests_group->form();
                    ?>
                    <?php wp_nonce_field( 'add_guests_group', 'wpem_guests_group_nonce' ); ?>
                </form>
            </div>
            <?php
        }
        elseif ($_GET['page'] == 'event-guests-groups' && isset($_GET['edit']) && !empty($_GET['edit']) )
        {
            ?>
            <div class="wrap">
                <h2><?php _e( 'Edit Group', 'wp-event-manager-guests' ); ?></h2>
                <form id="group-add-form" method="post">
                    <?php
                    $add_guests_group = new WPEM_Add_Guests_Group();
                    $add_guests_group->form();
                    ?>
                    <?php wp_nonce_field( 'add_guests_group', 'wpem_guests_group_nonce' ); ?>
                </form>
            </div>
            <?php
        }
        else
        {
            if ($_GET['page'] == 'event-guests-groups' && isset($_GET['delete']) && !empty($_GET['delete']) )
            {
                delete_event_guests_group($_GET['delete']);

                echo '<div class="updated"><p>' . __( 'Group deleted.', 'wp-event-manager-guests' ) . '</p></div>';
            }

            //Prepare Table of elements
            $guest_list_group_table = new WPEM_Guests_Group_Table();
            $guest_list_group_table->prepare_items();
            ?>
            <div class="wrap">
                <h2><?php _e( 'Groups', 'wp-event-manager-guests' ); ?> <a href="<?php echo admin_url( 'edit.php?post_type=event_guests&page=add-group' ); ?>" class="add-new-h2"><?php _e( 'Add Group', 'wp-event-manager-guests' ); ?></a></h2>

                <form id="group-management" method="post">
                    <input type="hidden" name="page" value="pending-orders" />
                    <?php
                    $search_group_name = '';
                    if(isset($_REQUEST['search_group_name']) && $_REQUEST['search_group_name'] != '')
                    {
                        $search_group_name = $_REQUEST['search_group_name'];
                    }
                    ?>
                    <div class="group-management-action">
                    <input type="text" name="search_group_name" value="<?php echo $search_group_name; ?>" placeholder="<?php _e( 'Find by Group Name', 'wp-event-manager-guests' ); ?>" style="width: 20%;" />

                    <?php 
                    $args = [
                        'post_type'   => 'event_listing',
                        'post_status' => 'publish',
                        'posts_per_page'    => -1,
                    ];

                    $events = get_posts($args);
                    if( !empty($events) )
                    {
                        $search_event_name = '';
                        if(isset($_REQUEST['search_event_name']) && $_REQUEST['search_event_name'] != '')
                        {
                            $search_event_name = $_REQUEST['search_event_name'];
                        }

                        ?>
                         <select id="dropdown_event_listings" name="search_event_name">
                            <option value=""><?php _e( 'Group for all events', 'wp-event-manager-guests' ) ?></option>
                        <?php

                        foreach ($events as $event) 
                        {
                            echo '<option value="'. $event->ID .'"  '. selected($search_event_name, $event->ID) .'>'. $event->post_title .'</option>';
                        }
                    }

                    echo '<input type="submit" id="doaction" class="button action" value="'. __( 'Filter', 'wp-event-manager-guests' ). '"> </div>';                    
                   
                    $guest_list_group_table->display();
                    ?>
                    
                </form>
            </div>
            <?php
        }
    }

    /**
     * Add screen ids to JM
     * @param  array $ids
     * @return array
     */
    public function screen_ids($ids)
    {
        $ids[] = 'edit-event_guests';
        $ids[] = 'guest_list';
        return $ids;
    }

    /**
     * enter_title_here function.
     *
     * @access public
     * @return void
     */
    public function enter_title_here($text, $post)
    {
        if ($post->post_type == 'event_guests')
        {
            return __('Attendee name', 'wp-event-manager-guests');
        }
        return $text;
    }

    /**
     * Filter registrations
     */
    public function restrict_manage_posts() {
        global $typenow, $wp_query, $wpdb;

        if ( 'event_guests' != $typenow ) {
            return;
        }

        // events
        ?>
        <select id="dropdown_event_listing" name="_event_listing">
            <option value=""><?php _e( 'Guest for all events', 'wp-event-manager-guests' ) ?></option>
            <?php
                $events_with_guesta = $wpdb->get_col( "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'event_guests';" );
                $current = isset( $_GET['_event_listing'] ) ? $_GET['_event_listing'] : 0;
                foreach ( $events_with_guesta as $event_id ) {
                    if ( ( $title = get_the_title( $event_id ) ) && $event_id ) {
                        echo '<option value="' . $event_id . '" ' . selected( $current, $event_id, false ) . '">' . $title . '</option>';
                    }
                }
            ?>
        </select>
        <select id="dropdown_guest_lists_group" name="_guests_group">
            <option value=""><?php _e( 'Select Group', 'wp-event-manager-guests' ) ?></option>
            <?php
            $current_event = isset( $_GET['_event_listing'] ) ? $_GET['_event_listing'] : 0;
            $current_group = isset( $_GET['_guests_group'] ) ? $_GET['_guests_group'] : 0;
            $groups = get_event_guests_group( '', '', $current_event );

            if(!empty($groups))
            {
                foreach ( $groups as $group ) 
                {
                    echo '<option value="' . $group->id . '" ' . selected( $current_group, $group->id, false ) . '">' . $group->group_name . '</option>';
                }    
            }            
            ?>
        </select>
        <?php
    }

    /**
     * modify what registrations are shown
     */
    public function request( $vars ) {
        global $typenow, $wp_query;

        if ( $typenow == 'event_guests' && isset( $_GET['_event_listing'] ) && $_GET['_event_listing'] > 0 ) {
            $vars['post_parent'] = (int) $_GET['_event_listing'];
        }

        // Sorting
        if ( isset( $vars['orderby'] ) ) {
            if ( 'rating' == $vars['orderby'] ) {
                $vars = array_merge( $vars, array(
                    'meta_key' => '_rating',
                    'orderby'  => 'meta_value_num'
                ) );
            }
        }

        return $vars;
    }

    public function extend_admin_search( $query ) {
        global $typenow, $wp_query;

        if ( $typenow == 'event_guests' && isset( $_GET['_guests_group'] ) && $_GET['_guests_group'] > 0 ) 
        {
            $query->set( 'meta_key', '_guests_group' );
            $query->set( 'meta_value', $_GET['_guests_group'] );
        }

        return $query;
    }

    public function google_recaptcha_settings($settings) {

        $settings[1][] = array(
                    'name'       => 'enable_event_manager_google_recaptcha_submit_guest_lists_group_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Submit Group Form', 'wp-event-manager-guests' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Submit Group Form.', 'wp-event-manager-guests' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                );

        $settings[1][] = array(
                    'name'       => 'enable_event_manager_google_recaptcha_submit_guest_lists_guest_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Submit Guest List Form', 'wp-event-manager-guests' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Submit Guest List Form.', 'wp-event-manager-guests' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                );

        return $settings;
    }

}

new WPEM_Guests_Admin();
