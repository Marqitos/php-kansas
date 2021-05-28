<?php declare(strict_types = 1 );
/**
 * Controlador principal en una aplicación con patrón MVC
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.1
 */

namespace Kansas\Controller;

use System\NotImplementedException;
use System\ArgumentNullException;
use Kansas\Controller\AbstractController;
use Kansas\Localization\Resources;
use Kansas\View\Result\Content;
use Kansas\View\Result\Css;
use Kansas\View\Result\File;
use Kansas\View\Result\Json;
use Kansas\View\Result\Redirect;
use Kansas\View\Result\Template;
use Kansas\View\Result\ViewResultInterface;

use function call_user_func;
use function get_class;
use function header;
use function http_response_code;
use function is_callable;

require_once 'Kansas/Controller/AbstractController.php';
require_once 'Kansas/Localization/Resources.php';
require_once 'Kansas/View/Result/Template.php';
require_once 'Kansas/View/Result/ViewResultInterface.php';

class Index	extends AbstractController {
	
	private static $actions = [];

	public function callAction(string $action, array $vars) : ViewResultInterface {
		if(is_callable([$this, $action])) {
			return $this->$action($vars);
		}
		if(isset(self::$actions[$action])) {
			return call_user_func(self::$actions[$action], $this, $vars);
		}
		require_once 'System/NotImplementedException.php';
		throw new NotImplementedException(sprintf(Resources::NOT_ACTION_IMPLEMENTED_EXCEPTION_FORMAT, $action, get_class($this)));
	}

	public static function addAction(string $actionName, callable $callback) : void {
		self::$actions[$actionName] = $callback;
	}

	public function template(array $vars) : Template {
		if(!isset($vars['template'])) {
			require_once 'System/ArgumentNullException.php';
			throw new ArgumentNullException('vars["template"]');
		}
		$template = $vars['template'];
		unset($vars['template']);
		return $this->createViewResult($template, $vars);
	}
	
	public function redirection(array $vars) {
		if(!isset($vars['gotoUrl'])) {
			require_once 'System/ArgumentNullException.php';
			throw new ArgumentNullException('vars["gotoUrl"]');
		}
		return Redirect::gotoUrl($vars['gotoUrl']);
	}
	
	public function css(array $vars) {
		require_once 'Kansas/View/Result/Css.php';
		global $application;
		$files = $vars['files'];
		$cssResult = new Css($files);
		if($backendCache = $application->hasPlugin('BackendCache')) {
			require_once 'Kansas/View/Result/Content.php';
			$cacheId = $cssResult->getCacheId();
			if($backendCache->test($cacheId)) {
				$content = $backendCache->load($cacheId);
			} else {
				$content = $cssResult->getResult(null);
				$backendCache->save($content, $cacheId);
			}
			return new Content($content, 'text/css');
		}
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
	
	public function API(array $vars) {
		require_once 'Kansas/View/Result/Json.php';
		if(isset($vars['error'])) {
			if(isset($vars['code'])) {
				$code = $vars['code'];
				unset($vars['code']);
				if($code == 401 && 
				   isset($vars['scheme'])) {
					header('WWW-Authenticate: ' . $vars['scheme']);
					unset($vars['scheme']);
				}
			} else {
				$code = 500;
			}
		} elseif(isset($vars['code'])) {
			$code = $vars['code'];
			unset($vars['code']);
		} else {
			$code = 200;
		}
		http_response_code($code);
		if(isset($vars['cors']) &&
		   $vars['cors'] !== false) {
			header('Access-Control-Allow-Origin: ' . $vars['cors']);
		}

		if(isset($vars['identity']) && isset($vars['identity']['id'])) {
			$vars['identity'] = $vars['identity']['id'];
		}
		foreach(['cors',
				 'uri',
				 'url',
				 'router',
				 'trail',
				 'requestType'] as $key) {
			unset($vars[$key]);
		}
		return new Json($vars);
	}
	
}
