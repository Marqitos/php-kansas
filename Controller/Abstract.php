<?php

use Zend\Http\Request;

abstract class Kansas_Controller_Abstract
	implements Kansas_Controller_Interface {
		
	private $_request;
	private $_params;
		
	protected function createView() {
		global $view;
		global $application;
		if(!$view instanceof Zend_View_Interface)
			$view = $application->createView();
		$view->assign($this->_params);
		return $view;
	}
	protected function createPage($description = null, $keywords = null, Kansas_View_Page_Interface $parent = null) {
		$router	= $this->getParam('router');
		$page		= $description instanceof Kansas_Post_Interface	? new Kansas_View_Page_Post($description, $parent, $router)
						//:	System_Guid::tryParse($keywords, $id)					?	new Kansas_View_Page_Db($id, $description, $url, $parent, $router)
																														: new Kansas_View_Page_Static($description, $keywords, $this->getParam('url'),	$parent, $router);
															
		$this->_params['page'] = $page;
		return $this->createView();
	}
	
	public function init(Request $request, array $params) {
		$this->_request = $request;
		$this->_params 	= $params;
	}
	
	protected function getParam($key, $default = null) {
		return	isset($this->_params[$key])	? $this->_params[$key]
		//				$this->_request->getParam($key, $default);				 
																				: $default;
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