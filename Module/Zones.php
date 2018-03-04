<?php

class Kansas_Module_Zones
	extends System_Configurable_Abstract
  implements Kansas_Module_Interface {
  
  /// Campos
  private $_zones = [];
  private $_zone;

  /// Constructor
  public function __construct(array $options) {
    parent::__construct($options);
  }
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [];
      default:
        require_once 'System/NotSuportedException.php';
        throw new System_NotSuportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  /// Metodos publicos
  // Obtiene la zona actual
  public function getZone() {
    if($this->_zone === NULL) {
      global $environment;
      $path = trim($environment->getRequest()->getUri()->getPath(), '/');
      $this->_zone = false;
      foreach($this->_zones as $zone) {
        if(System_String::startWith($path, $zone->getBasePath())) {
          $this->_zone = $zone;
          break;
        }
      }
    }
    return $this->_zone;
  }
  
  // Agrega una nueva zona
  public function addZone(Kansas_Module_Zone_Interface $zone) {
    $this->_zones[$zone->getBasePath()] = $zone;
    if($this->_zone === FALSE) unset($this->_zone);
  }

}