<?php

class Kansas_Application_Module_API {
	
	private $_router;
  protected $options;

	public function __construct(array $options) {
    $this->options = $options;
		global $application;
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir router
		global $application;
		$application->addRouter($this->getRouter());
	}

	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_API($this->options['router']);
		return $this->_router;
	}
		
	public function getBasePath() {
		return $this->options['router']['basePath'];
	}

	public function ApiMatch() {
		throw new System_NotSupportedException();
	}


}