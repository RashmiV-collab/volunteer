<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option('event_manager_google_recaptcha_site_key');
delete_option('event_manager_google_recaptcha_secret_key');