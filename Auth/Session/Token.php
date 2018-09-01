<?php
namespace Kansas\Auth\Session;

use Kansas\Auth\Session\SessionInterface;

require_once 'Kansas/Auth/Session/SessionInterface.php';

class Token implements SessionInterface {

    private $initialized = FALSE;
    private $token = FALSE;
    private $user;

    /// Miembros de SessionInterface
    public function initialize($force = FALSE, $lifetime = 0, $domain = NULL) {
      
        // Obtener sessión de headers

        // Obtener sessión de cookies
        if (isset($_COOKIE['token'])) {
            global $application;
            $tokenModule = $application->getModule('token');
            $tokenString = $_COOKIE['token'];
            $token = $tokenModule->parse($tokenString);
            echo $this->token->getHeaders(); // Retrieves the token header
            echo $this->token->getClaims(); // Retrieves the token claims
            
            // echo $this->token->getHeader('jti'); // will print "4f1g23a12aa"
            // echo $this->token->getClaim('iss'); // will print "http://example.com"
            // echo $this->token->getClaim('uid'); // will print "1"
            $this->initialized = true;
       }
    }

    // Obtiene el usuario actual, o false si no está autenticado
    public function getIdentity() {

    }

    public function setIdentity($user, $lifetime = 0, $domain = NULL) {
        $this->user = $user;
        $this->initialize(TRUE, $lifetime, $domain);

    }

    public function clearIdentity() {

    }
    
    public function appRender() { // desbloquear sesión

    }
}