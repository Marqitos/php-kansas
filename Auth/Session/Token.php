<?php
namespace Kansas\Auth\Session;

use Kansas\Auth\Session\SessionInterface;
use System\Guid;

require_once 'Kansas/Auth/Session/SessionInterface.php';

class Token implements SessionInterface {

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

    public function setIdentity(array $user, $lifetime = 0, $domain = null) {
        require_once 'System/Guid.php';
        global $application, $environment;
        $this->user = $user;
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
    
    public function initialize($cookie = false, $lifetime = 0, $domain = null) {
        if($this->initialized)
            return;
        global $application;
        // Obtener sessión de headers

        

        if (isset($_COOKIE['token'])) { // Obtener sessión de cookies
            global $application;
            $tokenPlugin = $application->getPlugin('token');
            $this->token = $tokenPlugin->parse($_COOKIE['token']);
            if($this->token && $this->token->hasClaim('sub')) { // Obtener usuario
                require_once 'System/Guid.php';
                $userId = new Guid($this->token->getClaim('sub'));
                $usersProvider = $application->getProvider('users');
                $user = $usersProvider->getById($userId);
                if($user && $user['isEnabled'])
                    $this->user = $user;
            }
            if( $this->token && 
                $this->token->hasClaim('iat') && 
                $this->token->hasClaim('exp')) { // Renovar cookie si es necesario
                
                var_dump($this->token, time());

            }
            $this->initialized = true;
       }
    }

    public function getId() {
        $this->initialize();
        if($this->token && $this->token->hasClaim('jti'))
            return $this->token->getClaim('jti');
        return false;
    }


}