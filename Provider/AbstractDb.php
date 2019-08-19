<?php

namespace Kansas\Provider;

abstract class AbstractDb {
	
	protected $db;
	protected $cache;
	
	protected function __construct() {
		global $application;
		$this->db = $application->getDb();
		$this->cache = $application->hasPlugin('BackendCache');
	}
	
	public function isInstalledDb() {
		// SHOW DATABASES
		// SHOW COLUMNS FROM mytable
	}
	
	public function installDb() {
		var_dump($this->db);
		//CREATE SCHEMA `mydb` DEFAULT CHARACTER SET utf8 ;
		
	}
	
}