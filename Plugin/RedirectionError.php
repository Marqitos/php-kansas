<?php
namespace Kansas\Plugin;

use System\Configurable;
use System\NotSuportedException;
use Kansas\Plugin\PluginInterface;
use Kansas\View\Result\Redirect;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class RedirectionError extends Configurable implements PluginInterface {

  private $next;

	public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    try {
      $this->next = $application->getOptions()['error'];
    } catch(Exception $ex) {
      $this->next = [$application, 'errorManager'];
    }
		$application->setOption('error', [$this, 'errorManager']);
	}
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [
          'basePath' => '',
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
      call_user_func($this->next, $params);
    }
  }
  
}