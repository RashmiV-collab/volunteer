<?php

namespace CronLogger;


class Timer {

	private $start;

	public function __construct() {
		$this->start = time();
	}

	function getStart() {
		return $this->start;
	}

	function getDuration() {
		return time() - $this->start;
	}

}