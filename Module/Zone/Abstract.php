<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/Module/Zone/Interface.php';

abstract class Kansas_Module_Zone_Abstract
  extends System_Configurable_Abstract
  implements Kansas_Module_Zone_Interface {
  
  /// Campos
  protected $zones;
  
  /// Contructor
  public function __construct($options) {
    parent::__construct($options);
		global $application;
    $this->zones = $application->getModule('zones');
    $this->zones->addZone($this);
	}
  
  /// Miembros de Kansas_Module_Zone_Interface
  // Obtiene la ruta inicial de la zona  
  public function getBasePath() {
    return $this->options['base_path'];
  }
    
}