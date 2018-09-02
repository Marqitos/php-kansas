<?php

namespace Kansas\Module;
use System\Configurable\ConfigurableInterface;

require_once 'System/Configurable/ConfigurableInterface.php';

interface ModuleInterface extends ConfigurableInterface {
	// Obtiene la versión del modulo cargado
	public function getVersion();
}