<?php

class Kansas_Application_Module_Messages
	extends Kansas_Application_Module_Abstract {

	private $_router;

	public function __construct(array $options) {
		parent::__construct($options);
		global $application;
    $this->options = array_replace_recursive([
      'router' => [
        'basepath' => 'contacto'
      ]
    ], $options);
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir router
		global $application;
		$application->addRouter($this->getRouter());
	}
	
	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_Messages($this->options['router']);
		return $this->_router;
	}
	
	public function getBasePath() {
		return $this->options['router']['basePath'];
	}

	public function fillContactForm(Kansas_Request $request, Zend_View_Interface $view, System_Guid $target) {
		$error = isset($_REQUEST['err']) ? (int)$_REQUEST['err'] : null;
		$view->assign('msg',		Bioter_Model_Message::getModel($mId));
		$view->assign('error',	$error);
		$view->assign('target',	$target->getHex());
		$view->assign('action',	'/mensajes/enviar');
	}

	public function ApiMatch() {
		$apiRouter = new Kansas_Router_API_Messages();
		$apiRouter->setBasePath("api/messages");
		return $apiRouter->match();
	}
	
}