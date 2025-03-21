<?php

/**
 * Get the default email content
 * @return string
 */
function get_new_event_default_email_content() {
	$message = <<<EOF
Hello

New Event "[event_title]" Submitted Successfully.

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[event_description]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

You can contact them directly at: [site_admin_email]
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_new_event_email_content() {
	return apply_filters( 'new_event_email_content', get_option( 'new_event_email_content', get_new_event_default_email_content() ) );
}

/**
 * Get the default email subject
 * @return string
 */
function get_new_event_default_email_subject() {
	return __( "New Event \"[event_title]\" Submited Successfully", 'wp-event-manager-emails' );
}

/**
 * Get New event Email Content
 * @return string
 */
function get_new_event_email_subject() {
	return apply_filters( 'new_event_email_subject', get_option( 'new_event_email_subject', get_new_event_default_email_subject() ) );
}


/**
 * Get the published default email content
 * @return string
 */
function get_published_event_default_email_content() {
	$message = <<<EOF
Hello

New Event "[event_title]" Published Successfully.

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[event_description]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

You can contact them directly at: [site_admin_email]
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_published_event_email_content() {
	return apply_filters( 'published_event_email_content', get_option( 'published_event_email_content', get_published_event_default_email_content() ) );
}

/**
 * Get the default email subject
 * @return string
 */
function get_published_event_default_email_subject() {
	return __( "Event \"[event_title]\" Published Successfully", 'wp-event-manager-emails' );
}

/**
 * Get New event Email Content
 * @return string
 */
function get_published_event_email_subject() {
	return apply_filters( 'published_event_email_subject', get_option( 'published_event_email_subject', get_published_event_default_email_subject() ) );
}

/**
 * Get the expired default email content
 * @return string
 */
function get_expired_event_default_email_content() {
	$message = <<<EOF
Hello

Your Event "[event_title]" Expired.

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[event_description]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

You can contact them directly at: [site_admin_email]
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_expired_event_email_content() {
	return apply_filters( 'expired_event_email_content', get_option( 'expired_event_email_content', get_expired_event_default_email_content() ) );
}

/**
 * Get the default email subject
 * @return string
 */
function get_expired_event_default_email_subject() {
	return __( "Event \"[event_title]\" Expired", 'wp-event-manager-emails' );
}

/**
 * Get New event Email Content
 * @return string
 */
function get_expired_event_email_subject() {
	return apply_filters( 'expired_event_email_subject', get_option( 'expired_event_email_subject', get_expired_event_default_email_subject() ) );
}


/**
 * Get the admin default email content
 * @return string
 */
function get_admin_event_default_email_content() {
	$message = <<<EOF
Hello

New Event "[event_title]" posted on your site.

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[event_description]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

You can contact them directly at: [organizer_email]
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_admin_event_email_content() {
	return apply_filters( 'admin_event_email_content', get_option( 'admin_event_email_content', get_admin_event_default_email_content() ) );
}

/**
 * Get the default email subject
 * @return string
 */
function get_admin_event_default_email_subject() {
	return __( "New Event \"[event_title]\" Posted", 'wp-event-manager-emails' );
}

/**
 * Get New event Email Content
 * @return string
 */
function get_admin_event_email_subject() {
	return apply_filters( 'admin_event_email_subject', get_option( 'admin_event_email_subject', get_admin_event_default_email_subject() ) );
}

/**
 * Get tags to dynamically replace in the notification email
 * @return array
 */
function get_event_manager_email_tags() {
	$tags = array(
		'site_admin_email'  =>      __( 'Site Admin Email', 'wp-event-manager-emails' ),	
		'organizer_email'   =>      __( 'Organizer Email', 'wp-event-manager-emails' ),	
		'organizer_name'    =>      __( 'Name of the organizer which submitted the event listing', 'wp-event-manager-emails' ),
		'event_type'        =>      __( 'Event Type', 'wp-event-manager-emails' ),
		'event_category'    =>      __( 'Event Category', 'wp-event-manager-emails' ),
		/* 'user_id'        =>      __( 'Organizer ID', 'wp-event-manager-emails' ), */
		'event_id'          =>      __( 'Event ID', 'wp-event-manager-emails' ),
		'event_title'       =>      __( 'Event Title', 'wp-event-manager-emails' ),
		'event_description' =>      __( 'Event Description', 'wp-event-manager-emails' ),
		'event_post_meta'   =>      __( 'Some meta data from the event. e.g. <code>[event_post_meta key="_event_location"]</code>', 'wp-event-manager-emails' )
	);

	return $tags;
}

/**
 * Shortcode handler
 * @param  array $atts
 * @return string
 */
function event_manager_email_shortcode_handler( $atts, $content, $value ) {
	$atts = shortcode_atts( array(
		'prefix' => '',
		'suffix' => ''
	), $atts );

	if ( ! empty( $value ) ) {
		return wp_kses_post( $atts['prefix'] ) . $value . wp_kses_post( $atts['suffix'] );
	}
}

/**
 * Add shortcodes for email content
 * @param  array $data
 */
function event_manager_email_add_shortcodes( $data ) {
	extract( $data );

	$event_title         = strip_tags( get_the_title( $event_id ) );
	$event_description   = get_post_field('post_content', $event_id);	
	$dashboard_id        = get_option( 'event_manager_event_dashboard_page_id' );
	$meta_data           = array();
	$organizer_name      = get_organizer_name( $event_id );
	$user_id             = $data['user_id'];

	$event_type = get_event_type( $event_id );
	$types = [];
	if(!empty($event_type))	{
		foreach ( $event_type as $type ){
			$types[$type->term_id] = $type->name;
		}
	}
	$event_type = !empty($types) ? implode(', ', $types) : '';

	$event_category = get_event_category( $event_id );
	$categories = [];
	if(!empty($event_category))	{
		foreach ( $event_category as $category ){
			$categories[$category->term_id] = $category->name;
		}
	}
	$event_category = !empty($categories) ? implode(', ', $categories) : '';

	add_shortcode( 'organizer_email', function( $atts, $content = '' ) use( $organizer_email ) {
		return event_manager_email_shortcode_handler( $atts, $content, $organizer_email );
	} );	
	add_shortcode( 'site_admin_email', function( $atts, $content = '' ) use( $site_admin_email ) {
		return event_manager_email_shortcode_handler( $atts, $content, $site_admin_email );
	} );
	add_shortcode( 'event_id', function( $atts, $content = '' ) use( $event_id ) {
		return event_manager_email_shortcode_handler( $atts, $content, $event_id );
	} );
	add_shortcode( 'event_title', function( $atts, $content = '' ) use( $event_title ) {
		return event_manager_email_shortcode_handler( $atts, $content, $event_title );
	} );
	add_shortcode( 'event_description', function( $atts, $content = '' ) use( $event_description ) {
		return event_manager_email_shortcode_handler( $atts, $content, $event_description );
	} );
	add_shortcode( 'event_type', function( $atts, $content = '' ) use( $event_type ) {
		return event_manager_email_shortcode_handler( $atts, $content, $event_type );
	} );
	add_shortcode( 'event_category', function( $atts, $content = '' ) use( $event_category ) {
		return event_manager_email_shortcode_handler( $atts, $content, $event_category );
	} );
	add_shortcode( 'organizer_name', function( $atts, $content = '' ) use( $organizer_name ) {
		return event_manager_email_shortcode_handler( $atts, $content, $organizer_name );
	} );
	add_shortcode( 'user_id', function( $atts, $content = '' ) use( $user_id ) {
		return event_manager_email_shortcode_handler( $atts, $content, $user_id );
	} );
	add_shortcode( 'event_post_meta', function( $atts, $content = '' ) use( $event_id ) {
		$atts  = shortcode_atts( array( 'key' => '' ), $atts );
		$value = '';
		if(isset( $atts['key']) && !empty( $atts['key']))
		$value = get_post_meta( $event_id, sanitize_text_field( $atts['key'] ), true );
		return event_manager_email_shortcode_handler( $atts, $content, $value );
	} );

	do_action( 'new_event_email_add_shortcodes', $data );
}

if(!function_exists('get_organizer_notification_email')){
	/**
	 * This function is used to get organizer email id to send mail notification
	 * @param int, post
	 * @return array
	 * @since 1.2.3
	 */
	function get_organizer_notification_email( $event_id, $event ) {

		//check organizer mail notification settings
		$check_organizer_mail_notification_settings = get_option( 'organizer_mail_account_setting',true );

		$organizers = array();
		//if settings is true then need to get event registration email id need to send notification else organizer email id used to send notification
		if(isset($check_organizer_mail_notification_settings) && $check_organizer_mail_notification_settings == 'true'){
			 //check for registration email
			 $register = get_event_registration_method($event_id);
			 if (strstr($register->raw_email, '@') && is_email($register->raw_email)) {
				 $organizers['organizer_name']  = $register->raw_email;
				 $organizers['organizer_email'] = $register->raw_email;
			 }
		}else{
			$organizers['organizer_name']   = get_organizer_name($event);
			$organizers['organizer_email']  = get_event_organizer_email($event);
		}
		return $organizers;
	}
}
?>