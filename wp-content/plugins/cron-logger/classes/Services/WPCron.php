<?php

namespace CronLogger\Services;


use CronLogger\Plugin;

class WPCron {

	var $times = array();

	public function __construct( Plugin $plugin ) {

		$this->log   = $plugin->log;
		$this->timer = $plugin->timer;

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			add_action( "plugins_loaded", array( $this, "start" ) );
			add_action( "shutdown", array( $this, "shutdown" ) );

			// publish posts schedule logs
			add_action( 'publish_future_post', array( $this, 'publish_future_post_start' ), 1, 1 );
			add_action( 'publish_future_post', array( $this, 'publish_future_post_finish' ), 100, 1 );
		}
	}

	function start() {
		do_action( Plugin::ACTION_WP_CRON_START );
		$this->log->start( "wp-cron.php" );
		$this->addCronActions();
	}

	function shutdown() {
		$this->log->update( $this->timer->getDuration(), 'Done wp-cron.php 🎉 ' );
		do_action( Plugin::ACTION_WP_CRON_FINISH );
		$this->log->clean();
	}

	function addCronActions() {
		$crons      = _get_cron_array();
		$registered = array();

		foreach ( $crons as $timestamp => $cronhooks ) {
			foreach ( $cronhooks as $hook => $keys ) {
				add_action( $hook, array( $this, "before_execute_cron_hook" ), 0 );
				add_action( $hook, array( $this, "after_execute_cron_hook" ), 999 );
				$registered[] = $hook;
			}
		}

		$msg = __( "No registered hooks? Something went wrong. There should be at least WordPress core cron hooks.", Plugin::DOMAIN );
		if ( count( $registered ) > 0 ) {
			$msg = sprintf( __( "Registered hooks: %s", Plugin::DOMAIN ), implode( ', ', $registered ) );
		}
		$this->log->addInfo( $msg );
	}

	function before_execute_cron_hook() {
		$this->times[ current_filter() ] = time();
		$this->log->addInfo( "Starts " . current_filter() );
	}

	function after_execute_cron_hook() {
		$this->log->addInfo( "Finished " . current_filter(), time() - $this->times[ current_filter() ] );
	}

	/**
	 * start future post schedule
	 *
	 * @param int $post_id
	 */
	public function publish_future_post_start( $post_id ) {
		$this->log->addInfo( "Check post -> $post_id" );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
	}

	/**
	 * after future post schedule finished
	 *
	 * @param $post
	 */
	public function publish_future_post_finish( $post ) {
		remove_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10 );
	}

	/**
	 * log which posts were published
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param \WP_Post $post
	 */
	function transition_post_status( $new_status, $old_status, $post ) {
		$this->log->addInfo(
			"Status changed from <b>$old_status</b> -> <b>$new_status</b> of '{$post->post_title}'"
		);
	}

}

