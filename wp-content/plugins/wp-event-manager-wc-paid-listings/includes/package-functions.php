<?php
/**
 * Get a package
 *
 * @param  stdClass $package
 * @return WP_Event_Manager_Package
 */
function wpem_paid_listings_get_package( $package ) {
	return new WP_Event_Manager_Package( $package );
}

/**
 * Approve a listing
 *
 * @param  int $listing_id
 * @param  int $user_id
 * @param  int $user_package_id
 * @return void
 */
function wpem_paid_listings_approve_listing_with_package( $listing_id, $user_id, $user_package_id ) {
	if ( wpem_paid_listings_package_is_valid( $user_id, $user_package_id ) ) {
		$resumed_post_status = get_post_meta( $listing_id, '_post_status_before_package_pause', true );
		if ( ! empty( $resumed_post_status ) ) {
			$listing = array(
				'ID'            => $listing_id,
				'post_status'   => $resumed_post_status,
			);
			delete_post_meta( $listing_id, '_post_status_before_package_pause' );
		} else {
			$listing = array(
				'ID'            => $listing_id,
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
			);

			if(get_post_type( $listing_id ) == 'event_listing' ){
				delete_post_meta( $listing_id, '_event_expires' );
				$listing[ 'post_status' ] = get_option( 'event_manager_submission_requires_approval' ) ? 'pending' : 'publish';
			}
		}

		// Do update
		wp_update_post( $listing );
		update_post_meta( $listing_id, '_user_package_id', $user_package_id );

		/**
		 * Checks to see whether or not a particular event listing affects the package count.
		 * @param bool $event_listing_affects_package_count True if it affects package count.
		 * @param int  $listing_id                        Post ID.
		 */
		if ( apply_filters( 'event_manager_event_listing_affects_package_count', true, $listing_id ) ) {
			wpem_paid_listings_increase_package_count( $user_id, $user_package_id );
		}
	}
}

/**
 * Approve a event listing
 *
 * @param  int $event_id
 * @param  int $user_id
 * @param  int $user_package_id
 * @return void
 */
function wpem_paid_listings_approve_event_listing_with_package( $event_id, $user_id, $user_package_id ) {
	wpem_paid_listings_approve_listing_with_package( $event_id, $user_id, $user_package_id );
}

/**
 * See if a package is valid for use
 *
 * @param int $user_id
 * @param int $package_id
 * @return bool
 */
function wpem_paid_listings_package_is_valid( $user_id, $package_id ) {
	global $wpdb;

	$package = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}emwcpl_user_packages WHERE user_id = %d AND id = %d;", $user_id, $package_id ) );

	if ( ! $package ) {
		return false;
	}

	if ( $package->package_count >= $package->package_limit && $package->package_limit != 0 ) {
		return false;
	}

	return true;
}

/**
 * Increase event count for package
 *
 * @param  int $user_id
 * @param  int $package_id
 * @return int affected rows
 */
function wpem_paid_listings_increase_package_count( $user_id, $package_id ) {
	global $wpdb;
	$packages = wpem_paid_listings_get_user_packages( $user_id );

	if ( isset( $packages[ $package_id ] ) ) {	
		$new_count = $packages[ $package_id ]->package_count + 1;
	} else {
		$new_count = 1;
	}

	return $wpdb->update(
		"{$wpdb->prefix}emwcpl_user_packages",
		array(
			'package_count' => $new_count,
		),
		array(
			'user_id' => $user_id,
			'id'      => $package_id,
		),
		array( '%d' ),
		array( '%d', '%d' )
	);
}

/**
 * Decrease event count for package
 *
 * @param  int $user_id
 * @param  int $package_id
 * @return int affected rows
 */
function wpem_paid_listings_decrease_package_count( $user_id, $package_id ) {
	global $wpdb;

	$packages = wpem_paid_listings_get_user_packages( $user_id );

	if ( isset( $packages[ $package_id ] ) ) {
		$new_count = $packages[ $package_id ]->package_count - 1;
	} else {
		$new_count = 0;
	}

	return $wpdb->update(
		"{$wpdb->prefix}emwcpl_user_packages",
		array(
			'package_count' => max( 0, $new_count ),
		),
		array(
			'user_id' => $user_id,
			'id'      => $package_id,
		),
		array( '%d' ),
		array( '%d', '%d' )
	);
}
