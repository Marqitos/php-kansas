<?php declare(strict_types = 1);
/**
 * Proporciona un manejo de la sesión de usuario mediante un token jwt
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Auth\Session;

use System\Guid;
use Kansas\Auth\Session\SessionInterface;
use Kansas\Plugin\Token AS TokenPlugin;

use function intval;
use function System\String\startWith;

require_once 'Kansas/Auth/Session/SessionInterface.php';

/**
 * Autenticación mediante javascript web token
 */
abstract class AbstractToken implements SessionInterface {

    private $initialized = false;
    private $token = false;
    private $user = false;

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

    public function setIdentity(array $user, int $lifetime = 0, string $domain = null) {
        global $application;
        $tokenPlugin    = $application->getPlugin('Token');
        $this->user     = $user;
        if($lifetime == 0) {
            $lifetime = $tokenPlugin->getEXP();
        }
        if($domain == null) {
            $domain = $tokenPlugin->getSessionDomain();
        }
        $data = [
            'iss'   => $tokenPlugin->getISS(),
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
        if(!empty($domain)) {
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
        $this->initialize();
        return $this->token;
    }
    
    public function initialize(int $lifetime = null, string $domain = null) {
        if($this->initialized) {
            return;
        }
        global $application, $environment;
        $localizationPlugin     = $application->getPlugin('Localization');
        $locale                 = $localizationPlugin->getLocale();
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
        if(!isset($tokenString) &&
           isset($_COOKIE['token'])) { // Obtener sesión de cookies
            $tokenString = $_COOKIE['token'];
        }
        if(isset($tokenString)) {
            $tokenPlugin = $application->getPlugin('Token');
            if($lifetime == null) {
                $lifetime = $tokenPlugin->getEXP();
            }
            if($domain == null) {
                $domain = $tokenPlugin->getSessionDomain();
            }
            $tokenPlugin    = $application->getPlugin('token');
            $jwt            = $tokenPlugin->parse($tokenString);
            if($jwt) {
                if($jwt->hasClaim('jti')) {
                    $tokenProvider  = $application->getProvider('token');
                    $id = new Guid($jwt->getClaim('jti'));
                    $this->token = $tokenProvider->getToken($id);
                }  else {
                    $this->token = $jwt;
                }
            }
            if($this->token &&
               $this->token->hasClaim('sub')) { // Obtener usuario
                $userId = false;
                $this->tryParseUser($this->token->getClaim('sub'), $userId);
                $usersProvider = $application->getProvider('Users');
                $userRow = $usersProvider->getById($userId, $locale['lang'], $locale['country']);
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
                    if($this->token->hasClaim('jti')) {
                        $tokenProvider->saveToken($this->token);
                    }
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
        $this->initialize();
        if($this->token &&
           $this->token->hasClaim('jti')) {
            return $this->token->getClaim('jti');
        }
        return false;
    }

}
