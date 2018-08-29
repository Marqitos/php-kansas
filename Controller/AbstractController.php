<?php

namespace Kansas\Controller;
use Kansas\Controller\ControllerInterface;
use Kansas\View\Result\Template;
use Kansas\View\Result\Redirect;
use System\NotImplementedException;
use function is_callable;

abstract class AbstractController implements ControllerInterface {
		
	private $_params;
		
	public function init(array $params) {
		$this->_params 	= $params;
	}

	public function callAction ($action, array $vars) {
		if(!is_callable([$this, $action])) {
			throw new NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($this));
		}
		return $this->$action($vars);
	}
	
	public function getParam($key, $default = null) {
		return	isset($this->_params[$key])
			? $this->_params[$key]
			: (isset($_REQUEST[$key])
				? $_REQUEST[$key]
				: $default);
	}

	protected function createViewResult($defaultTemplate, array $data = [], $mimeType = 'text/html') {
		global $application;
		$view = $application->getView();
		$template = $view->createTemplate($this->getParam('template', $defaultTemplate), array_merge($this->_params, $data));
		return new Template($template, $mimeType);
	}
	protected function isCached($defaultTemplate) {
		global $view;
		$template = $this->getParam('template', $defaultTemplate);
		return $view->isCached($template);
	}
	
	protected function isAuthenticated(&$result, $ru = null) {
		global $application;
		$auth = $application->getModule('auth');
		if($auth->hasIdentity()) {
			$result = $auth->getIdentity();
			return true;
		} else {
			global $environment;
			if($ru  == null)
				$ru = $environment->getRequest()->getRequestUri();
			Redirect::gotoUrl(
				$auth->getRouter()->assemble(['action' => 'signin', 'ru' => $ru])
			);
			return false;
		}
	}
}