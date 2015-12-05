<?php


class Kansas_Application_Module_RedirectionError
  extends Kansas_Application_Module_Abstract {

	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
		$application->set('error', [$this, "errorManager"]);
	}
  
  public function getDefaultOptions() {
    return [
      'basePath' => false,
      'append'   => true
    ];
  }
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	  
  
  public function errorManager($params) {
    global $application;
    if($params['code'] == 404 && $path = $this->getOptions('basePath')) {
      global $environment;
      if($this->getOptions('append'))
        $path = rtrim($path, '/') . $environment->getRequest()->getRequestUri();
      $result = Kansas_View_Result_Redirect::gotoUrl($path);
      $result->executeResult();
    } else {
      $application->errorManager($params);
    }
  }
  
}