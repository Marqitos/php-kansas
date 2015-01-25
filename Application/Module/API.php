<?php

require_once('Kansas/Application/Module/Abstract.php');

class Kansas_Application_Module_API
	extends Kansas_Application_Module_Abstract {
	
	private $_router;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		global $application;
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir router
		global $application;
		$application->getRouter()->addRouter($this->getRouter());
	}

	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_API($this->options->router);
		return $this->_router;
	}
		
	public function getBasePath() {
		return $this->options->router->basePath;
	}

	public function ApiMatch(Zend_Controller_Request_Abstract $request) {
		throw new System_NotSupportedException();
	}


}