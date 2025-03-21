<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WPEM_Guests_Dashboard class.
 */
class WPEM_Guests_Dashboard {

	private $fields     = array();
	private $error      = '';
	private $group_dashboard_message = '';
	private $guest_lists_dashboard_message = '';
	private static $secret_dir = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'edit_handler' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'sanitize_file_name_chars', array( $this, 'sanitize_file_name_chars' ) );
		add_action( 'wp_loaded', array( $this, 'guest_list_form_handler' ) );

		add_filter( 'event_manager_event_dashboard_columns', array( $this, 'add_group_columns' ), 12 );
		add_action( 'event_manager_event_dashboard_column_groups', array( $this, 'group_column' ) );
		add_action( 'event_manager_event_dashboard_content_show_groups', array( $this, 'show_groups' ) );

		add_action( 'event_manager_event_dashboard_content_add_group', array( $this, 'add_group' ) );
		add_action( 'event_manager_event_dashboard_content_edit_group', array( $this, 'edit_group' ) );

		add_action( 'event_manager_event_dashboard_content_show_guest_lists', array( $this, 'show_guest_lists' ) );
		add_action( 'event_manager_event_dashboard_content_add_guest', array( $this, 'add_guest' ) );
		add_action( 'event_manager_event_dashboard_content_edit_guest', array( $this, 'edit_guest' ) );

		add_filter( 'wpem_dashboard_menu', array($this,'wpem_dashboard_menu_add') );

		self::$secret_dir = uniqid();
	}

	/**
	 * add dashboard menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpem_dashboard_menu_add($menus) {
		//add style before loading content
		wp_enqueue_style( 'wpem-guest-frontend-style' );

		$menus['guest_lists'] = [
						'title' => __('Guest Lists', 'wp-event-manager-guests'),
						'icon' => 'wpem-icon-users',
						'submenu' => [
							'show_groups' => [
								'title' => __('Groups', 'wp-event-manager-guests'),
								'query_arg' => ['action' => 'show_groups'],
							],
							'add_group' => [
								'title' => __('Add Group', 'wp-event-manager-guests'),
								'query_arg' => ['action' => 'add_group'],
							],
							'show_guest_lists' => [
								'title' => __('Guests', 'wp-event-manager-guests'),
								'query_arg' => ['action' => 'show_guest_lists'],
							],
							'add_guest' => [
								'title' => __('Add Guest', 'wp-event-manager-guests'),
								'query_arg' => ['action' => 'add_guest'],
							],
						]
					];
		return $menus;
	}

	/**
	 * add_group_columns function.
	 *
	 * @access public
	 * @param $columns
	 * @return void
	 * @since 1.0.0
	 */
	public function add_group_columns( $columns )
	{
		$columns['groups'] = __( 'Groups', 'wp-event-manager-guests' );
		return $columns;
	}

	/**
	 * group_column function.
	 *
	 * @access public
	 * @param $event
	 * @return void
	 * @since 1.0.0
	 */
	public function group_column( $event )
	{
		global $post;

		$groups = get_event_guests_group('', '', $event->ID);

		echo ( !empty($groups) ) ? '<a href="' . add_query_arg( array( 'action' => 'show_groups', 'event_id' => $event->ID ), get_permalink( $post->ID ) ) . '">' . count($groups) . '</a>' : '&ndash;';
	}

	/**
	 * show_groups function.
	 *
	 * @access public
	 * @param $event
	 * @return void
	 * @since 1.0.0
	 */
	public function show_groups( $event )
	{
		global $post;

		$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';
		echo $this->group_dashboard_message;
		if(isset($_GET['deleted']) && $_GET['deleted'] == true ){
			 echo '<div class="wpem-alert wpem-alert-danger">' . __( 'Group deleted.', 'wp-event-manager-guests' ) . '</div>';
		}
		echo do_shortcode('[guests_groups event_id="'.$event_id.'"]');
	}

	/**
	 * add_group function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function add_group($event)
	{
		$user_id = get_current_user_id();
		$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';

		wp_enqueue_style( 'chosen' );
		wp_enqueue_style( 'wpem-guests-frontend' );

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'wpem-guests-dashboard' );

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);



		get_event_manager_template(
			'add-group.php',
			array(
				'user_id' => $user_id,
				'event_id' => $event_id,
				'events'  => $events,
				'group_dashboard_message' => $this->group_dashboard_message,
			),
			'wp-event-manager-guests',
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * edit_group function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function edit_group($event)
	{
		$user_id = get_current_user_id();
		$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';
		$group = get_event_guests_group($group_id);

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);

		wp_enqueue_style( 'chosen' );
		//wp_enqueue_style( 'wpem-guests-frontend' );

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'wpem-guests-dashboard' );

		get_event_manager_template(
			'edit-group.php',
			array(
				'user_id' => $user_id,
				'group'	  => $group,
				'events'  => $events,
				'group_dashboard_message' => $this->group_dashboard_message,
			),
			'wp-event-manager-guests',
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * show_guest_lists function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function show_guest_lists()
	{
		$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';
		$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';
		if(isset($_GET['deleted']) && $_GET['deleted'] == true ){
			echo '<div class="wpem-alert wpem-alert-danger">' . __( 'Guest
			 deleted.', 'wp-event-manager-guests' ) . '</div>';
	   }
		echo do_shortcode('[guest_lists_guests event_id="'.$event_id.'" group_id="'.$group_id.'"]');
	}

	/**
	 * add_guest function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function add_guest($event) {
		$user_id = get_current_user_id();
		$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';
		$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';

		$groups = get_event_guests_group('', $user_id, $event_id);
		$group = get_event_guests_group($group_id);
		$fields = get_event_guests_form_fields();

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);

		wp_enqueue_style( 'chosen' );
		wp_enqueue_style( 'wpem-guests-frontend' );

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'wpem-guests-dashboard' );

		get_event_manager_template(
			'add-guest-lists.php',
			array(
				'group_id' => $group_id,
				'user_id' => $user_id,
				'event_id' => $event_id,
				'groups' => $groups,
				'group' => $group,
				'fields' => $fields,
				'events'  => $events,
				'guest_lists_dashboard_message' => $this->guest_lists_dashboard_message,
			),
			'wpem-guests',
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * edit_guest function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function edit_guest($event) {
		$user_id = get_current_user_id();
		$guest_id = isset($_REQUEST['guest_id']) ? $_REQUEST['guest_id'] : '';
		$group_id = get_post_meta($guest_id, '_guests_group', true);
		$guest = get_post($guest_id);
		$event_id = $guest->post_parent;

		$group = get_event_guests_group($group_id);
		$fields = get_event_guests_form_fields();
		$groups = get_event_guests_group('', $user_id, $group->event_id);

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);

		wp_enqueue_style( 'chosen' );
		wp_enqueue_style( 'wpem-guests-frontend' );

		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'wpem-guests-dashboard' );

		get_event_manager_template(
			'edit-guest-lists.php',
			array(
				'group_id' 	=> $group_id,
				'user_id' 	=> $user_id,
				'guest_id' 	=> $guest_id,
				'event_id' 	=> $event_id,
				'guest' 	=> $guest,
				'groups' 	=> $groups,
				'group' 	=> $group,
				'fields' 	=> $fields,
				'events'  => $events,
				'guest_lists_dashboard_message' => $this->guest_lists_dashboard_message,
			),
			'wp-event-manager-guests',
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * edit_handler function.
	 *
	 * @access public
	 * @return void
	 */
	public function edit_handler() {
		if ( ! empty( $_POST['wp_event_manager_add_group'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_add_group' ) )
		{
			$group_name        = isset($_POST['group_name']) ? trim( $_POST['group_name']) : '';
	        $group_description = isset($_POST['group_description']) ? $_POST['group_description'] : '';
	        $group_fields      = isset($_POST['group_fields']) ? $_POST['group_fields'] : '';
	        $event_id      	   = isset($_POST['event_id']) ? $_POST['event_id'] : '';
	        $id      	   	   = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';

	        try
	        {
	            // Validate
	            if (empty($group_name))
	            {
	                //$this->error = __('Group name is a required field.', 'wp-event-manager-guests');
	                throw new Exception( __('Group name is a required field.', 'wp-event-manager-guests') );
	            }

	            $data = array(
	            	'id'        		=> $id,
	                'user_id'           => get_current_user_id(),
	                'event_id'        	=> $event_id,
	                'group_name'        => $group_name,
	                'group_description' => $group_description,
	                'group_fields'      => $group_fields,
	            );

	            // Validation hook
				$data = apply_filters( 'guest_list_group_form_validate_fields', $data );

	            if (WPEM_Guests_Post_Types::save_guest_lists_group($data))
	            {
	            	$this->group_dashboard_message = '<div class="wpem-alert wpem-alert-success">' . __( 'Your Group is saved successfully.', 'wp-event-manager-guests' ) . '</div>';
	            }
	            else
	            {
	            	throw new Exception(  __('Could not create the group.', 'wp-event-manager-guests') );
	            }
	        }
	        catch (Exception $e)
	        {
	            $this->error = $e->getMessage();

				add_action( 'event_manager_guests_group_start', array( $this, 'group_form_errors' ) );
	        }
		}
		elseif ( isset($_GET['action']) && $_GET['action'] == 'delete_group' && wp_verify_nonce( $_GET['_wpnonce'], 'event_manager_group_actions' ) )
		{
			$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';
			$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';
			$delete = delete_event_guests_group($group_id);

			if($delete)
			{


				$event_dashboard = get_option('event_manager_event_dashboard_page_id');
				$action_url = add_query_arg ( array (
							'action' => 'show_groups',
							'deleted' => true,
							), get_permalink($event_dashboard) );

				wp_redirect( $action_url );
				exit;
			}
		}
		elseif ( isset($_GET['action']) && $_GET['action'] == 'delete_guest' && wp_verify_nonce( $_GET['_wpnonce'], 'event_manager_guests_actions' ) )
		{
			$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';
			$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';
			$guest_id = isset($_REQUEST['guest_id']) ? $_REQUEST['guest_id'] : '';

			$delete = wp_trash_post($guest_id);

			if($delete)
			
			{
				$this->guest_lists_dashboard_message = '<div class="wpem-alert wpem-alert-danger">' . __( 'Guest deleted', 'wp-event-manager-guests' ) . '</div>';

				$event_dashboard = get_option('event_manager_event_dashboard_page_id');
				$action_url = add_query_arg ( array (
							'action' => 'show_guest_lists',
							'event_id' => $event_id,
							'group_id' => $group_id,
							'deleted' => true
							), get_permalink($event_dashboard) );

				wp_redirect( $action_url );
				exit;
			}
		}
		elseif(! empty( $_POST['wp_event_manager_add_guest'] ) && isset($_GET['action']) && $_GET['action'] == 'add_guest' && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_add_guest' ) ){
			try {
				$fields = $this->get_fields();
				$values = array();
				$event_id = absint( $_POST['event_id'] );
				$event    = get_post( $event_id );
				$group_id = absint( $_POST['guest_lists_group'] );
				$group = get_event_guests_group($group_id);
				$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : [];
				$meta   = array();

				if ( empty( $event_id ) || ! $event || 'event_listing' !== $event->post_type ) {
					throw new Exception( __( 'Invalid event.', 'wp-event-manager-guests' ) );
				}

				// Validate posted fields
				foreach ( $fields as $key => $field ) {

					if(!in_array($key, $group_fields)){
						$field['required']=false;
					}

					$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );

					switch( $field['type'] ) {
						case "file" :
							$values[ $key ] = $this->upload_file( $key, $field );

							if ( is_wp_error( $values[ $key ] ) ) {
								throw new Exception( $field['label'] . ': ' . $values[ $key ]->get_error_message() );
							}
						break;
						default :
							$values[ $key ] = isset( $_POST[ $key ] ) ? $this->sanitize_text_field_with_linebreaks( $_POST[ $key ] ) : '';
						break;
					}

					// Validate required
					if ( $field['required'] && empty( $values[ $key ] ) ) {
						throw new Exception( sprintf( __( ' "%s" is a required field.', 'wp-event-manager-guests' ), $field['label'] ) );
					}

					// Extra validation rules
					if ( ! empty( $field['rules'] ) && ! empty( $values[ $key ] ) ) {
						foreach( $field['rules'] as $rule ) {
							switch( $rule ) {
								case 'email' :
								case 'from_email' :
									if ( ! is_email( $values[ $key ] ) ) {
										throw new Exception( $field['label'] . ': ' . __( 'Please provide a valid email address', 'wp-event-manager-guests' ) );
									}
								break;
								case 'numeric' :
									if ( ! is_numeric( $values[ $key ] ) ) {
										throw new Exception( $field['label'] . ': ' . __( 'Please enter a number', 'wp-event-manager-guests' ) );
									}
								break;
							}
						}
					}
				}

				// Validation hook
				$valid = apply_filters( 'guest_list_form_validate_fields', true, $fields, $values );

				if ( is_wp_error( $valid ) ) {
					throw new Exception( $valid->get_error_message() );
				}

				// Prepare meta data to save
				$from_name                = '';
				$from_email               = '';
				$meta['_secret_dir']      = self::$secret_dir;
				$meta['_attachment']      = array();
				$meta['_attachment_file'] = array();
				$meta['guest_lists_group'] = $_POST['guest_lists_group'];
				$guest_list_fields= array();

				foreach ( $fields as $key => $field ) {
					if ( empty( $values[ $key ] ) ) {
						continue;
					}

					$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );

					if ( in_array( 'from_name', $field['rules'] ) ) {
						$from_name = $values[ $key ];
					}

					if ( in_array( 'from_email', $field['rules'] ) ) {
						$from_email = $values[ $key ];
					}
					$guest_list_fields[$key]=$values[ $key ];

					if ( 'file' === $field['type'] ) {
						if ( ! empty( $values[ $key ] ) ) {
							$index = 1;
							foreach ( $values[ $key ] as $attachment ) {
								if ( ! is_wp_error( $attachment ) ) {
									if ( in_array( 'attachment', $field['rules'] ) ) {
										$meta['_attachment'][]      = $attachment->url;
										$meta['_attachment_file'][] = $attachment->file;
									} else {
										$meta[ $key. ' ' . $index ] = $attachment->url;
									}
								}
								$index ++;
							}
						}
					}
					elseif ( 'checkbox' === $field['type'] ) {
						$meta[ $key ] = $values[ $key ] ? __( 'Yes', 'wp-event-manager-guests' ) : __( 'No', 'wp-event-manager-guests' );
					}
					elseif ( is_array( $values[ $key ] ) ) {
						$meta[ $key ] = implode( ', ', $values[ $key ] );
					}
					else {
						$meta[ $key ] = $values[ $key ];
					}
				}
				//set rule value in meta so we can use those meta velue while sending email in notification
				$meta['from_name']  = $from_name;
				$meta['from_email'] = $from_email;
				$meta                = apply_filters( 'event_guests_form_posted_meta', $meta, $values );

				if( isset($_REQUEST['guest_id']) && !empty($_REQUEST['guest_id']) )
				{
					// Edit guest list
					if ( ! $guest_list_id = create_event_guests( $event_id, $guest_list_fields, $meta, true ) ) {
						throw new Exception( __( 'Could not create event guest list.', 'wp-event-manager-guests' ) );
					}
				}
				else
				{
					// Create guest list
					if ( ! $guest_list_id = create_event_guests( $event_id, $guest_list_fields, $meta ) ) {
						throw new Exception( __( 'Could not create event guest list.', 'wp-event-manager-guests' ) );
					}
				}

				// Message to display
				add_action( 'event_manager_guests_guest_start', array( $this, 'guest_list_form_success' ) );

				// Trigger action
				do_action( 'new_event_guest_list', $guest_list_id, $event_id );

			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				add_action( 'event_manager_guests_guest_start', array( $this, 'guest_list_form_errors' ) );
			}

		}
		elseif(! empty( $_POST['wp_event_manager_send_guest'] ) && isset($_GET['action']) && $_GET['action'] == 'edit_guest' && wp_verify_nonce( $_POST['_wpnonce'], 'event_manager_send_guest' ) ){
			try {
				$fields = $this->get_fields();
				$values = array();
				$event_id = absint( $_POST['event_id'] );
				$event    = get_post( $event_id );
				$group_id = absint( $_POST['guest_lists_group'] );
				$group = get_event_guests_group($group_id);
				$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : [];
				$meta   = array();

				if ( empty( $event_id ) || ! $event || 'event_listing' !== $event->post_type ) {
					throw new Exception( __( 'Invalid event.', 'wp-event-manager-guests' ) );
				}

				// Validate posted fields
				foreach ( $fields as $key => $field ) {

					if(!in_array($key, $group_fields)){
						$field['required']=false;
					}

					$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );

					switch( $field['type'] ) {
						case "file" :
							$values[ $key ] = $this->upload_file( $key, $field );

							if ( is_wp_error( $values[ $key ] ) ) {
								throw new Exception( $field['label'] . ': ' . $values[ $key ]->get_error_message() );
							}
						break;
						default :
							$values[ $key ] = isset( $_POST[ $key ] ) ? $this->sanitize_text_field_with_linebreaks( $_POST[ $key ] ) : '';
						break;
					}

					// Validate required
					if ( $field['required'] && empty( $values[ $key ] ) ) {
						throw new Exception( sprintf( __( ' "%s" is a required field', 'wp-event-manager-guests' ), $field['label'] ) );
					}

					// Extra validation rules
					if ( ! empty( $field['rules'] ) && ! empty( $values[ $key ] ) ) {
						foreach( $field['rules'] as $rule ) {
							switch( $rule ) {
								case 'email' :
								case 'from_email' :
									if ( ! is_email( $values[ $key ] ) ) {
										throw new Exception( $field['label'] . ': ' . __( 'Please provide a valid email address', 'wp-event-manager-guests' ) );
									}
								break;
								case 'numeric' :
									if ( ! is_numeric( $values[ $key ] ) ) {
										throw new Exception( $field['label'] . ': ' . __( 'Please enter a number', 'wp-event-manager-guests' ) );
									}
								break;
							}
						}
					}
				}

				// Validation hook
				$valid = apply_filters( 'guest_list_form_validate_fields', true, $fields, $values );

				if ( is_wp_error( $valid ) ) {
					throw new Exception( $valid->get_error_message() );
				}

				// Prepare meta data to save
				$from_name                  = '';
				$from_email                 = '';
				$meta['_secret_dir']        = self::$secret_dir;
				$meta['_attachment']        = array();
				$meta['_attachment_file']   = array();
				$meta['guest_lists_group']  = $_POST['guest_lists_group'];
				$guest_list_fields= array();

				foreach ( $fields as $key => $field ) {
					if ( empty( $values[ $key ] ) ) {
						continue;
					}

					$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );

					if ( in_array( 'from_name', $field['rules'] ) ) {
						$from_name = $values[ $key ];
					}

					if ( in_array( 'from_email', $field['rules'] ) ) {
						$from_email = $values[ $key ];
					}
					$guest_list_fields[$key]=$values[ $key ];

					if ( 'file' === $field['type'] ) {
						if ( ! empty( $values[ $key ] ) ) {
							$index = 1;
							foreach ( $values[ $key ] as $attachment ) {
								if ( ! is_wp_error( $attachment ) ) {
									if ( in_array( 'attachment', $field['rules'] ) ) {
										$meta['_attachment'][]      = $attachment->url;
										$meta['_attachment_file'][] = $attachment->file;
									} else {
										$meta[ $key. ' ' . $index ] = $attachment->url;
									}
								}
								$index ++;
							}
						}
					}
					elseif ( 'checkbox' === $field['type'] ) {
						$meta[ $key ] = $values[ $key ] ? __( 'Yes', 'wp-event-manager-guests' ) : __( 'No', 'wp-event-manager-guests' );
					}
					elseif ( is_array( $values[ $key ] ) ) {
						$meta[ $key ] = implode( ', ', $values[ $key ] );
					}
					else {
						$meta[ $key ] = $values[ $key ];
					}
				}
				//set rule value in meta so we can use those meta velue while sending email in notification
				$meta['from_name']          = $from_name;
				$meta['from_email']         = $from_email;
				$meta                       = apply_filters( 'event_guests_form_posted_meta', $meta, $values );

				if( isset($_REQUEST['guest_id']) && !empty($_REQUEST['guest_id']) ) {
					// Edit guest list
					if ( ! $guest_list_id = create_event_guests( $event_id, $guest_list_fields, $meta, true ) ) {
						throw new Exception( __( 'Could not create event guest list.', 'wp-event-manager-guests' ) );
					}
				}
				else {
					// Create guest list
					if ( ! $guest_list_id = create_event_guests( $event_id, $guest_list_fields, $meta ) ) {
						throw new Exception( __( 'Could not create event guest list.', 'wp-event-manager-guests' ) );
					}
				}

				// Message to display
				add_action( 'event_manager_guests_guest_start', array( $this, 'guest_list_form_success' ) );

				// Trigger action
				do_action( 'new_event_guest_list', $guest_list_id, $event_id );

			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				add_action( 'event_manager_guests_guest_start', array( $this, 'guest_list_form_errors' ) );
			}

		}
	}

	/**
	 * Send the guest list email if posted
	 * @throws Exception
	 */
	public function guest_list_form_handler() {
		if ($_POST && isset($_POST["event_id"]) && $_POST["event_id"] !== 0) {
			// UPDATE GUEST
			if (isset($_POST["guest_id"])) {
				$fields         = $this->get_fields();
				$values         = array();
				$event_id       = absint( $_POST['event_id'] );
				$event          = get_post($event_id);
				$group_id       = absint($_POST['guest_lists_group']);
				$group          = get_event_guests_group($group_id);
				$group_fields   = isset($group->group_fields) ? json_decode($group->group_fields, true) : [];
				$meta           = array();
				$_REQUEST       = $_POST;

				if (empty($event_id) || ! $event || $event->post_type !== 'event_listing') {
					return new Exception( __( 'Invalid event.', 'wp-event-manager-guests' ) );
				}

				// Prepare meta data to save
				$from_name                  = '';
				$from_email                 = '';
				$meta['_secret_dir']        = self::$secret_dir;
				$meta['_attachment']        = array();
				$meta['_attachment_file']   = array();
				$meta['guest_lists_group']  = $group_id;
				$guest_list_fields          = array();

				foreach ($fields as $key => $field) {
					if (empty($values[$key])) {
						continue;
					}
					$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );
					if ( in_array( 'from_name', $field['rules'] ) ) {
						$from_name = $values[ $key ];
					}

					if ( in_array( 'from_email', $field['rules'] ) ) {
						$from_email = $values[ $key ];
					}
					$guest_list_fields[$key]=$values[ $key ];

					if ( 'file' === $field['type'] ) {
						if ( ! empty( $values[ $key ] ) ) {
							$index = 1;
							foreach ( $values[ $key ] as $attachment ) {
								if ( ! is_wp_error( $attachment ) ) {
									if ( in_array( 'attachment', $field['rules'] ) ) {
										$meta['_attachment'][]      = $attachment->url;
										$meta['_attachment_file'][] = $attachment->file;
									} else {
										$meta[ $key. ' ' . $index ] = $attachment->url;
									}
								}
								$index ++;
							}
						}
					}
					elseif ( 'checkbox' === $field['type'] ) {
						$meta[$key] = $values[ $key ] ? __( 'Yes', 'wp-event-manager-guests' ) : __( 'No', 'wp-event-manager-guests' );
					}
					elseif ( is_array( $values[ $key ] ) ) {
						$meta[ $key ] = implode( ', ', $values[ $key ] );
					} else {
						$meta[ $key ] = $values[$key];
					}
				}
				//set rule value in meta so we can use those meta velue while sending email in notification
				$meta['from_name']      = $from_name;
				$meta['from_email']     = $from_email;
				$meta                   = apply_filters( 'event_guests_form_posted_meta', $meta, $values );

				if(isset($_REQUEST['guest_id']) && !empty($_REQUEST['guest_id']) ) {
					// Edit guest list
					if (!$guest_list_id = create_event_guests( $event_id, $guest_list_fields, $meta, true ) ) {
						throw new Exception( __( 'Could not create event guest list.', 'wp-event-manager-guests' ) );
					}
				}
				return true;
			} else {
				// TODO: ADD GUEST
				return true;
			}
		} else {
			return new Exception( __( 'Invalid event.', 'wp-event-manager-guests' ) );
		}
	}

	public function validate_guest_fields($group_fields) {
		$fields = $this->fields;
		// Validate posted fields
		foreach ( $fields as $key => $field ) {
			if(!in_array($key, $group_fields)){
				$field['required'] = false;
			}
			$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );
			switch( $field['type'] ) {
				case "file" :
					$values[ $key ] = $this->upload_file( $key, $field );

					if ( is_wp_error( $values[ $key ] ) ) {
						return new Exception( $field['label'] . ': ' . $values[ $key ]->get_error_message() );
					}
					break;
				default :
					$values[ $key ] = isset( $_POST[ $key ] ) ? $this->sanitize_text_field_with_linebreaks( $_POST[ $key ] ) : '';
					break;
			}
			// Validate required
			if ($field['required'] && empty($values[$key])) {
				return new Exception( sprintf( __( ' "%s" is a required field', 'wp-event-manager-guests' ), $field['label'] ) );
			}
			// Extra validation rules
			if (!empty($field['rules'] ) && !empty($values[$key])) {
				foreach($field['rules'] as $rule) {
					switch( $rule ) {
						case 'email' :
						case 'from_email' :
							if (!is_email($values[$key])) {
								return new Exception( $field['label'] . ': ' . __( 'Please provide a valid email address', 'wp-event-manager-guests' ) );
							}
							break;
						case 'numeric' :
							if (!is_numeric($values[$key])) {
								return new Exception( $field['label'] . ': ' . __( 'Please enter a number', 'wp-event-manager-guests' ) );
							}
							break;
					}
				}
			}
		}
		// Validation hook
		$valid = apply_filters( 'guest_list_form_validate_fields', true, $fields, $values );
		if (is_wp_error($valid)) {
			throw new Exception( $valid->get_error_message() );
		}
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {

		wp_register_style( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css' );
		wp_register_script( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array( 'jquery' ), '1.1.0', true );

		wp_register_style( 'wpem-guests-frontend', WPEM_GUESTS_PLUGIN_URL . '/assets/css/frontend.css' );

		wp_register_script( 'wpem-guests-dashboard', WPEM_GUESTS_PLUGIN_URL . '/assets/js/guests-dashboard.min.js', array( 'jquery' ), WPEM_GUESTS_VERSION, true );

		wp_localize_script( 'wpem-guests-dashboard', 'wpem_guest_lists_dashboard', array(
								'ajax_url' 	 => admin_url( 'admin-ajax.php' ),
								'wpem_guests_security'  => wp_create_nonce( "_nonce_wpem_guests_security" ),

								'i18n_confirm_group_delete' => __( 'Are you sure you want to delete this group? If ok, the guests also will be deleted of this group.', 'wp-event-manager-guests' ),

								'i18n_confirm_guest_lists_delete' => __( 'Are you sure you want to delete this guest?', 'wp-event-manager-guests' ),
							)
						);
	}

	/**
	 * Chars which should be removed from file names
	 */
	public function sanitize_file_name_chars( $chars ) {
		$chars[] = "%";
		$chars[] = "^";
		return $chars;
	}

	/**
	 * Init guest list form
	 */
	public function init() {
		global $event_manager;


	}

	public function get_fields() {
		$this->init_fields();
		return $this->fields;
	}

	/**
	 * Sanitize a text field, but preserve the line breaks! Can handle arrays.
	 * @param  string $input
	 * @return string
	 */
	private function sanitize_text_field_with_linebreaks( $input ) {
		if ( is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$input[ $k ] = $this->sanitize_text_field_with_linebreaks( $v );
			}
			return $input;
		} else {
			return str_replace( '[nl]', "\n", sanitize_text_field( str_replace( "\n", '[nl]', strip_tags( stripslashes( $input ) ) ) ) );
		}
	}

	/**
	 * Init form fields
	 */
	public function init_fields() {
		if ( ! empty( $this->fields ) ) {
			return;
		}

		$current_user = is_user_logged_in() ? wp_get_current_user() : false;
		$this->fields = get_event_guests_form_fields();

		// Handle values
		foreach ( $this->fields as $key => $field ) {
			if ( ! isset( $this->fields[ $key ]['value'] ) ) {
				$this->fields[ $key ]['value'] = '';
			}

			$field['rules'] = array_filter( isset( $field['rules'] ) ? (array) $field['rules'] : array() );

			// Special field type handling
			if ( in_array( 'from_name', $field['rules'] ) ) {
				if ( $current_user ) {
					$this->fields[ $key ]['value'] = $current_user->first_name . ' ' . $current_user->last_name;
				}
			}
			if ( in_array( 'from_email', $field['rules'] ) ) {
				if ( $current_user ) {
					$this->fields[ $key ]['value'] = $current_user->user_email;
				}
			}
			if ( 'select' === $field['type'] && ! $this->fields[ $key ]['required'] ) {
				$this->fields[ $key ]['options'] = array_merge( array( 0 => __( 'Choose an option', 'wp-event-manager-guests' ) ), $this->fields[ $key ]['options'] );
			}


			// Check for already posted values
			//$this->fields[ $key ]['value'] = isset( $_POST[ $key ] ) ? $this->sanitize_text_field_with_linebreaks( $_POST[ $key ] ) : $this->fields[ $key ]['value'];
		}

		uasort( $this->fields, array( $this, 'sort_by_priority' ) );
	}

	/**
	 * Get a field from either event manager
	 */
	public static function get_field_template( $key, $field ) {
		get_event_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ) );
	}

	/**
	 * Disable guest list form if needed
	 */
	public function disable_guest_lists_form( $template, $template_name ) {

	}

	/**
	 * Allow users to register to a event
	 */
	public function guest_list_form() {


	}

	/**
	 * Sort array by priority value
	 */
	private function sort_by_priority( $a, $b ) {
		return $a['priority'] - $b['priority'];
	}

	/**
	 * Upload a file
	 * @return  string or array
	 */
	public function upload_file( $field_key, $field ) {
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			if ( ! empty( $field['allowed_mime_types'] ) ) {
				$allowed_mime_types = $field['allowed_mime_types'];
			} else {
				$allowed_mime_types = get_allowed_mime_types();
			}

			$files           = array();
			$files_to_upload = event_manager_prepare_uploaded_files( $_FILES[ $field_key ] );

			add_filter( 'event_manager_upload_dir', array( $this, 'upload_dir' ), 10, 2 );

			foreach ( $files_to_upload as $file_to_upload ) {
				$uploaded_file = event_manager_upload_file( $file_to_upload, array( 'file_key' => $field_key ) );

				if ( is_wp_error( $uploaded_file ) ) {
					throw new Exception( $uploaded_file->get_error_message() );
				} else {
					if ( ! isset( $uploaded_file->file ) ) {
						$uploaded_file->file = str_replace( site_url(), ABSPATH, $uploaded_file->url );
					}
					$files[] = $uploaded_file;
				}
			}

			remove_filter( 'event_manager_upload_dir', array( $this, 'upload_dir' ), 10, 2 );

			return $files;
		}
	}

	/**
	 * Filter the upload directory
	 */
	public static function upload_dir( $pathdata ) {
		return 'event_guests/' . self::$secret_dir;
	}

	/**
	 * Show errors
	 */
	public function group_form_errors() {
		if ( $this->error ) {
			echo '<p class="event-manager-error event-manager-guest-lists-error wpem-alert wpem-alert-danger">' . esc_html( $this->error ) . '</p>';
		}
	}

	/**
	 * Success message
	 */
	public function guest_list_form_success() {
		get_event_manager_template( 'guests-submitted.php', array(), 'wp-event-manager-guests', WPEM_GUESTS_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Show errors
	 */
	public function guest_list_form_errors() {
		if ( $this->error ) {
			echo '<p class="event-manager-error event-manager-guest-lists-error wpem-alert wpem-alert-danger">' . esc_html( $this->error ) . '</p>';
		}
	}

}

new WPEM_Guests_Dashboard();
