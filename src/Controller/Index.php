<?php declare(strict_types = 1);
/**
  * Controlador principal para patrón MVC
  *
  * @package Kansas
  * @author Marcos Porto
  * @copyright 2024, Marcos Porto
  * @since v0.1
  */

namespace Kansas\Controller;

use System\NotImplementedException;
use System\ArgumentNullException;
use Kansas\Controller\AbstractController;
use Kansas\Localization\Resources;
use Kansas\View\Result\File;
use Kansas\View\Result\Content;
use Kansas\View\Result\Json;
use Kansas\View\Result\Redirect;
use Kansas\View\Result\Template;
use Kansas\View\Result\ViewResultInterface;

use function call_user_func;
use function connection_aborted;
use function get_class;
use function header;
use function is_callable;
use function is_int;
use function sprintf;

require_once 'Kansas/Controller/AbstractController.php';
require_once 'Kansas/Localization/Resources.php';
require_once 'Kansas/View/Result/Template.php';
require_once 'Kansas/View/Result/ViewResultInterface.php';

class Index extends AbstractController {
    
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
        return new File($vars['file'], $vars);
    }

    public function content(array $vars) {
        $etag = isset($vars['etag'])
            ? $vars['etag']
            : null;
        require_once 'Kansas/View/Result/Content.php';
        return new Content($vars['content'], $vars['mimetype'], $etag);
    }

    public function clearCache(array $vars) {
        global $application;
        $application->getView()->getEngine()->clearAllCache();

        require_once 'Kansas/View/Result/Redirect.php';
        return Redirect::gotoUrl($this->getParam('ru', '/'));
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

    public function API(array $vars) {
        require_once 'Kansas/View/Result/Json.php';
        if (connection_aborted() == 1) {
            die;
        }
        
        if (isset($vars['status']) &&
            is_int($vars['status'])) {
            $code = $vars['status'];
            if ($code == 401 &&
                isset($vars['scheme'])) {
                header('WWW-Authenticate: ' . $vars['scheme']);
                unset($vars['scheme']);
            }
        } else {
            $code = 500;
        }
        http_response_code($code);
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
