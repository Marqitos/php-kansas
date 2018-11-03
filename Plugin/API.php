<?php

namespace Kansas\Plugin;

use Kansas\Plugin\AbstractZone;
use System\NotSupportedException;
use Kansas\Router\API as RouterAPI;

require_once 'Kansas/Plugin/AbstractZone.php';

class API extends AbstractZone {
	
	private $router;

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
        throw new NotSupportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
	public function appPreInit() { // añadir router
		global $application;
    if($this->zones->getZone() instanceof Kansas\Plugin\API) {
      $application->addRouter($this->getRouter());
    }
	}

	public function getRouter() {
		if($this->router == null) {
			require_once 'Kansas/Router/API.php';
			$this->router = new RouterAPI($this->options);
		}
		return $this->router;
	}

}