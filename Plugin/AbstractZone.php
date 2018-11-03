<?php
namespace Kansas\Plugin;

use System\Configurable;
use Kansas\Plugin\Zone\ZoneInterface;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/Zone/ZoneInterface.php';

abstract class AbstractZone extends Configurable implements ZoneInterface {
  
  /// Campos
  protected $zones;
  
  /// Contructor
  public function __construct($options) {
    parent::__construct($options);
		global $application;
    $this->zones = $application->getModule('zones');
    $this->zones->addZone($this);
	}
  
  /// Miembros de ZoneInterface
  // Obtiene la ruta inicial de la zona  
  public function getBasePath() {
    return $this->options['base_path'];
  }
    
}