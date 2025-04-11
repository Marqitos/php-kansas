<?php
/**
  * Plugin para controlar la autenticación
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Kansas\Application;
use Kansas\Auth\AuthorizationInterface;
use Kansas\Auth\ServiceInterface as AuthService;
use Kansas\Auth\Session\SessionInterface;
use Kansas\Environment;
use Kansas\Loader;
use Kansas\Plugin\Localization;
use Kansas\Plugin\RouterPluginInterface;
use Kansas\Provider\UsersInterface;
use Kansas\Router\Auth as AuthRouter;
use Kansas\Router\RouterInterface;
use Psr\Http\Message\RequestInterface;
use System\Configurable;
use System\EnvStatus;
use System\NotSupportedException;
use System\Version;

require_once 'Kansas/Auth/AuthorizationInterface.php';
require_once 'Kansas/Auth/Session/SessionInterface.php';
require_once 'Kansas/Plugin/RouterPluginInterface.php';
require_once 'Kansas/Router/RouterInterface.php';
require_once 'Psr/Http/Message/RequestInterface.php';
require_once 'System/Configurable.php';

class Auth extends Configurable implements RouterPluginInterface {

    /// Constantes
    const TYPE_FORM         = 'form';
    const TYPE_FEDERATED    = 'federated';

    /// Campos
    private $authorization;
    private $localization;
    private $users;
    private $router;
    private $authServices = [];
    private $session;
    private $callbacks = [
        'onChangedPassword' => []
    ];
    private $authTypes = [];

    /// Constructor
    public function __construct(array $options) {
        global $application;
        parent::__construct($options);
        require_once 'Kansas/Application.php';
        $application->registerCallback(Application::EVENT_PREINIT, [$this, "appPreInit"]);
        $application->registerCallback(Application::EVENT_ROUTE,   [$this, "appRoute"]);
    }

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'actions'               => [],
            'authorization_plugin'  => null, //'NullAuthorization',
            'device'                => true,
            'domain'                => '',
            'lifetime'              => 60 * 60 * 24 * 15, // 15 días
            'router'                =>  [
                'base_path'             => 'cuenta',
                'pages'                 => []],
            'session'               => 'Kansas\Auth\Session\SessionDefault',
            'users_provider'        => 'Users'];
    }

    public function getVersion() : Version {
        return Environment::getVersion();
    }

    // Miembros de Kansas\Plugin\RouterPluginInterface
    public function getRouter() : RouterInterface {
        if ($this->router == null) {
            require_once 'Kansas/Router/Auth.php';
            $this->router = new AuthRouter($this->options['router']);
            $this->router->addActions($this->options['actions']);
            foreach ($this->authServices as $authService) {
                $this->router->addActions($authService->getActions());
            }
        }
        return $this->router;
    }

    /// Eventos de la aplicación
    public function appPreInit() { // añadir router
        global $application;
        $application->addRouter($this->getRouter());
    }

    public function appRoute(RequestInterface $request, array $params) { // Añadir datos de usuario
        $result = [];
        $user   = $this->getSession()->getIdentity();
        if ($user) {
            $result['identity'] = $user;
        }
        if (array_search(self::TYPE_FORM, $this->authTypes) !== false) {
            $result['authForm'] = true;
        }
        return $result;
    }

    // Objetos internos
    public function getSession() : SessionInterface {
        if (!isset($this->session)) {
            require_once 'Kansas/Loader.php';
            Loader::loadClass($this->options['session']);
            $this->session = new $this->options['session']();
        }
        return $this->session;
    }

    protected function getAuthorization() : AuthorizationInterface {
        if (isset($this->authorization)) {
            if (is_string($this->options['authorization_plugin'])) {
                global $application;
                $this->authorization = $application->getPlugin($this->options['authorization_plugin']);
            } elseif ($this->options['authorization_plugin'] instanceof AuthorizationInterface) {
                $this->authorization = $this->options['authorization_plugin'];
            } else {
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException();
            }

        }
        return $this->authorization;
    }

    protected function getLocalization() : Localization {
        if ($this->localization == null) {
            global $application;
            $this->localization = $application->getPlugin('Localization');
        }
        return $this->localization;
    }

    protected function getUsersProvider() : UsersInterface {
        if ($this->users == null) {
            if (is_string($this->options['users_provider'])) {
                global $application;
                $this->users = $application->getProvider($this->options['users_provider']);
            } elseif ($this->options['users_provider'] instanceof UsersInterface) {
                $this->users = $this->options['users_provider'];
            } else {
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException();
            }
        }
        return $this->users;
    }

    public function setIdentity($user, $remember = false, $domain = null, $deviceId = null) {
        // Almacenar usuario en sesión
        global $application;
        $lifetime = $remember
            ? $this->options['lifetime']
            : 0;
        if ($this->options['device']) {
            if ($deviceId == null) {
                $deviceId = $application->getPlugin('Token')->getDevice();
            }
            $device = $deviceId;
        } else {
            $device = false;
        }
        if ($domain == null) {
            $domain = ''; // TODO: Ver por que no existe: $this->options['domain']
        }
        $this->getSession()->setIdentity($user, $lifetime, $domain, $device);
    }

    public function getIdentity() {
        return $this->getSession()->getIdentity();
    }

    public function getRole() {
        $user = $this->getIdentity();
        if ($user) {
            return $user['role'];
        }
        return null;

    }

    public function registerChangedPassword($callback) {
        if (is_callable($callback)) {
            $this->callbacks['onChangedPassword'][] = $callback;
        }
    }

    /**
     * Comprueba si el usuario/email/teléfono y contraseña son correctos.
     * Asocia el usuario a la sesión actual, y devuelve el resultado de la validación.
     *
     * @param string $username Username, email o telefono del usuario
     * @param string $password Contraseña para validar
     * @param bool $rememberMe Si se debe recordar al usuario en el navegador
     * @param array $user Si se ha encontrado un usuario, establece la tupla del usuario
     * @return bool true en caso de que el usuario y contraseña concuerden, false en caso contrario.
     */
    public function login(string $username, string $password, bool $rememberMe, ?array &$user = null) {
        $email              = filter_var($username, FILTER_VALIDATE_EMAIL);
        $login              = false;

        $localizationPlugin = $this->getLocalization();
        $usersProvider      = $this->getUsersProvider();
        $locale             = $localizationPlugin->getLocale();
        if ($email) {
            $user = $usersProvider->getByEmail($email, $locale['lang'], $login, $password, $locale['country']);
        }
        if (!$user) {
            $user = $usersProvider->searchUser($username, $locale['lang'], $login, $password, $locale['country']);
        }
        if ($login) {
            $this->setIdentity($user, $rememberMe);
        }
        return $login;
    }


    public function getAuthService($serviceName = 'membership') { // Devuelve un servicio de autenticación por el nombre
        return isset($this->authServices[$serviceName])
            ? $this->authServices[$serviceName]
            : false;
    }

    public function getAuthServices($serviceAuthType = 'form') { // Devuelve los servicios de autenticación por el tipo
        $result = [];
        foreach ($this->authServices as $name => $service) {
            if ($service->getAuthType() == $serviceAuthType) {
                $result[$name] = $service;
            }
        }
        return $result;
    }

    public function addAuthService(AuthService $authService) {
        $this->authServices[$authService->getName()] = $authService;
        if (!array_search($authService->getAuthType(), $this->authTypes)) {
            $this->authTypes[] = $authService->getAuthType();
        }
    }

    // Obtiene si el usuario actual tiene permisos para realizar una acción
    public function hasPermision($permisionName) {
        $user = $this->getIdentity();
        return $this->getAuthorization()->hasPermision($user, $permisionName);
    }

}
