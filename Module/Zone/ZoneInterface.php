<?php

namespace Kansas\Module\Zone;

use Kansas\Module\ModuleInterface;

require_once 'Kansas/Module/ModuleInterface.php';

// Representa una zona
interface ZoneInterface extends ModuleInterface {
    // Obtiene la ruta inicial de la zona
    public function getBasePath();
}