<?php

require_once 'Kansas/Module/Zone/Abstract.php';

class Kansas_Module_API
  extends Kansas_Module_Zone_Abstract {
	
	private $_router;

  /// Constructor
	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
    $application->registerCallback('preinit', [$this, 'appPreInit']);
	}
 
  /// Miembros de System_Configurable_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [
          'base_path' => 'api',
          'params' => []
        ];
      default:
        require_once 'System/NotSupportedException.php';
        throw new System_NotSupportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
	public function appPreInit() { // añadir router
		global $application;
    if($this->zones->getZone() instanceof Kansas_Module_API) {
      $application->addRouter($this->getRouter());
    }
	}

	public function getRouter() {
		if($this->_router == null) {
			require_once 'Kansas/Router/API.php';
			$this->_router = new Kansas_Router_API([$this->options]);
		}
		return $this->_router;
	}

}