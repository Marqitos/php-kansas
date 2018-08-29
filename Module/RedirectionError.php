<?php
namespace Kansas\Module;

use System\Configurable;
use System\NotSuportedException;
use Kansas\Module\ModuleInterface;
use Kansas\View\Result\Redirect;

require_once 'System/Configurable.php';
require_once 'Kansas/Module/ModuleInterface.php';

class RedirectionError extends Configurable implements ModuleInterface {

	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
		$application->set('error', [$this, 'errorManager']);
	}
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [
          'basePath' => false,
          'append'   => true
        ];
      default:
        require_once 'System/NotSuportedException.php';
        throw new NotSuportedException("Entorno no soportado [$environment]");
    }
  }
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	  
  
  public function errorManager($params) {
    global $application;
    if($params['code'] == 404 && $path = $this->options['basePath']) {
      global $environment;
      if($this->options['append'])
        $path = rtrim($path, '/') . $environment->getRequest()->getRequestUri();
      require_once 'Kansas/View/Result/Redirect.php';
      $result = Redirect::gotoUrl($path);
      $result->executeResult();
    } else {
      $application->errorManager($params);
    }
  }
  
}