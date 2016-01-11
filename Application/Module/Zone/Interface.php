<?php

// Representa una zona
interface Kansas_Application_Module_Zone_Interface
  extends Kansas_Application_Module_Interface {
    // Obtiene la ruta inicial de la zona
    public function getBasePath();
}