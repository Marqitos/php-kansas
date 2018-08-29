<?php

namespace Kansas\Module;

use System\Configurable;
use Kansas\Module\ModuleInterface;
use System\NotSuportedException;
use System\String;
use Kansas\Module\Zone\ZoneInterface;

require_once 'Kansas/Module/Zone/ZoneInterface.php';
require_once 'System/Configurable.php';
require_once 'Kansas/Module/ModuleInterface.php';

class Zones extends Configurable implements ModuleInterface {
  
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
        throw new NotSuportedException("Entorno no soportado [$environment]");
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
        if(String::startWith($path, $zone->getBasePath())) {
          $this->_zone = $zone;
          break;
        }
      }
    }
    return $this->_zone;
  }
  
  // Agrega una nueva zona
  public function addZone(ZoneInterface $zone) {
    $this->_zones[$zone->getBasePath()] = $zone;
    if($this->_zone === FALSE) unset($this->_zone);
  }

}