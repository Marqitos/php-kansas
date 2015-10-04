<?php

class Kansas_Application_Module_API
  extends Kansas_Application_Module_Abstract {
	
	private $_router;

	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
  public function getDefaultOptions() {
    return [
      'router' => [
        'basePath' => 'api'
      ]
    ];
  }
  
	public function appPreInit() { // añadir router
		global $application;
		$application->addRouter($this->getRouter());
	}

	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_API($this->getOptions('router'));
		return $this->_router;
	}
		
	public function getBasePath() {
		return $this->getOptions(['router', 'basePath']);
	}

	public function ApiMatch() {
		throw new System_NotSupportedException();
	}

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}

}