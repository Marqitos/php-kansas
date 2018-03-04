<?php
require_once 'Kansas/Auth/Session/Interface.php';

class Kansas_Auth_Session_Default
    implements Kansas_Auth_Session_Interface {

    private $_initialized = FALSE;

    /// Miembros de Kansas_Auth_Session_Interface
    public function initialize($force = FALSE, $lifetime = 0, $domain = NULL) {
        $cookieName = session_name();

        if ((session_status() != PHP_SESSION_ACTIVE &&
             isset($_COOKIE[$cookieName])) ||
             $force) {
            if($domain == NULL) {
                session_set_cookie_params($lifetime);
            } else {
                session_set_cookie_params($lifetime, '/', $domain);
            }
            session_start();
            global $application;
            $application->registerCallback('render',  [$this, "appRender"]);
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