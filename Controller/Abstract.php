<?php

abstract class Kansas_Controller_Abstract
	implements Kansas_Controller_Interface {
		
	private $_params;
		
	public function init(array $params) {
		$this->_params 	= $params;
	}
	
	protected function getParam($key, $default = null) {
		return	isset($this->_params[$key])	? $this->_params[$key] :
					 (isset($_REQUEST[$key])      ? $_REQUEST[$key]				 
																				: $default);
	}
	
	protected function createViewResult($defaultTemplate, array $data = [], $mimeType = 'text/html') {
    global $application;
    $view = $application->getView();
		$template = $view->createTemplate($this->getParam('template', $defaultTemplate), array_merge($this->_params, $data));
		return new Kansas_View_Result_Template($template, $mimeType);
	}
	protected function isCached($defaultTemplate) {
    global $view;
		$template = $this->getParam('template', $defaultTemplate);
		return $view->isCached($template);
	}
	protected function isAuthenticated(&$result, $ru = null) {
		global $application;
    global $environment;
		$auth			= $application->getModule('auth');
		if($auth->hasIdentity()) {
			$result = $auth->getIdentity();
			return true;
		} else {
			if($ru  == null)
				$ru = $environment->getRequest()->getRequestUri();
			$result	= new Kansas_View_Result_Redirect();
			$result->setGotoUrl(
				$auth->getRouter()->assemble(['action' => 'signin', 'ru' => $ru])
			);
			return false;
		}
	}
}