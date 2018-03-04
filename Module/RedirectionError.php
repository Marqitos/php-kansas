<?php
require_once 'System/Configurable/Abstract.php';

class Kansas_Module_RedirectionError
  extends System_Configurable_Abstract {

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
        throw new System_NotSuportedException("Entorno no soportado [$environment]");
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
      $result = Kansas_View_Result_Redirect::gotoUrl($path);
      $result->executeResult();
    } else {
      $application->errorManager($params);
    }
  }
  
}