<?php

namespace Kansas\Controller;
use Kansas\Controller\ControllerInterface;
use Kansas\View\Result\Template;
use Kansas\View\Result\Redirect;
use System\NotImplementedException;
use function is_callable;
use function get_class;
use function array_merge;

require_once 'Kansas/Controller/ControllerInterface.php';

abstract class AbstractController implements ControllerInterface {
		
	private $_params;
		
	public function init(array $params) {
		$this->_params 	= $params;
	}

	public function callAction ($action, array $vars) {
		if(!is_callable([$this, $action])) {
			require_once 'System/NotImplementedException.php';
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

	public static function getIdentity(array $vars) {
		global $application;
		if(isset($vars['identity']))
			return $vars['identity'];
		return $application
			->getPlugin('Auth')
			->getSession()
			->getIdentity();
	}

	protected function createViewResult($defaultTemplate, array $data = [], $mimeType = 'text/html') {
		require_once 'Kansas/View/Result/Template.php';
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
		global $application, $environment;
		$auth = $application->getPlugin('auth');
		if($auth->hasIdentity()) {
			$result = $auth->getIdentity();
			return true;
		} else {
			require_once 'Kansas/View/Result/Redirect.php';
			if($ru === null)
				$ru = $environment->getRequest()->getRequestUri();
			$result = Redirect::gotoUrl(
				$auth->getRouter()->assemble(['action' => 'signin', 'ru' => $ru])
			);
			return false;
		}
	}
}