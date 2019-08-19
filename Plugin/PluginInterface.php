<?php

namespace Kansas\Plugin;
use System\Configurable\ConfigurableInterface;

require_once 'System/Configurable/ConfigurableInterface.php';

interface PluginInterface extends ConfigurableInterface {
	// Obtiene la versión del modulo cargado
	public function getVersion();
}