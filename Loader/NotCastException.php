<?php

namespace Kansas\Loader;

use System\Collections\KeyNotFoundException;

/*
 * Excepción que se produce cuando no se puede encontrar un plugin.
 */
 
class NotCastException extends KeyNotFoundException {
	
	public function __construct($name, $type) {
		$message = "El Plugin de nombre '$name' no es del tipo esperado; se esperaba: '$type'";
		parent::__construct($message);
	}
	
}