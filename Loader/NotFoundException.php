<?php

namespace Kansas\Loader;

use System\Collections\KeyNotFoundException;

/*
 * Excepción que se produce cuando no se puede encontrar un plugin.
 */
 
class NotFoundException extends KeyNotFoundException {
	
	public function __construct($name, array $registry) {
		$message = "El Plugin de nombre '$name' no se encuentra en el registro; usando las rutas:";
		foreach ($registry as $prefix => $paths)
			$message .= "\n$prefix: " . implode(PATH_SEPARATOR, $paths);
			
		parent::__construct($message);
	}
	
}