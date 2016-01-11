<?php

class Kansas_Application_Module_Zones
	extends Kansas_Application_Module_Abstract {
  
  /// Campos
  private $_zones = [];
  private $_zone;

  /// Constructor
  public function __construct(array $options) {
    parent::__construct($options, __FILE__);
  }
  
  /// Miembros de Kansas_Application_Module_Interface
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  /// Metodos publicos
  // Obtiene la zona actual
  public function getZone() {
    return $this->_zone;
  }
  
  // Agrega una nueva zona
  public function addZone(Kansas_Application_Module_Zone_Interface $zone) {
    global $environment;
    $this->_zones[$zone->getBasePath()] = $zone;
    $path = trim($environment->getRequest()->getUri()->getPath(), '/');
    if(Kansas_String::startWith($path, $zone->getBasePath()))
      $this->_zone = $zone;
  }

}