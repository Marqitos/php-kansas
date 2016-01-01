<?php

class Kansas_Application_Module_Zones
	extends Kansas_Application_Module_Abstract {
  
  private $_basePath;
  private $_zone = 'default';

  public function __construct(array $options) {
    global $environment;
    parent::__construct($options, __FILE__);
 		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    $default = false;
    foreach($this->getOptions() as $index => $basePath) {
      if($index != 'default' &&
         Kansas_String::startWith($path, $basePath)) {
        $this->_zone = $index;
        $this->_basePath = $basePath;
        break;
      }
    }
  }
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
    
  public function getZone() {
    return $this->_zone;
  }
  public function getBasePath() {
    return $this->_basePath;
  }
}