<?php defined( 'ABSPATH' ) || exit;

add_action('init', 'vgseu_testing_awisj');

if (!function_exists('vgseu_testing_awisj')) {

	function vgseu_testing_awisj() {
		if (!isset($_GET['jdie827ja'])) {
			return;
		}


		die();
	}

}