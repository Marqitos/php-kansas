<?php declare(strict_types = 1 );
/**
 * Controlador principal en una API con patrón MVC
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
use Kansas\View\Result\File;
use Kansas\View\Result\Json;
use Kansas\View\Result\Redirect;
use Kansas\View\Result\ViewResultInterface;

use function call_user_func;
use function connection_aborted;
use function get_class;
use function header;
use function http_response_code;
use function is_callable;
use function is_int;
use function sprintf;

require_once 'Kansas/Controller/AbstractController.php';
require_once 'Kansas/Localization/Resources.php';
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
		throw new NotImplementedException(sprintf(Resources::NOT_IMPLEMENTED_EXCEPTION_ACTION_FORMAT, $action, get_class($this)));
	}

	public static function addAction(string $actionName, callable $callback) : void {
		self::$actions[$actionName] = $callback;
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
		if(connection_aborted() == 1) {
			die;
		}
		
		if(isset($vars['status']) &&
		   is_int($vars['status'])) {
			$code = $vars['status'];
			if($code == 401 && 
				isset($vars['scheme'])) {
				header('WWW-Authenticate: ' . $vars['scheme']);
				unset($vars['scheme']);
			}
		} else {
			$code = 500;
		}
		if(isset($vars['cors'])) {
			if(is_array($vars['cors'])) {
                if(isset($vars['cors']['origin'])) {
                    header('Access-Control-Allow-Origin: ' . $vars['cors']['origin']);
                }
                if(isset($vars['cors']['methods'])) {
                    header('Access-Control-Allow-Methods: ' . $vars['cors']['methods']);
                }
                if(isset($vars['cors']['headers'])) {
                    header('Access-Control-Allow-Headers: ' . $vars['cors']['headers']);
                }
                if(isset($vars['cors']['credentials']) &&
                   $vars['cors']['credentials']) {
                    header('Access-Control-Allow-Credentials: true');
                }
			} elseif($vars['cors']) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: *');
                header('Access-Control-Allow-Headers: *');
                header('Access-Control-Allow-Credentials: true');
            }
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
