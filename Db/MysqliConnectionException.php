<?php
/**
 * Representa un error producido durante una consulta MySQL mediante mysqli;
 */


namespace Kansas\Db;

use Exception;
use mysqli;

class MysqliConnectionException extends Exception {
		
	public function __construct(mysqli $link = null) {
        if($link == null) {
            parent::__construct('Error de Conexión con la base de datos.');
        } else {
            parent::__construct('Error de Conexión (' . $link->connect_errno . ') ' . $link->connect_error);
        }
	}
		

}