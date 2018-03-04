<?php
require_once 'Kansas/Module/Interface.php';

// Representa una zona
interface Kansas_Module_Zone_Interface
  extends Kansas_Module_Interface {
    // Obtiene la ruta inicial de la zona
    public function getBasePath();
}