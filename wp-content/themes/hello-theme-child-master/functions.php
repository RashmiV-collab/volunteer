<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts()
{
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20);

/* add_action('wp_footer', 'check_user');
function check_user(){
	if ( is_user_logged_in() ) {
		
		echo '<pre style="display:none;">';
		echo 'User ID: ' . get_current_user_id();
		$all_meta_for_user = get_user_meta( get_current_user_id() );
		echo get_user_meta( get_current_user_id(), 'ff_profile_company', true );
		  print_r( $all_meta_for_user );
		print_R(get_post_meta(352));
		echo '</pre>';
	}
} */


/**
 * Remove the order notes field from checkout.
 */
function devpress_remove_checkout_phone_field($fields)
{
	unset($fields['order']['order_comments']);
	return $fields;
}
add_filter('woocommerce_checkout_fields', 'devpress_remove_checkout_phone_field');

