<?php

namespace CronLogger;


use CronLogger\Services\SolrPlugin;
use CronLogger\Services\WPCron;

/**
 * @property WPCron wp_cron
 * @property SolrPlugin solr
 */
class Services {
	public function __construct( Plugin $plugin ) {

		$this->wp_cron = new WPCron( $plugin );
		$this->solr = new SolrPlugin( $plugin );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

	}

	function plugins_loaded() {
		do_action( Plugin::ACTION_INIT, Plugin::instance() );
	}

}