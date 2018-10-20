<?php

namespace Kansas\Controller;
use Kansas\Controller\AbstractController;
use Kansas\View\Result\Redirect;
use Kansas\View\Result\File;
use System\NotImplementedException;
use System\ArgumentOutOfRangeException;
use System\ArgumentNullException;

require_once 'Kansas/Controller/AbstractController.php';

class Index	extends AbstractController {
	
	private static $actions = [];

	public function callAction ($action, array $vars) {
		if(is_callable([$this, $action]))
			return $this->$action($vars);
		if(isset(self::$actions[$action]))
			return call_user_func(self::$actions[$action], $this, $vars);
		throw new NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($this));
	}

	public static function addAction($actionName, callable $callback) {
		if(!is_string($actionName)) {
			throw new ArgumentOutOfRangeException('actionName');
		}
		self::$actions[$actionName] = $callback;
	}

	public function template(array $vars = []) {
		if(!isset($vars['template'])) {
			throw new ArgumentNullException('vars["template"]');
		}
		$template = $vars['template'];
		unset($vars['template']);
		return $this->createViewResult($template, $vars);
	}
	
	public function redirection(array $vars = []) {
		if(!isset($vars['gotoUrl'])) {
			throw new ArgumentNullException('vars["gotoUrl"]');
		}
		
		return Redirect::gotoUrl($vars['gotoUrl']);
	}
	
	public function css(array $vars) {
		require_once 'Kansas/View/Result/Css.php';
		global $application;
		$files = $vars['files'];
		$cssResult = new Kansas_View_Result_Css($files);
		$backendCache = $application->getModule('BackendCache');
		if($backendCache) {
			$cacheId = $cssResult->getCacheId();
			if($backendCache->test($cacheId)) {
				$content = $backendCache->load($cacheId);
			} else {
				$content = $cssResult->getResult();
				$backendCache->save($content, $cacheId);
			}
			$contentResult = new Kansas_View_Result_Content($content);
			$contentResult->setMimeType('text/css');
			return $contentResult;
		} else
			return $cssResult;
	}

	
	
	public function file(array $vars) {
		require_once 'Kansas/View/Result/File.php';
		return new File($vars['file']);
	}
  
	public function clearCache(array $vars) {
		global $application;
		$application->getView()->getEngine()->clearAllCache();

		return Redirect::gotoUrl($this->getParam('ru', '/'));
	}
	
	public function phpInclude(array $vars) {
		return new Kansas_View_Result_Include($vars['file']);
	}
	
}