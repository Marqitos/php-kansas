<?php

abstract class Kansas_Application_Module_Zone_Abstract
  extends Kansas_Application_Module_Abstract
  implements Kansas_Application_Module_Zone_Interface {
  
  /// Campos
  protected $zones;
  
  /// Contructor
  protected function __construct($options, $default) {
    parent::__construct($options, $default);
		global $application;
    $this->zones = $application->getModule('zones');
    $this->zones->addZone($this);
	}
  
  /// Miembros de Kansas_Application_Module_Zone_Interface
  // Obtiene la ruta inicial de la zona  
  public function getBasePath() {
    return $this->getOptions('basePath');
  }
    
}