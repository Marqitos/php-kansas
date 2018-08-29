<?php

namespace Kansas\Db;

abstract class AbstractDb {
	
	protected $db;
	protected $cache;
	
	protected function __construct() {
		global $application;
		$this->db = $application->getDb();
		$this->cache = $application->hasModule('BackendCache')
			? $application->getModule('BackendCache')
			: false;
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