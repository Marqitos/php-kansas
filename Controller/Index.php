<?php
require_once 'Kansas/Controller/Abstract.php';

class Kansas_Controller_Index
	extends Kansas_Controller_Abstract {
	
	private static $actions = [];

	public function callAction ($action, array $vars) {
		if(is_callable([$this, $action]))
			return $this->$action($vars);
		if(isset(self::$actions[$action]))
			return call_user_func(self::$actions[$action], $this, $vars);
		throw new System_NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($this));
	}

	public static function addAction($actionName, $callback) {
		if(!is_string($actionName)) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new System_ArgumentOutOfRangeException('actionName');
		}
		if(!is_callable($callback)) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new System_ArgumentOutOfRangeException('callback');
		}
		self::$actions[$actionName] = $callback;
	}

	public function template(array $vars = []) {
		if(!isset($vars['template'])) {
			require_once 'System/ArgumentNullException.php';
			throw new System_ArgumentNullException('vars["template"]');
		}
		$template = $vars['template'];
		unset($vars['template']);
		return $this->createViewResult($template, $vars);
	}
	
	public function redirection(array $vars = []) {
		if(!isset($vars['gotoUrl'])) {
			require_once 'System/ArgumentNullException.php';
			throw new System_ArgumentNullException('vars["gotoUrl"]');
		}
		return Kansas_View_Result_Redirect::gotoUrl($vars['gotoUrl']);
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
		return new Kansas_View_Result_File($vars['file']);
	}
  
	public function clearCache(array $vars) {
		global $application;
		$application->getView()->getEngine()->clearAllCache();

		return Kansas_View_Result_Redirect::gotoUrl($this->getParam('ru', '/'));
	}
	
	public function phpInclude(array $vars) {
		return new Kansas_View_Result_Include($vars['file']);
	}
	
}