<?php

namespace CronLogger;

use wpdb;

/**
 * @property wpdb $wpdb
 * @property string $table
 */
class Log {

	private $plugin;
	private $log_id = null;
	private $errors = array();

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table = $this->wpdb->prefix . Plugin::TABLE_LOGS;
	}

	function start( $info = "" ) {
		if ( $this->log_id != null ) {
			error_log( "Only start logger once per session.", 4 );

			return;
		}
		$this->wpdb->insert(
			$this->table,
			array(
				'executed' => $this->plugin->timer->getStart(),
				'duration' => 0,
				'info'     => "Running ⏳ $info",
			),
			array(
				'%d',
				'%d',
				'%s',
			)
		);
		$this->log_id = $this->wpdb->insert_id;
	}

	function update( $duration, $info = null ) {

		if ( $this->log_id == null ) {
			$this->start();
		}
		$data        = array( 'duration' => $duration );
		$data_format = array( '%d' );
		if ( $info != null ) {
			$data['info']  = $info;
			$data_format[] = '%s';
		}

		return $this->wpdb->update(
			$this->table,
			$data,
			array(
				'id' => $this->log_id,
			),
			$data_format,
			array(
				'%d',
			)
		);
	}

	function addInfo( $message, $duration = null ) {
		$result = $this->wpdb->insert(
			$this->table,
			array(
				'parent_id' => $this->log_id,
				'info'      => $message,
				'executed'  => time(),
				'duration'  => $duration,
			),
			array(
				'%d',
				'%s',
				'%d',
				'%d',
			)
		);
		if ( $result == false ) {
			echo $this->wpdb->last_query;
			$error_message  = "🚨 " . $this->wpdb->last_query;
			$this->errors[] = $error_message;
			error_log( "Cron Logger: " . $error_message );
		} else {
			$this->update(
				$this->plugin->timer->getDuration()
			);
		}

	}

	function getList( $args = array() ) {
		$args = (object) array_merge(
			array(
				"count"       => 15,
				"page"        => 1,
				"min_seconds" => null,
			),
			$args
		);
		$count  = $args->count;
		$page   = $args->page;
		$offset = $count * ( $page - 1 );

		$where_min_seconds = ( $args->min_seconds != null ) ? "AND duration >= " . $args->min_seconds : "";

		return $this->wpdb->get_results(
			"SELECT * FROM " . $this->table . " WHERE parent_id IS NULL " . $where_min_seconds . " ORDER BY executed DESC LIMIT $offset, $count"
		);
	}

	function getSublist( $log_id, $count = 50, $page = 0 ) {
		$offset = $count * $page;

		return $this->wpdb->get_results(
			"SELECT * FROM " . $this->table . " WHERE parent_id = $log_id  ORDER BY id DESC LIMIT $offset, $count"
		);
	}

	function clean() {
		$table     = $this->table;
		$days      = apply_filters( Plugin::FILTER_EXPIRE, 14 );
		$parentIds = "SELECT id FROM (" .
		             "SELECT id FROM " . $this->table . " WHERE " .
		             "parent_id IS NULL AND " .
		             "executed < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $days day))" .
		             ") as parent_id";

		$this->wpdb->query( "DELETE FROM $table WHERE parent_id IN ($parentIds)" );
		$this->wpdb->query( "DELETE FROM $table WHERE id IN ($parentIds)" );
	}

	function createTable() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( "CREATE TABLE IF NOT EXISTS " . $this->table . " 
		(
		 id bigint(20) unsigned not null auto_increment,
		 parent_id bigint(20) unsigned default null,
		 executed bigint(20) unsigned default null ,
		 duration int(11) unsigned default null,
		 info text,
		 primary key (id),
		 key ( executed ),
		 key (duration),
		 key (parent_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );
	}
}
