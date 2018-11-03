<?php

namespace Kansas\Plugin\Zone;

use Kansas\Plugin\PluginInterface;

require_once 'Kansas/Plugin/PluginInterface.php';

// Representa una zona
interface ZoneInterface extends PluginInterface {
    // Obtiene la ruta inicial de la zona
    public function getBasePath();
}