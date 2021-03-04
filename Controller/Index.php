<?php
/**
 * Controlador principal en una aplicación con patrón MVC
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.1
 */

namespace Kansas\Controller;
use Kansas\Controller\AbstractController;
use Kansas\View\Result\Redirect;
use Kansas\View\Result\File;
use Kansas\View\Result\Json;
use System\NotImplementedException;
use System\ArgumentOutOfRangeException;
use System\ArgumentNullException;

use function call_user_func;
use function get_class;
use function header;
use function http_response_code;
use function is_callable;
use function is_string;

require_once 'Kansas/Controller/AbstractController.php';

class Index	extends AbstractController {
	
	private static $actions = [];

	public function callAction ($action, array $vars) {
		if(is_callable([$this, $action])) {
			return $this->$action($vars);
		}
		if(isset(self::$actions[$action])) {
			return call_user_func(self::$actions[$action], $this, $vars);
		}
		require_once 'System/NotImplementedException.php';
		throw new NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($this));
	}

	public static function addAction($actionName, callable $callback) {
		if(!is_string($actionName)) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new ArgumentOutOfRangeException('actionName');
		}
		self::$actions[$actionName] = $callback;
	}

	public function template(array $vars = []) {
		if(!isset($vars['template'])) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new ArgumentNullException('vars["template"]');
		}
		$template = $vars['template'];
		unset($vars['template']);
		return $this->createViewResult($template, $vars);
	}
	
	public function redirection(array $vars = []) {
		if(!isset($vars['gotoUrl'])) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new ArgumentNullException('vars["gotoUrl"]');
		}
		
		return Redirect::gotoUrl($vars['gotoUrl']);
	}
	
	public function css(array $vars) {
		require_once 'Kansas/View/Result/Css.php';
		global $application;
		$files = $vars['files'];
		$cssResult = new Kansas_View_Result_Css($files);
		$backendCache = $application->hasPlugin('BackendCache');
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

		require_once 'Kansas/View/Result/Redirect.php';
		return Redirect::gotoUrl($this->getParam('ru', '/'));
	}
	
	public function phpInclude(array $vars) {
		return new Kansas_View_Result_Include($vars['file']);
	}

	public function API(array $vars) {
		if(isset($vars['error'])) {
			if(isset($vars['code'])) {
				$code = $vars['code'];
				unset($vars['code']);
				switch ($code) {
					case 401: // Unauthorized
					if(isset($vars['scheme'])) {
						header('WWW-Authenticate: ' . $vars['scheme']);
						unset($vars['scheme']);
					}
					break;
				}
			} else
				$code = 500;
		} elseif(isset($vars['code'])) {
			$code = $vars['code'];
			unset($vars['code']);
		} else 
			$code = 200;
		http_response_code($code);
		if(isset($vars['identity']) && isset($vars['identity']['id'])) {
			$identityId = $vars['identity']['id'];
			$vars['identity'] = $identityId;
		}
		unset($vars['uri']);
		unset($vars['url']);
		unset($vars['router']);
		unset($vars['trail']);
		unset($vars['requestType']);
		require_once 'Kansas/View/Result/Json.php';
		return new Json($vars);
	}
	
}