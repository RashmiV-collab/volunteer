<?php

namespace CronLogger\Services;


use CronLogger\Plugin;

class SolrPlugin {
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( "solr_cron_start", array( $this, "onStart" ) );
		add_action( "solr_cron_finish", array( $this, "onFinish" ) );
	}

	function onStart() {
		$this->plugin->log->start( 'Solr cron.php' );
		$this->plugin->log->addInfo( "Solr cron.php starts" );
	}

	function onFinish() {
		$this->plugin->log->update( $this->plugin->timer->getDuration(), "Solr finished 🔎 🎉" );
	}
}