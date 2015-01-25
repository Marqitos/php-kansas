<?php

abstract class Kansas_Controller_Abstract
	implements Kansas_Controller_Interface {
		
	private $_request;
		
	protected function getApplication() {
		return Kansas_Application::getInstance();
	}
	
	protected function createView() {
		global $view;
		global $application;
		if(!$view instanceof Zend_View_Abstract)
			$view = Kansas_Application::getInstance()->createView();
		$view->assign($this->_request->getParams());
		return $view;
	}
	protected function createPage($description = null, $keywords = null, Kansas_View_Page_Interface $parent = null) {
		$id;
		$router	= $this->getParam('router');
		$url		= trim($this->getRequest()->getPathInfo(), '/');
		$page		= $description instanceof Kansas_Post_Interface	? new Kansas_View_Page_Post($description, $parent, $router)
						//:	System_Guid::tryParse($keywords, $id)					?	new Kansas_View_Page_Db($id, $description, $url, $parent, $router)
																														: new Kansas_View_Page_Static($description, $keywords, $url,	$parent, $router);
															
		$this->_request->setParam('page', $page);
		return $this->createView();
	}
	
	public function init(Zend_Controller_Request_Http $request) {
		$this->_request = $request;
	}
	
	protected function getParam($key, $default = null) {
		return $this->_request->getParam($key, $default);
	}
	
	protected function getRequest() {
		return $this->_request;
	}
	
	protected function createResult($view, $defaultTemplate) {
		$template = $this->getParam('template', $defaultTemplate);
		$page			= $this->getParam('page');
		return $page != null?	new Kansas_View_Result_Page(		$view, $template, $page)
												:	new Kansas_View_Result_Template($view, $template);
	}
	protected function isCached($view, $defaultTemplate) {
		$template = $this->getParam('template', $defaultTemplate);
		return $view->isCached($template);
	}
	protected function isAuthenticated(&$result, $ru = null) {
		$auth			= Zend_Auth::getInstance();
		if($auth->hasIdentity()) {
			$result = $auth->getIdentity();
			return true;
		} else {
			if($ru  == null)
				$ru = $this->getRequest()->getRequestUri();
			$result	= new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru', $ru)));
			return false;
		}
	}
}