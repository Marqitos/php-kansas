<?php
/**
 * Representa un proveedor MySql para acceso a datos
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Provider;
use function floatval;
use function intval;

abstract class AbstractDb {
	
	protected $db;
	protected $cache;
	
	protected function __construct() {
		global $application;
		$this->db = $application->getDb();
		$this->cache = $application->hasPlugin('BackendCache');
	}
	
	public function isInstalledDb($tableName, &$tableColumns) {
		// SHOW DATABASES
		// SHOW COLUMNS FROM mytable
	}
	
	public function installDb() {
		var_dump($this->db);
		//CREATE SCHEMA `mydb` DEFAULT CHARACTER SET utf8 ;
		
	}

    public static function intOrNull($value) {
        return $value == null
            ? null
            : intval($value);
    }

    public static function floatOrNull($value) {
        return $value == null
            ? null
            : floatval($value);
    }
	
}