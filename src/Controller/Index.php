<?php declare(strict_types = 1);
/**
  * Controlador principal para patrón MVC
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
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
            // ignore CWE-94 - Code Injection
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
        // Si el usuario ya se ha desconectado, abandonamos el procesamiento
        if (connection_aborted() == 1) {
            http_response_code(408); // HTTP 408: Request Timeout
            exit(0);
        } else {
            ignore_user_abort(true);
        }

        // Enviamos el código de respuesta HTTP
        self::sendResponseCode($vars);

        // Enviamos cabeceras CORS, si procede
        self::sendCORSHeaders($vars);

        // Limpieza de datos
        self::apiClean($vars);
        return new Json($vars);
    }

    public static function sendResponseCode(array $vars): int {
        // Enviamos el código de respuesta HTTP
        if (isset($vars['status']) &&
            is_int($vars['status'])) {
            $code = $vars['status'];
            if ($code == 401 && // HTTP 401: Unauthorized
                isset($vars['scheme'])) {
                header('WWW-Authenticate: ' . $vars['scheme']);
                unset($vars['scheme']);
            }
        } else { // Desconocido - HTTP 500: Internal Server Error
            $code = 500;
        }
        http_response_code($code);
        return $code;
    }

    public const NO_CORS    = 0x0;
    public const CORS_ACAO  = 0x1;
    public const CORS_ACAM  = 0x2;
    public const CORS_ACAH  = 0x4;
    public const CORS_ACAC  = 0x8;
    public const CORS_ALL   = 0xF;

    public static function sendCORSHeaders(array $data): int {
        $result = self::NO_CORS;
        if (isset($data['cors'])) {
            if (is_array($data['cors'])) {
                if (isset($data['cors']['origin'])) {
                    if ($data['cors']['origin'] === true) {
                        header('Access-Control-Allow-Origin: *');
                    } else {
                        header('Access-Control-Allow-Origin: ' . $data['cors']['origin']);
                    }
                    $result |= self::CORS_ACAO;
                }
                if (isset($data['cors']['methods'])) {
                    if ($data['cors']['methods'] === true) {
                        header('Access-Control-Allow-Methods: *');
                    } else {
                        header('Access-Control-Allow-Methods: ' . $data['cors']['methods']);
                    }
                    $result |= self::CORS_ACAM;
                }
                if (isset($data['cors']['headers'])) {
                    if ($data['cors']['headers'] === true) {
                        header('Access-Control-Allow-Headers: *');
                    } else {
                        header('Access-Control-Allow-Headers: ' . $data['cors']['headers']);
                    }
                    $result |= self::CORS_ACAH;
                }
                if (isset($data['cors']['credentials']) &&
                    $data['cors']['credentials']) {
                    header('Access-Control-Allow-Credentials: true');
                    $result |= self::CORS_ACAC;
                }
            } elseif (is_int($data['cors'])) {
                if ($data['cors'] & self::CORS_ACAO) {
                    header('Access-Control-Allow-Origin: *');
                    $result |= self::CORS_ACAO;
                }
                if ($data['cors'] & self::CORS_ACAM) {
                    header('Access-Control-Allow-Methods: *');
                    $result |= self::CORS_ACAM;
                }
                if ($data['cors'] & self::CORS_ACAH) {
                    header('Access-Control-Allow-Headers: *');
                    $result |= self::CORS_ACAH;
                }
                if ($data['cors'] & self::CORS_ACAC) {
                    header('Access-Control-Allow-Credentials: true');
                    $result |= self::CORS_ACAC;
                }
            } elseif ($data['cors'] === true) { // true -> acceso completo
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: *');
                header('Access-Control-Allow-Headers: *');
                header('Access-Control-Allow-Credentials: true');
                $result = self::CORS_ALL;
            }
        }
        return $result;
    }

    public static function apiClean(array &$data) {
        if (isset($data['identity']['id'])) {
            $data['identity'] = $data['identity']['id'];
        }
        foreach(['cors',
                 'uri',
                 'url',
                 'router',
                 'trail',
                 'requestType'] as $key) {
            unset($data[$key]);
        }

    }

}
