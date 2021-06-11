<?php

namespace Kansas\Auth\Session;

use System\Configurable;
use System\Guid;
use System\NotSupportedException;
use System\Version;
use Kansas\Auth\Session\SessionInterface;
use Kansas\Plugin\Token AS TokenPlugin;

use function intval;
use function System\String\startWith;

require_once 'System/Configurable.php';
require_once 'Kansas/Auth/Session/SessionInterface.php';

/**
 * Autenticación mediante javascript web token
 */
abstract class AbstractToken extends Configurable implements SessionInterface {

    private $initialized = false;
    private $token = false;
    private $user = false;

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'lifetime'  => 0,
                    'domain'    => '',
                    'iss'       => $_SERVER['SERVER_NAME']
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    /**
     *  @see System\Configurable\getVersion()
     */
    public function getVersion() : Version {
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
        if(!$this->initialized) {
            global $application;
            $localizationPlugin     = $application->getPlugin('Localization');
            $locale                 = $localizationPlugin->getLocale();
            $this->initialize($locale['lang'], null, null, $locale['country']);
        }
        return $this->user;
    }

    public function setIdentity(array $user, $lifetime = null, $domain = null) {
        global $application;
        $this->user = $user;
        if($lifetime == null) {
            $lifetime = $this->options['lifetime'];
        }
        if($domain == null) {
            $domain = $this->options['domain'];
        }
        $tokenPlugin = $application->getPlugin('token'); // crear token
        $data = [
            'iss'   => $this->options['iss'],
            'iat'   => time()
        ];
        if(isset($user['id'])) {
            if(is_object($user['id'])) {
                $data['sub'] = (string) $user['id'];
            } else {
                $data['sub'] = $user['id'];
            }
        }
        if($lifetime > 0) {
            $lifetime += time();
            $data['exp'] = $lifetime;
        }
        if($domain !== null) {
            $data['aud'] = $domain;
        }
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

    public function clearIdentity() : bool {
        if($this->token) {
            require_once 'Kansas/Plugin/Token.php';
            TokenPlugin::deleteToken($this->token);
        }
        $this->user = false;
        unset($_COOKIE['token']);
        $res = setcookie('token', '', time() - 3600);
        $this->initialized = true;
        return $res;
    }

    public function getToken() {
        if(!$this->initialized) {
            global $application;
            $localizationPlugin     = $application->getPlugin('Localization');
            $locale                 = $localizationPlugin->getLocale();
            $this->initialize($locale['lang'], null, null, $locale['country']);
        }
        return $this->token;
    }
    
    public function initialize($lang, $lifetime = null, $domain = null, $country = null) {
        if($this->initialized) {
            return;
        }
        global $application, $environment;
        $request = $environment->getRequest();
        if($request->hasHeader('Authorization')) { // Obtener sessión de headers
            require_once 'System/String/startWith.php';
            foreach($request->getHeader('Authorization') as $authHeader) {
                if(startWith($authHeader, 'Bearer ')) {
                    $tokenString = substr($authHeader, 7);
                    break;
                }
            }
        }
        if (isset($_COOKIE['token'])) { // Obtener sesión de cookies
            $tokenString = $_COOKIE['token'];
        }
        if(isset($tokenString)) {
            if($lifetime == null) {
                $lifetime = $this->options['lifetime'];
            }
            if($domain == null) {
                $domain = $this->options['domain'];
            }
            $tokenPlugin    = $application->getPlugin('token');
            $tokenProvider  = $application->getProvider('token');
            $jwt            = $tokenPlugin->parse($tokenString);
            if($jwt &&
               $jwt->hasClaim('jti')) {
                $id = new Guid($jwt->getClaim('jti'));
                $this->token = $tokenProvider->getToken($id, false);
            }
            if($this->token && $this->token->hasClaim('sub')) { // Obtener usuario
                $userId = false;
                $this->tryParseUser($this->token->getClaim('sub'), $userId);
                $usersProvider = $application->getProvider('users');
                $userRow = $usersProvider->getById($userId, $lang, $country);
                if($userRow && $userRow['isEnabled']) {
                    $this->user = $userRow;
                }
            }
            if($this->token && 
               $this->token->hasClaim('exp') &&
               $lifetime > 0) { // Renovar token si es necesario
                $updateTime = intval($this->token->getClaim('exp')) - ($lifetime / 2);
                if($updateTime < time()) {
                    $lifetime += time();
                    $this->token = $tokenPlugin->updateToken($this->token, [
                        'exp' => $lifetime
                    ]);
                    $tokenProvider->saveToken($this->token);
                    setcookie('token', (string) $this->token, $lifetime, '/', $domain); // Establecer cookie
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
        if(!$this->initialized) {
            global $application;
            $localizationPlugin     = $application->getPlugin('Localization');
            $locale                 = $localizationPlugin->getLocale();
            $this->initialize($locale['lang'], null, null, $locale['country']);
        }
        if($this->token && $this->token->hasClaim('jti')) {
            return $this->token->getClaim('jti');
        }
        return false;
    }


}