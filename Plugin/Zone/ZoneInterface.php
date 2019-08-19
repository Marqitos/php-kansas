<?php

namespace Kansas\Plugin\Zone;

use Kansas\Plugin\PluginInterface;

require_once 'Kansas/Plugin/PluginInterface.php';

/**
 * Representa un plugin de zona
 */
interface ZoneInterface extends PluginInterface {
    /**
     * Obtiene la ruta inicial de la zona
     *
     * @return string con la ruta inicial
     */
    public function getBasePath();
}