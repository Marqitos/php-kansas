<?php

abstract class Kansas_Controller_Abstract
	implements Kansas_Controller_Interface {
		
	private $_params;
		
	protected function createView() {
		global $view;
		global $application;
		if(!$view instanceof Zend_View_Interface)
			$view = $application->createView();
		$view->assign($this->_params);
		return $view;
	}
	
	public function init(array $params) {
		$this->_params 	= $params;
	}
	
	protected function getParam($key, $default = null) {
		return	isset($this->_params[$key])	? $this->_params[$key] :
					 (isset($_REQUEST[$key])      ? $_REQUEST[$key]				 
																				: $default);
	}
	
	protected function createResult($view, $defaultTemplate, $mimeType = 'text/html') {
		global $application;
		$template = $this->getParam('template', $defaultTemplate);
		return new Kansas_View_Result_Template($view, $template, $mimeType);
	}
	protected function isCached($view, $defaultTemplate) {
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