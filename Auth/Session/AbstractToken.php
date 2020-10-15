<?php
namespace Kansas\Auth\Session;

use System\Configurable;
use System\Guid;
use Kansas\Auth\Session\SessionInterface;

use function intval;
use function System\String\startWith;

require_once 'System/Configurable.php';
require_once 'Kansas/Auth/Session/SessionInterface.php';

abstract class AbstractToken extends Configurable implements SessionInterface {

    private $initialized = false;
    private $token = false;
    private $user = false;

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'lifetime'  => 0,
                    'domain'    => ''
                ];
            default:
                require_once 'System/NotSuportedException.php';
                throw new NotSuportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() {
        global $environment;
        return $environment->getVersion();
    }	
    

    /// Miembros de SessionInterface
    /**
     * Obtiene el usuario actual, o false si no está autenticado
     *
     * @return mixed Devuelve un array con los datos de usuario, o false para sesiones no autenticadas
     */
    public function getIdentity() {
        $this->initialize();
        return $this->user;
    }

    public function setIdentity(array $user, $lifetime = null, $domain = null) {
        global $application, $environment;
        $this->user = $user;
        if($lifetime == null) {
            $lifetime = $this->options['lifetime'];
        }
        if($domain == null) {
            $domain = $this->options['domain'];
        }
        $tokenPlugin = $application->getPlugin('token'); // crear token
        $data = [
            'iss'   => $_SERVER['SERVER_NAME'],
            'iat'   => time(),
            'sub'   => $user['id']
        ];
        if($lifetime > 0) {
            $lifetime += time();
            $data['exp'] = $lifetime;
        }
        if($domain !== null)
            $data['aud'] = $domain;
        $this->token = $tokenPlugin->createToken($data);
        setcookie( // Establecer cookie
            'token',
            (string) $this->token,
            $lifetime,
            '/',
            $domain);
        $this->initialized = true;
        return $this->token->getClaim('jti');
    }

    public function clearIdentity() {
        $this->user = false;
        unset($_COOKIE['token']);
        $res = setcookie('token', '', time() - 3600);
        $this->initialized = true;
        return $res;
    }

    public function getToken() {
        $this->initialize();
        return $this->token;
    }
    
    public function initialize($cookie = false, $lifetime = null, $domain = null) {
        if($this->initialized)
            return;
        global $application, $environment;
        $request = $environment->getRequest();
        if($request->hasHeader('Authorization')) { // Obtener sessión de headers
            $authHeader = $request->getHeader('Authorization')[0];
            require_once 'System/String/startWith.php';
            if(startWith($authHeader, 'Bearer ')) {
                $tokenString = substr($authHeader, 7);
            }
        }
        if (isset($_COOKIE['token'])) { // Obtener sessión de cookies
            $tokenString = $_COOKIE['token'];
        }
        if(isset($tokenString)) {
            if($lifetime == null) {
                $lifetime = $this->options['lifetime'];
            }
            if($domain == null) {
                $domain = $this->options['domain'];
            }
            $tokenPlugin = $application->getPlugin('token');
            $this->token = $tokenPlugin->parse($tokenString);
            if($this->token && $this->token->hasClaim('sub')) { // Obtener usuario
                $userId = false;
                $this->tryParseUser($this->token->getClaim('sub'), $userId);
                $usersProvider = $application->getProvider('users');
                $user = $usersProvider->getById($userId);
                if($user && $user['isEnabled'])
                    $this->user = $user;
            }
            if( $this->token && 
                $this->token->hasClaim('exp') &&
                $lifetime > 0) { // Renovar cookie si es necesario
                $updateTime = intval($this->token->getClaim('exp')) - ($lifetime / 2);
                if($updateTime > time()) {
                    $lifetime += time();
                    $this->token->setClaim('exp', $lifetime);
                    $tokenPlugin = $application->getPlugin('token'); // crear token
                    $this->token = $tokenPlugin->updateToken($this->token);
                    setcookie( // Establecer cookie
                        'token',
                        (string) $this->token,
                        $lifetime,
                        '/',
                        $domain);
                }
            }
            $this->initialized = true;
       }
    }

    protected abstract function tryParseUser($value, &$userId);

    public static function tryParseGuidUser($value, &$userId) {
        require_once 'System/Guid.php';
        if(!Guid::tryParse($value, $userId)) {
            $userId = $value;
            return false;
        }
        return true;
    }

    public static function tryParseIntUser($value, &$userId) {
        $userId = intval($value);
        if($userId == 0) {
            $userId = $value;
            return false;
        }
        return true;
    }

    public function getId() {
        $this->initialize();
        if($this->token && $this->token->hasClaim('jti'))
            return $this->token->getClaim('jti');
        return false;
    }


}