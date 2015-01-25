<?php


abstract class Kansas_Db {
	
	protected $db;
	
	protected function __construct(Zend_Db_Adapter_Abstract $db) {
		$this->db = $db;
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