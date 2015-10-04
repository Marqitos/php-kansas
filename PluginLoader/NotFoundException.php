<?php

require_once 'System/Collections/KeyNotFoundException.php';

/*
 * Excepción que se produce cuando no se puede encontrar un plugin.
 */
 
class Kansas_PluginLoader_NotFoundException
	extends System_Collections_KeyNotFoundException {
	
	public function __construct($name, array $registry) {
		$message = "El Plugin de nombre '$name' no se encuentra en el registro; usando las rutas:";
		foreach ($registry as $prefix => $paths)
			$message .= "\n$prefix: " . implode(PATH_SEPARATOR, $paths);
			
		parent::__construct($message);
	}
	
}