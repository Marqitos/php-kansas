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
	
	/**
	 * Crea capa de acceso a base de datos,
	 * usando la configuración de conexión definida por la aplicación,
	 * y el cache en caso de estar configurado
	 */
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

	/**
	 * Devuelve int o null del valor indicado
	 * 
	 * @param mixed valor a analizar
	 * @return int|null numero entero en caso de que no sea null, y se pueda evaluar como tal
	 */
    public static function intOrNull($value) : ?int {
        return $value == null
            ? null
            : intval($value);
    }

	/**
	 * Devuelve float o null del valor indicado
	 * 
	 * @param mixed valor a analizar
	 * @return float|null numero en caso de que no sea null, y se pueda evaluar como tal
	 */
    public static function floatOrNull($value) : ?float {
        return $value == null
            ? null
            : floatval($value);
    }
	
}