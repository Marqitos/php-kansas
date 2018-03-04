<?php
require_once 'Kansas/Auth/Session/Interface.php';

class Kansas_Auth_Session_Token
    implements Kansas_Auth_Session_Interface {

    private $_initialized = FALSE;
    private $_token = FALSE;

    /// Miembros de Kansas_Auth_Session_Interface
    public function initialize($force = FALSE, $lifetime = 0, $domain = NULL) {
      
        if (isset($_COOKIE['token'])) {
            global $application;
            $tokenModule = $application->getModule('token');
            $tokenString = $_COOKIE['token'];
            $token = $tokenModule->parse($tokenString);
            echo $this->_token->getHeaders(); // Retrieves the token header
            echo $this->_token->getClaims(); // Retrieves the token claims
            
            // echo $this->_token->getHeader('jti'); // will print "4f1g23a12aa"
            // echo $this->_token->getClaim('iss'); // will print "http://example.com"
            // echo $this->_token->getClaim('uid'); // will print "1"
            $this->_initialized = true;
       }
    }

    // Obtiene el usuario actual, o false si no está autenticado
    public function getIdentity() {
        return (isset($_SESSION['auth']))
            ? $_SESSION['auth']
            : false;
    }

    public function setIdentity($user, $lifetime = 0, $domain = NULL) {
        if(!$this->_initialized)
            $this->initialize(TRUE, $lifetime, $domain);
        $_SESSION['auth'] = $user;
    }

    public function clearIdentity() {
        unset($_SESSION['auth']);
        session_destroy();
        session_regenerate_id();
    }
    
    public function appRender() { // desbloquear sesión
        session_write_close();
    }
}