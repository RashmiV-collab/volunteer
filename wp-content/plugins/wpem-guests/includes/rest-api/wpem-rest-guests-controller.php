<?php
/**
 * REST API Guest Lists controller
 *
 * Handles requests to the /events/guest endpoint.
 *
 * @author   WPEM
 * @since    3.1.14
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Guest Lists controller class.
 *
 * @extends WPEM_REST_Controller
 */
class WPEM_REST_Event_Guest_Lists_Controller extends WPEM_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wpem';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'events/(?P<event_id>[\d]+)/groups';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'event_guests';


	/**
	 * Initialize guest list actions.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ), 10 );
		//add tickets details on each event
		add_filter( "wpem_rest_get_event_listing_data",array($this,'wpem_guest_lists_add_data_to_listing'),10,3 );
	}

	/**
	 * Register the routes for event attendees.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base,
				array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_groups' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_group' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
							'event_id'     => array(
								'required'    => true,
								'description' => __( 'Unique identifier for the event.', 'wp-event-manager-guests' ),
								'type'        => 'integer',
							),
							'group_name' => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Name of the group.', 'wp-event-manager-guests' ),
							),
							'group_description' => array(
								'required'    => false,
								'type'        => 'string',
								'description' => __( 'Description of the group.', 'wp-event-manager-guests' ),
							),
							'group_fields' => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Fields of the group.', 'wp-event-manager-guests' ),
							),
							'status' => array(
								'required'    => false,
								'type'        => 'string',
								'description' => __( 'Status of guest list should contain waiting, confirm, concelled', 'wp-event-manager-guests' ),
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/(?P<group_id>[\d]+)', array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wp-event-manager-guests' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_group' ),
					//'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_group' ),
					//'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_group' ),
					//'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'wp-event-manager-guests' ),
						),
					),
				),

				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/(?P<group_id>[\d]+)/guests', array(
					array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_guests' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_guest' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
							'event_id'     => array(
								'required'    => true,
								'description' => __( 'Unique identifier for the event.', 'wp-event-manager-guests' ),
								'type'        => 'integer',
							),
							'guest_name'       => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Name of the guest list.', 'wp-event-manager-guests' ),
							),
							'guest_email' => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Email of the guest list.', 'wp-event-manager-guests' ),
							),
							'status' => array(
								'required'    => false,
								'type'        => 'string',
								'description' => __( 'Status of guest list should contain waiting, confirm, concelled', 'wp-event-manager-guests' ),
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/(?P<group_id>[\d]+)/guests/(?P<guest_id>[\d]+)', array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wp-event-manager-guests' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_guest' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_guest' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_guest' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'wp-event-manager-guests' ),
						),
					),
				),

				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/batch', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/(?P<group_id>[\d]+)/guests/(?P<guest_id>[\d]+)/checkin',array(
				'args'   => array(
					'guest_id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wp-event-manager-guests' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'checkin_guest' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to checkin or undo checkin.', 'wp-event-manager-sell-tickets' ),
						),
						'checkin_source' => array(
							'default'     => 'app',
							'type'        => 'string',
							'description' => __( 'Checkin platform details', 'wp-event-manager-sell-tickets' ),
						),
						'checkin_device_id' => array(
							'default'     => '',
							'type'        => 'string',
							'description' => __( 'Device id', 'wp-event-manager-sell-tickets' ),
						),
						'checkin_device_name' => array(
							'default'     => '',
							'type'        => 'string',
							'description' => __( 'Device name', 'wp-event-manager-sell-tickets' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
				'/' . $this->rest_base . '/(?P<group_id>[\d]+)/guests/(?P<guest_id>[\d]+)/checkout',array(
				'args'   => array(
					'guest_id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wp-event-manager-guests' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'checkout_guest' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to checkout or undo checkout.', 'wp-event-manager-sell-tickets' ),
						),
						'checkout_source' => array(
							'default'     => 'app',
							'type'        => 'string',
							'description' => __( 'Checkout platform details', 'wp-event-manager-sell-tickets' ),
						),
						'checkout_device_id' => array(
							'default'     => '',
							'type'        => 'string',
							'description' => __( 'Device id', 'wp-event-manager-sell-tickets' ),
						),
						'checkout_device_name' => array(
							'default'     => '',
							'type'        => 'string',
							'description' => __( 'Device name', 'wp-event-manager-sell-tickets' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/'.$this->rest_base.'/fields', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_guest_lists_fields' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

	}

	/**
	 * Get object.
	 *
	 * @param int $id Object ID.
	 *
	 * @since  3.0.0
	 * @return Post Data object
	 */
	protected function get_object($id) {
		return get_post($id);
	}

	/**
	 * Prepare a single event output for response.
	 *
	 * @param Post $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since  3.0.0
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_guest_lists_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Post Data $object Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "wpem_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		$args['post_type'] = $this->post_type;
		$args['post_parent'] = $request['event_id'];


		if( isset($request['group_id']) && !empty($request['group_id']) )
		{
			$args['meta_query'][] = [
		            'key'     => '_guests_group',
		            'value'   => $request['group_id'],
		            'compare' => '=',
		        ];

		    return $args;
		}else{

			return new WP_Error(
				"wpem_rest_{$this->post_type}_invalid_group_id",
				__( 'Invalid group ID.', 'wp-event-manager-rest-api' ),
				array(
					'status' => 400,
				)
			);

		}


	}

	/**
	 * Get event data.
	 *
	 * @param $post Event instance.
	 * @param string $context Request context.
	 * Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_guest_lists_data( $guest_list, $context = 'view' ) {

		$meta_data = get_post_meta($guest_list->ID);
		foreach ($meta_data as $key => $value) {
			$meta_data[$key] = get_post_meta($guest_list->ID,$key,true);
		}

		$data = array(
				'id'                    => $guest_list->ID,
				'name'                  => $guest_list->post_title,
				'slug'                  => $guest_list->post_name,
				'permalink'             => get_permalink( $guest_list->ID ),
				'date_created'          => get_the_date('',$guest_list),
				'date_modified'         => get_the_modified_date( '', $guest_list),
				'status'                => $guest_list->post_status,
				'featured'              => $guest_list->_featured,

				'description'           => 'view' === $context ? wpautop( do_shortcode( get_event_description($guest_list) ) ) : get_event_description($guest_list ),

				'avatar'                => rest_get_avatar_urls( $guest_list->author ),
				//'menu_order'            => '',
				'meta_data'             =>  $meta_data,
		);

		return $data;
	}

	/**
	 * Prepare a single product review output for response.
	 *
	 * @param WP_Comment $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $guest_list, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$fields  = $this->get_fields_for_response( $request );
		$data    = array();

		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = (int) $review->comment_ID;
		}
		if ( in_array( 'date_created', $fields, true ) ) {
			$data['date_created'] = wpem_rest_prepare_date_response( $review->comment_date );
		}
		if ( in_array( 'date_created_gmt', $fields, true ) ) {
			$data['date_created_gmt'] = wpem_rest_prepare_date_response( $review->comment_date_gmt );
		}
		if ( in_array( 'product_id', $fields, true ) ) {
			$data['product_id'] = (int) $review->comment_post_ID;
		}
		if ( in_array( 'status', $fields, true ) ) {
			$data['status'] = $this->prepare_status_response( (string) $review->comment_approved );
		}
		if ( in_array( 'reviewer', $fields, true ) ) {
			$data['reviewer'] = $review->comment_author;
		}
		if ( in_array( 'reviewer_email', $fields, true ) ) {
			$data['reviewer_email'] = $review->comment_author_email;
		}
		if ( in_array( 'review', $fields, true ) ) {
			$data['review'] = 'view' === $context ? wpautop( $review->comment_content ) : $review->comment_content;
		}
		if ( in_array( 'rating', $fields, true ) ) {
			$data['rating'] = (int) get_comment_meta( $review->comment_ID, 'rating', true );
		}
		if ( in_array( 'verified', $fields, true ) ) {
			$data['verified'] = wpem_review_is_from_verified_owner( $review->comment_ID );
		}
		if ( in_array( 'reviewer_avatar_urls', $fields, true ) ) {
			$data['reviewer_avatar_urls'] = rest_get_avatar_urls( $review->comment_author_email );
		}

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $review, $request ) );

		/**
		 * Filter product reviews object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Comment       $review   Product review object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_product_review', $response, $review, $request );
	}

	/**
	 * Prepare a single product review to be inserted into the database.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return array|WP_Error  $prepared_review
	 */
	protected function prepare_item_for_database( $request ) {
		if ( isset( $request['id'] ) ) {
			$prepared_review['comment_ID'] = (int) $request['id'];
		}

		if ( isset( $request['review'] ) ) {
			$prepared_review['comment_content'] = $request['review'];
		}

		if ( isset( $request['product_id'] ) ) {
			$prepared_review['comment_post_ID'] = (int) $request['product_id'];
		}

		if ( isset( $request['reviewer'] ) ) {
			$prepared_review['comment_author'] = $request['reviewer'];
		}

		if ( isset( $request['reviewer_email'] ) ) {
			$prepared_review['comment_author_email'] = $request['reviewer_email'];
		}

		if ( ! empty( $request['date_created'] ) ) {
			$date_data = rest_get_date_with_gmt( $request['date_created'] );

			if ( ! empty( $date_data ) ) {
				list( $prepared_review['comment_date'], $prepared_review['comment_date_gmt'] ) = $date_data;
			}
		} elseif ( ! empty( $request['date_created_gmt'] ) ) {
			$date_data = rest_get_date_with_gmt( $request['date_created_gmt'], true );

			if ( ! empty( $date_data ) ) {
				list( $prepared_review['comment_date'], $prepared_review['comment_date_gmt'] ) = $date_data;
			}
		}

		/**
		 * Filters a review after it is prepared for the database.
		 *
		 * Allows modification of the review right after it is prepared for the database.
		 *
		 * @since 3.5.0
		 * @param array           $prepared_review The prepared review data for `wp_insert_comment`.
		 * @param WP_REST_Request $request         The current request.
		 */
		return apply_filters( 'woocommerce_rest_preprocess_product_review', $prepared_review, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Comment $review Product review object.
	 * @return array Links for the given product review.
	 */
	protected function prepare_links( $review, $request ) {
		$links = array(
				'self'       => array(
						'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $review->comment_ID ) ),
				),
				'collection' => array(
						'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
				),
		);

		if ( 0 !== (int) $review->comment_post_ID ) {
			$links['up'] = array(
					'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $review->comment_post_ID ) ),
			);
		}

		if ( 0 !== (int) $review->user_id ) {
			$links['reviewer'] = array(
					'href'       => rest_url( 'wp/v2/users/' . $review->user_id ),
					'embeddable' => true,
			);
		}

		return $links;
	}

	/**
	 * Prepare a single guest list for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error | Post
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		global $reg_id;
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		if ( isset( $request['id'] ) ) {
			$event = get_post( $id );
		}
		else
		{
			if(!empty($request['guest_name']) && isset($request['event_id'])  && isset($request['wp_event_manager_send_guest']) ){
				$event_id = $request['event_id'];
				$_POST =  $request;

				if(class_exists('WPEM_Guests_Dashboard'))
				{
					$guest_lists_dashboard = new WPEM_Guests_Dashboard();
					$guest_list_id = $guest_lists_dashboard->guest_list_form_handler();

					$guest_list = get_post($guest_list_id);
				}

			}
			else{
				return;
			}

		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param Post         $event  Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "wpem_rest_pre_insert_{$this->post_type}_object", $guest_list, $request, $creating );
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id     = (int) $request['id'];
		$force  = (bool) $request['force'];
		$object = $this->get_object( (int) $request['id'] );
		$result = false;

		if ( ! $object || 0 === $object->ID ) {
			return new WP_Error(
				"wpem_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID.', 'wp-event-manager-rest-api' ),
				array(
					'status' => 400,
				)
			);
		}



		$supports_trash = EMPTY_TRASH_DAYS > 0 && is_callable( array( $object, 'get_status' ) );

		/**
		 * Filter whether an object is trashable.
		 *
		 * Return false to disable trash support for the object.
		 *
		 * @param boolean $supports_trash Whether the object type support trashing.
		 * @param WC_Data $object         The object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "wpem_rest_{$this->post_type}_object_trashable", $supports_trash, $object );

		if ( ! wpem_rest_check_post_permissions( $this->post_type, 'delete', $object->ID ) ) {
			return new WP_Error(
				"wpem_rest_user_cannot_delete_{$this->post_type}",
				/* translators: %s: post type */
				sprintf( __( 'Sorry, you are not allowed to delete %s.', 'wp-event-manager-guests' ), $this->post_type ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$result = wp_delete_post($object->ID,true);

			//$result = 0;
		} else {
			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				return new WP_Error(
					'wpem_rest_trash_not_supported',
					/* translators: %s: post type */
					sprintf( __( 'The %s does not support trashing.', 'wp-event-manager-rest-api' ), $this->post_type ),
					array(
						'status' => 501,
					)
				);
			}

			// Otherwise, only trash if we haven't already.
			if ( is_callable( array( $object, 'get_status' ) ) ) {
				if ( 'trash' === $object->get_status() ) {
					return new WP_Error(
						'wpem_rest_already_trashed',
						/* translators: %s: post type */
						sprintf( __( 'The %s has already been deleted.', 'wp-event-manager-rest-api' ), $this->post_type ),
						array(
							'status' => 410,
						)
					);
				}

				$result =  wp_delete_post($object->ID);
			}
		}

		if ( ! $result ) {
			return new WP_Error(
				'wpem_rest_cannot_delete',
				/* translators: %s: post type */
				sprintf( __( 'The %s cannot be deleted.', 'wp-event-manager-guests' ), $this->post_type ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param Post          $object   The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "wpem_rest_delete_{$this->post_type}_object", $object, $response, $request );

		return $response;
	}

	/**
	 * Get the Product Review's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'event_guests',
				'type'       => 'object',
				'properties' => array(
						'id'               => array(
								'description' => __( 'Unique identifier for the resource.', 'wp-event-manager-guests' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
						),
						'date_created'     => array(
								'description' => __( "The date the guest list was created, in the site's timezone.", 'wp-event-manager-guests' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
						),
						'date_created_gmt' => array(
								'description' => __( 'The date the guest list was created, as GMT.', 'wp-event-manager-guests' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
						),
						'event_id'       => array(
								'description' => __( 'Unique identifier for the event that the guest list belongs to.', 'wp-event-manager-guests' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
						),
						'status'           => array(
								'description' => __( 'Status of the guest list.', 'wp-event-manager-guests' ),
								'type'        => 'string',
								'default'     => 'approved',
								'enum'        => array( 'new', 'waiting', 'confirm', 'concelled', 'trash', 'untrash' ),
								'context'     => array( 'view', 'edit' ),
						),
						'registration_name'         => array(
								'description' => __( 'Attendee name.', 'wp-event-manager-guests' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
						),
						'registration_email'   => array(
								'description' => __( 'Attendee email.', 'wp-event-manager-guests' ),
								'type'        => 'string',
								'format'      => 'email',
								'context'     => array( 'view', 'edit' ),
						),
				),
		);

		if ( get_option( 'show_avatars' ) ) {
			$avatar_properties = array();
			$avatar_sizes      = rest_get_avatar_sizes();

			foreach ( $avatar_sizes as $size ) {
				$avatar_properties[ $size ] = array(
						/* translators: %d: avatar image size in pixels */
						'description' => sprintf( __( 'Avatar URL with image size of %d pixels.', 'wp-event-manager-guests' ), $size ),
						'type'        => 'string',
						'format'      => 'uri',
						'context'     => array( 'embed', 'view', 'edit' ),
				);
			}
			$schema['properties']['registration_avatar_urls'] = array(
					'description' => __( 'Avatar URLs for the object reviewer.', 'wp-event-manager-guests' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => $avatar_properties,
			);
		}

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['after']            = array(
				'description' => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'wp-event-manager-guests' ),
				'type'        => 'string',
				'format'      => 'date-time',
		);
		$params['before']           = array(
				'description' => __( 'Limit response to reviews published before a given ISO8601 compliant date.', 'wp-event-manager-guests' ),
				'type'        => 'string',
				'format'      => 'date-time',
		);
		$params['exclude']          = array(
				'description' => __( 'Ensure result set excludes specific IDs.', 'wp-event-manager-guests' ),
				'type'        => 'array',
				'items'       => array(
						'type' => 'integer',
				),
				'default'     => array(),
		);
		$params['include']          = array(
				'description' => __( 'Limit result set to specific IDs.', 'wp-event-manager-guests' ),
				'type'        => 'array',
				'items'       => array(
						'type' => 'integer',
				),
				'default'     => array(),
		);
		$params['offset']           = array(
				'description' => __( 'Offset the result set by a specific number of items.', 'wp-event-manager-guests' ),
				'type'        => 'integer',
		);
		$params['order']            = array(
				'description' => __( 'Order sort attribute ascending or descending.', 'wp-event-manager-guests' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array(
						'asc',
						'desc',
				),
		);
		$params['orderby']          = array(
				'description' => __( 'Sort collection by object attribute.', 'wp-event-manager-guests' ),
				'type'        => 'string',
				'default'     => 'date_gmt',
				'enum'        => array(
						'date',
						'date_gmt',
						'id',
						'include',
						'product',
				),
		);
		$params['attendee']         = array(
				'description' => __( 'Limit result set to attendees assigned to specific user IDs.', 'wp-event-manager-guests' ),
				'type'        => 'array',
				'items'       => array(
						'type' => 'integer',
				),
		);
		$params['attendee_exclude'] = array(
				'description' => __( 'Ensure result set excludes reviews assigned to specific user IDs.', 'wp-event-manager-guests' ),
				'type'        => 'array',
				'items'       => array(
						'type' => 'integer',
				),
		);
		$params['guest_email']   = array(
				'default'     => null,
				'description' => __( 'Limit result set to that from a specific author email.', 'wp-event-manager-guests' ),
				'format'      => 'email',
				'type'        => 'string',
		);
		$params['event']          = array(
				'default'     => array(),
				'description' => __( 'Limit result set to registration assigned to specific event IDs.', 'wp-event-manager-guests' ),
				'type'        => 'array',
				'items'       => array(
						'type' => 'integer',
				),
		);
		$params['status']           = array(
				'default'           => 'publish',
				'description'       => __( 'Limit result set to attendees assigned a specific status.', 'wp-event-manager-guests' ),
				'sanitize_callback' => 'sanitize_key',
				'type'              => 'string',
				'enum'              => array(
						'all',
						'new',
						'waiting',
						'confirm',
						'cancelled',
				),
		);

		/**
		 * Filter collection parameters for the registration controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Comment_Query parameter. Use the
		 * `wpem_rest_registration_query` filter to set WP_Query parameters.
		 *
		 * @since 3.1.14
		 * @param array $params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'wpem_rest_event_guests_collection_params', $params );
	}

	/**
	 * Get the reivew, if the ID is valid.
	 *
	 * @since 3.1.14
	 * @param int $id Supplied ID.
	 * @return WP_Comment|WP_Error Comment object if ID is valid, WP_Error otherwise.
	 */
	protected function get_registration( $id ) {
		$id    = (int) $id;
		$error = new WP_Error( 'wpem_rest_registration_invalid_id', __( 'Invalid review ID.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );

		if ( 0 >= $id ) {
			return $error;
		}

		$registration = get_post( $id );
		if ( empty( $registration ) ) {
			return $error;
		}

		if ( ! empty( $registration->ID ) ) {
			$post = get_post( (int) $registration->ID );

			if ( 'event_registration' !== get_post_type( (int) $registration->ID ) ) {
				return new WP_Error( 'wpem_rest_product_invalid_id', __( 'Invalid product ID.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
			}
		}

		return $registration;
	}

	/**
	 * Prepends internal property prefix to query parameters to match our response fields.
	 *
	 * @since 3.5.0
	 * @param string $query_param Query parameter.
	 * @return string
	 */
	protected function normalize_query_param( $query_param ) {
		$prefix = 'comment_';

		switch ( $query_param ) {
			case 'id':
				$normalized = $prefix . 'ID';
				break;
			case 'product':
				$normalized = $prefix . 'post_ID';
				break;
			case 'include':
				$normalized = 'comment__in';
				break;
			default:
				$normalized = $prefix . $query_param;
				break;
		}

		return $normalized;
	}

	/**
	 * Checks comment_approved to set comment status for single comment output.
	 *
	 * @since 3.5.0
	 * @param string|int $comment_approved comment status.
	 * @return string Comment status.
	 */
	protected function prepare_status_response( $comment_approved ) {
		switch ( $comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;
			case 'approve':
			case '1':
				$status = 'approved';
				break;
			case 'spam':
			case 'trash':
			default:
				$status = $comment_approved;
				break;
		}

		return $status;
	}

	/**
	 * Get the query params and return event fields
	 *
	 * @return array
	 */
	public function get_guest_lists_fields($request){
		$fields = get_event_guests_form_fields();
		return $fields;
	}

	/**
	* wpem_sell_tickets_add_data_to_listing
	*
	* @param $data, $event, $context
	* @since 1.8.8
	*/
	public function wpem_guest_lists_add_data_to_listing($data, $event, $context){

		$data['guest_listing'] = [];
		$total_guests = 0;

		$groups = get_event_guests_group('', '', $event->ID);
		if(!empty($groups))
		{
			foreach ($groups as $group)
			{
				$guests = get_guests($group->id, '', $event->ID);

				$group_name = 'group_' . sanitize_title($group->group_name);
				$group_name = str_replace('-', '_', $group_name);

				$count = !empty($guests) ? count($guests) : 0;
				$data['guest_listing'][$group_name] = $count;

				$total_guests += $count;
			}
		}

		$data['guest_listing']['total_guests'] = $total_guests;

		return $data;
	}

	public function get_groups($request){

		$get_group = get_event_guests_group('', '', $request['event_id']);

		$data = [];
		$total_checkin = 0;

		if( isset($get_group) && !empty($get_group) )
		{

			foreach ($get_group as $key => $group)
			{
				foreach ($group as $name => $value)
				{
					if($name == 'group_fields')
					{
						$data[$key][$name] = json_decode($value, true);
					}
					else
					{
						$data[$key][$name] = $value;
					}
				}

					$get_guests = get_guests($group->id);

					if(is_array($get_guests)){
					foreach ($get_guests as $guest_key => $value) {


						if(get_post_meta($value->ID,'_check_in',true))
							$total_checkin++;
					}

					$data[$key]['total_checkin'] = $total_checkin;
					$data[$key]['total_guest'] = count($get_guests);
				}


			}
		}
		else
		{
			return array('message' => __('There are not group.','wp-event-manager-guests') );
		}

		return $data;
	}

	public function create_group($request) {
		if(class_exists('WPEM_Guests_Post_Types')) {
			$request['group_fields'] = isset($request['group_fields']) ? explode(',', $request['group_fields']) : '';

			$data = [
				'user_id' 			=> $request['user_id'],
				'event_id' 			=> $request['event_id'],
				'group_name' 		=> $request['group_name'],
				'group_description' => $request['group_description'],
				'group_fields'      => $request['group_fields'],
			];

			$guest_lists_post_type = new WPEM_Guests_Post_Types();
			$group_id = $guest_lists_post_type::save_guest_lists_group($data);

			if( isset($group_id) && !empty($group_id) ) {
				$group = get_event_guests_group($group_id);
				error_log(print_r($group, true));
				$data = [];
				foreach ($group as $name => $value) {
					if ($name == 'group_fields') {
						$data[$name] = json_decode($value, true);
					} else {
						$data[$name] = $value;
					}
				}
			} else {
				return new WP_Error( 'wpem_rest_group_invalid', __( 'Invalid group details.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
			}
		}
		else
		{
			return new WP_Error( 'wpem_rest_group_invalid', __( 'Invalid mothod.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		return $data;
	}

	public function update_group($request)
	{
		$request['group_fields'] = isset($request['group_fields']) ? explode(',', $request['group_fields']) : '';

		$data = [
			'user_id' 			=> $request['user_id'],
			'id' 				=> $request['group_id'],
			'event_id' 			=> $request['event_id'],
			'group_name' 		=> $request['group_name'],
			'group_description' => $request['group_description'],
			'group_fields'      => $request['group_fields'],
		];

		$guest_lists_post_type = new WPEM_Guests_Post_Types();
		$group_id = $guest_lists_post_type::save_guest_lists_group($data);

		$group = get_event_guests_group($request['group_id']);

		$data = [];

		if( isset($group) && !empty($group) )
		{
			foreach ($group as $name => $value)
			{
				if($name == 'group_fields')
				{
					$data[$name] = json_decode($value, true);
				}
				else
				{
					$data[$name] = $value;
				}
			}
		}
		else
		{
			return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		return $data;
	}

	public function get_group($request) {
		$event_id = absint($request["event_id"]);
		$group = get_event_guests_group($request['group_id']);

		if ($group && absint($group->event_id) === $event_id) {
			$data = [];
			if( isset($group) && !empty($group) ) {
				foreach ($group as $name => $value) {
					if($name == 'group_fields') {
						$data[$name] = json_decode($value, true);
					} else {
						$data[$name] = $value;
					}
				}


				$get_guests = get_guests($request['group_id']);
				$total_checkin = 0;
				if ($get_guests) {
					foreach ($get_guests as $guest_key => $value) {
						if(get_post_meta($value->ID,'_check_in',true)) {
							$total_checkin ++;
						}
					}
				}

				$data['total_checkin'] = $total_checkin;
				$data['total_guest'] = is_array($get_guests ) ? count($get_guests) : 0;

			} else {
				return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
			}
			return $data;
		} else {
			return array(
				'message' => __('Group not found for this event','wp-event-manager-guests'),
				'data' 		=> array('status' => 404)
			);
		}
	}

	public function delete_group($request) {
		global $wpdb;
		$event_id = absint($request["event_id"]);
		$group = get_event_guests_group($request['group_id']);

		if (absint($group->event_id) === $event_id) {
			$delete = delete_event_guests_group($request['group_id']);
			if($delete) {
				return array(	'message' => __('Group deleted successfully','wp-event-manager-guests')	);
			} else {
				return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
			}
		} else {
			return array(
				'message' => __('Group not found for this event','wp-event-manager-guests'),
				'data' 		=> array('status' => 404)
			);
		}
	}

	public function get_guests($request) {
		$get_guests = get_guests($request['group_id']);

		$group = get_event_guests_group($request['group_id']);

		if( isset($group) && empty($group) )
		{
			return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		if( isset($get_guests) && !empty($get_guests) )
		{
			$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : '';

			$data = [];

			foreach ($get_guests as $key => $guest)
			{
				$data[$key]['id'] = $guest->ID;
				$data[$key]['guest_lists_group'] = $request['group_id'];


				$data[$key]['slug'] = $guest->post_name;
				$data[$key]['permalink']       = get_permalink( $guest->ID );
				$data[$key]['date_created']    = get_the_date('',$guest);
				$data[$key]['date_modified']   = get_the_modified_date( '', $guest);
				$data[$key]['status']			= $guest->post_status;



				if( isset($group_fields) && !empty($group_fields) )
				{
					foreach ($group_fields as $field)
					{
						$data[$key][$field] = get_post_meta($guest->ID, $field, true);
					}
				}

				$guest_meta = get_post_meta($guest->ID);
				foreach ($guest_meta as $guest_key => $value) {
					$guest_meta[$guest_key] = get_post_meta($guest->ID,$guest_key,true);
				}

				$data[$key]['meta_data']= $guest_meta;
			}
		}
		else
		{
			return array(  );
		}

		return $data;
	}

	public function create_guest($request) {
		if( !empty($request['guest_name']) && isset($request['event_id']) && isset($request['group_id']) ) {
			$event_id = $request['event_id'];
			$request['guest_lists_group'] = $request['group_id'];
			$_POST =  $request;

			if(class_exists('WPEM_Guests_Dashboard'))
			{
				$guest_lists_dashboard = new WPEM_Guests_Dashboard();
				$guest_list_id = $guest_lists_dashboard->guest_list_form_handler();

				$group = get_event_guests_group($request['group_id']);

				$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : '';

				$data = [];

				$data['guest_id'] = $guest_list_id;
				$data['guest_lists_group'] = $request['group_id'];

				if( isset($group_fields) && !empty($group_fields) ) {
					foreach ($group_fields as $field) {
						$data[$field] = get_post_meta($guest_list_id, $field, true);
					}
				}
			}
		}
		else
		{
			return new WP_Error( 'wpem_rest_guest_invalid', __( 'Invalid guest detail.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		return $data;
	}

	public function update_guest( $request ) {
		$event_id = absint($request["event_id"]);
		$guest_id = absint($request['guest_id']);
		$object = $this->get_object($guest_id);
		$group = get_event_guests_group($request['group_id']);
		if ($object && $object->post_type === $this->post_type && absint($object->post_parent) === $event_id) {
			if ($object->ID === 0) {
				return new WP_Error( "wpem_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'wp-event-manager-rest-api' ), array( 'status' => 400 ) );
			}

			if(class_exists('WPEM_Guests_Dashboard')) {
				$_POST =  $request;
				$_POST['guest_lists_group'] = $request['group_id'];
				$guest_lists_dashboard = new WPEM_Guests_Dashboard();
				$guest_lists_dashboard->guest_list_form_handler();

				$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : '';
				$data = [];
				$data['guest_id'] = $guest_id;
				$data['guest_lists_group'] = $request['group_id'];

				if( isset($group_fields) && !empty($group_fields) ) {
					foreach ($group_fields as $field) {
						$data[$field] = get_post_meta($guest_id, $field, true);
					}
				}
				return $data;
			} else {
				return array(
					'message' => __('Unable to update guest data','wp-event-manager-guests'),
					'data' 		=> array('status' => 500)
				);
			}

		} else {
			return array(
				'message' => __('Guest not found for this event','wp-event-manager-guests'),
				'data' 		=> array('status' => 404)
			);
		}
	}

	public function get_guest($request) {
		if( !empty($request['guest_id']) && isset($request['guest_id']) ) {
			$group = get_event_guests_group($request['group_id']);
			$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : '';
			$guest = get_post($request['guest_id']);

			$data = [];

			$data['guest_id'] = $request['guest_id'];
			$data['guest_lists_group'] = $request['group_id'];

			$data['slug'] = $guest->post_name;
			$data['permalink']       = get_permalink( $guest->ID );
			$data['date_created']    = get_the_date('',$guest);
			$data['date_modified']   = get_the_modified_date( '', $guest);
			$data['status']			= $guest->post_status;
			$meta_data 				=  get_post_meta($request['guest_id']);

			foreach ($meta_data  as $key => $value) {
				$meta_data[$key] = get_post_meta($request['guest_id'],$key,true);
			}

			if( isset($group_fields) && !empty($group_fields) )
			{
				foreach ($group_fields as $field)
				{
					$data[$field] = get_post_meta($request['guest_id'], $field, true);
				}
			}

			$data['meta_data']= $meta_data;
		} else {
			return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		return $data;
	}

	public function delete_guest($request)
	{
		if( !empty($request['guest_id']) && isset($request['guest_id']) )
		{
			$group = get_event_guests_group($request['group_id']);

			$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : '';

			$data = [];

			$data['guest_id'] = $request['guest_id'];
			$data['guest_lists_group'] = $request['group_id'];

			if( isset($group_fields) && !empty($group_fields) )
			{
				foreach ($group_fields as $field)
				{
					$data[$field] = get_post_meta($request['guest_id'], $field, true);
				}
			}

			wp_delete_post($request['guest_id'], $request['force']);
		}
		else
		{
			return new WP_Error( 'wpem_rest_group_invalid_id', __( 'Invalid group id.', 'wp-event-manager-guests' ), array( 'status' => 404 ) );
		}

		return $data;
	}

	/**
	* checkin_attendee
	*
	* @param $request
	* @since 1.8.8
	*/
	public function checkin_guest($request){

		if( isset($request['guest_id']) && $request['force']  && $request['force'] == true ){

			$guest_id 			= absint($request['guest_id']);
			$checkin_source 		= isset($request['checkin_source']) ? $request['checkin_source'] : '';
			$checkin_device_id 		= isset($request['checkin_device_id']) ? $request['checkin_device_id'] : '';
			$checkin_device_name 	= isset($request['checkin_device_name']) ? $request['checkin_device_name'] : '';

			update_post_meta($guest_id,'_check_in',1);
			update_post_meta($guest_id,'_checkin_source',$checkin_source);
			update_post_meta($guest_id,'_checkin_device_id',$checkin_device_id);
			update_post_meta($guest_id,'_checkin_device_name',$checkin_device_name);
			update_post_meta($guest_id,'_checkin_time',current_time( 'mysql' ));
			return array('message' => __('Checkin successfull','wp-event-manager-sell-tickets'),
		'data' 		=> array('status' => 200));
		}
		elseif(isset($request['guest_id']) && isset($request['force']) && $request['force'] == false)
		{
			$guest_id = absint($request['guest_id']);
			update_post_meta($guest_id,'_check_in',0);
			return array(	'message' 	=> __('Udo Checkin successfull','wp-event-manager-sell-tickets'),
							'data' 		=> array('status' => 200)
				);
		}
		else
		{
			return array('message' => __('There was some error while checkin','wp-event-manager-sell-tickets'),
				'data' 		=> array('status' => 401) );
		}

	}

	/**
	* checkout_attendee
	*
	* @param $request
	* @since 1.8.8
	*/
	public function checkout_guest($request){

		if( isset($request['guest_id']) && $request['force']  && $request['force'] == true ){

			$guest_id 			= absint($request['guest_id']);
			$checkin_source 		= isset($request['checkout_source']) ? $request['checkout_source'] : '';
			$checkin_device_id 		= isset($request['checkout_device_id']) ? $request['checkout_device_id'] : '';
			$checkin_device_name 	= isset($request['checkout_device_name']) ? $request['checkout_device_name'] : '';

			update_post_meta($guest_id,'_check_out',1);
			update_post_meta($guest_id,'_checkout_source',$checkin_source);
			update_post_meta($guest_id,'_checkout_device_id',$checkin_device_id);
			update_post_meta($guest_id,'_checkout_device_name',$checkin_device_name);
			update_post_meta($guest_id,'_checkout_time',current_time( 'mysql' ));
			return array('message' => __('Check out successfull','wp-event-manager-sell-tickets'));
		}
		elseif(isset($request['guest_id']) && isset($request['force']) && $request['force'] == false)
		{
			$guest_id = absint($request['guest_id']);
			update_post_meta($guest_id,'_check_out',0);
			update_post_meta($guest_id,'_checkout_source','');
			update_post_meta($guest_id,'_checkout_device_id','');
			update_post_meta($guest_id,'_checkout_device_name','');
			update_post_meta($guest_id,'_checkout_time','');
			return array(	'message' => __('Undo check In successfull','wp-event-manager-sell-tickets')	);
		}
		else
		{
			return array('message' => __('There was some error while checkin','wp-event-manager-sell-tickets') );
		}

	}

	/**
	 * Check if a given request has access to read an item.
	 * @override parent::get_item_permissions_check
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */

	/**
	 * Check if a given request has access to read an item.
	 * @override parent::get_item_permissions_check
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$object = $this->get_object((int) $request['guest_id']);
		if (!is_wp_error($object) && $object) {
			$object_id = $object->ID;
			if ($object->post_type === 'product') {
				$object_id = $object->get_id();
			}

			if (0 !== $object_id && ! wpem_rest_api_check_post_permissions($this->post_type, 'read', $object_id) ) {
				return new WP_Error('wpem_rest_cannot_view', __('Sorry, you cannot view this resource.', 'wpem-rest-api'), array( 'status' => rest_authorization_required_code() ));
			}
			return true;
		} else {
			// pass actual error to response
			return $object;
		}
	}

	/**
	 * Check if a given request has access to update an item.
	 * @override parent::update_item_permissions_check
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check($request) {
		$object = $this->get_object((int) $request['guest_id']);
		if (!is_wp_error($object) && $object) {
			if ($object && 0 !== $object->ID && ! wpem_rest_api_check_post_permissions($this->post_type, 'edit', $object->ID) ) {
				return new WP_Error('wpem_rest_cannot_edit', __('Sorry, you are not allowed to edit this resource.', 'wpem-rest-api'), array( 'status' => rest_authorization_required_code() ));
			}
			return true;
		} else {
			// pass actual error to response
			return $object;
		}
	}

	/**
	 * Check if a given request has access to delete an item.
	 * @override parent::delete_item_permissions_check
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$object = $this->get_object((int) $request['guest_id']);
		if (!is_wp_error($object) && $object) {
			if ($object && 0 !== $object->ID && ! wpem_rest_api_check_post_permissions($this->post_type, 'delete', $object->ID) ) {
				return new WP_Error('wpem_rest_cannot_delete', __('Sorry, you are not allowed to delete this resource.', 'wpem-rest-api'), array( 'status' => rest_authorization_required_code() ));
			}
			return true;
		} else {
			// pass actual error to response
			return $object;
		}
	}
}

new WPEM_REST_Event_Guest_Lists_Controller();
