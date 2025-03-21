<?php

namespace WPDataAccess\Utilities {

	use WPDataAccess\WPDA;

	class WPDA_Remote_Call {

		public static function post( $url, $body, $die = false, $headers = array() ) {
			$response = wp_remote_post(
				$url,
				array(
					'headers' => $headers,
					'body'    => $body,
					'timeout' => 60,
				)
			);
			// var_dump($response);

			if ( is_wp_error( $response ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [' . json_encode( $response ) . ']' );
				}

				return $response->get_error_message();
			}

			if ( ! isset( $response['response'], $response['body'] ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [missing response|body]' );
				}

				return false;
			}

			return $response;
		}

		public static function get( $url, $args = array(), $die = false ) {
			$response = wp_remote_get( $url, $args );
			// var_dump($response);

			if ( is_wp_error( $response ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [' . json_encode( $response ) . ']' );
				}

				return false;
			}

			if ( ! isset( $response['response'], $response['body'] ) ) {
				WPDA::wpda_log_wp_error( json_encode( $response ) );
				if ( $die ) {
					wp_die( 'ERROR: Remote call failed [missing response|body]' );
				}

				return false;
			}

			return $response;
		}

		public static function max_size() {
			$max  = ini_get('post_max_size');
			$unit = $max[ strlen( $max ) - 1 ];
			$max  = substr( $max, 0, strlen( $max ) - 1 );

			switch($unit) {
				case 'G':
					$max *= 1024;
				case 'M':
					$max *= 1024;
				case 'K':
					$max *= 1024;
			}

			return $max;
		}

	}

}