<?php declare(strict_types = 1);

namespace Kansas\Controller;

use System\NotImplementedException;
use Kansas\Controller\ControllerInterface;
use Kansas\Localization\Resources;
use Kansas\View\Result\ViewResultInterface;
use Kansas\View\Result\Template;
use Kansas\View\Result\Redirect;
use function array_merge;
use function get_class;
use function is_callable;

require_once 'Kansas/Controller/ControllerInterface.php';
require_once 'Kansas/Localization/Resources.php';

/**
 * Implementa las funcionalidades básicas de un controlador
 */
abstract class AbstractController implements ControllerInterface {
    
    private $params;
        
    public function init(array $params) : void {
        $this->params   = $params;
    }

    public function callAction(string $action, array $vars) : ViewResultInterface {
        if(!is_callable([$this, $action])) {
            require_once 'System/NotImplementedException.php';
            throw new NotImplementedException(sprintf(Resources::NOT_IMPLEMENTED_EXCEPTION_ACTION_FORMAT, $action, get_class($this)));
        }
        return $this->$action($vars);
    }
    
    /**
     * Obtiene un parametro de la solicitud
     *
     * @param string $key Clave del parametro
     * @param mixed $default Valor por defecto
     * @return mixed Valor almacenado, o valor por defecto en caso de que no esté establecido
     */
    public function getParam(string $key, $default = null) {
        if(isset($this->params[$key])) {
            return $this->params[$key];
        }
        return isset($_REQUEST[$key])
            ? $_REQUEST[$key]
            : $default;
    }

    /**
     * Obtiene la sesión de usuario activo
     *
     * @param array $vars Parametros de entrada
     * @return array|bool Datos de usuario si existe, o false en caso contrario
     */
    public static function getIdentity(array $vars) {
        global $application;
        if(isset($vars['identity'])) {
            return $vars['identity'];
        }
        return $application
            ->getPlugin('Auth')
            ->getIdentity();
    }

    /**
     * Devuelve una plantilla segun el archivo indicado y el contexto indicado
     *
     * @param string $defaultTemplate Nombre por defecto de nombre del archivo de la plantilla
     * @param array $data Contexto de la plantilla
     * @param string $mimeType Tipo de datos a devolver
     * @return Template Plantilla con los datos integrados
     */
    protected function createViewResult(string $defaultTemplate, array $data = [], string $mimeType = 'text/html') : Template {
        require_once 'Kansas/View/Result/Template.php';
        global $application;
        $view = $application->getView();
        $template = $view->createTemplate($this->getParam('template', $defaultTemplate), array_merge($this->params, $data));
        return new Template($template, $mimeType);
    }

    protected function isCached($defaultTemplate) {
        global $view;
        $template = $this->getParam('template', $defaultTemplate);
        return $view->isCached($template);
    }
    
    /**
     * Obtiene si hay una sesión de usuario activa para la petición actual
     *
     * @param mixed $result (out) Devuelve los datos de usuario si hay usuario, o una redirección en caso contrario.
     * @param string $ru (opcional) Establece a donde se debe redireccionar despues de iniciar sesión.
     * @return bool true si hay un usuario activo, o false en caso contrario.
     */
    protected function isAuthenticated(&$result, string $ru = null) : bool {
        global $application, $environment;
        $auth = $application->getPlugin('auth');
        if($auth->hasIdentity()) {
            $result = $auth->getIdentity();
            return true;
        } else {
            require_once 'Kansas/View/Result/Redirect.php';
            if($ru === null) {
                $ru = $environment->getRequest()->getRequestUri();
            }
            $result = Redirect::gotoUrl(
                $auth->getRouter()->assemble([
                    'action'    => 'signin',
                    'ru'        => $ru])
            );
            return false;
        }
    }
}
