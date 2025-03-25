<?php declare(strict_types = 1);
/**
 * Maneja la sesión de usuario mediante la funcionalidad integrada de php
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Auth\Session;

use Kansas\Auth\Session\SessionInterface;
use function session_destroy;
use function session_set_cookie_params;
use function session_write_close;
use function setcookie;
use const PHP_SESSION_ACTIVE;

require_once 'Kansas/Auth/Session/SessionInterface.php';

class SessionDefault implements SessionInterface {

    /// Miembros de SessionInterface
    /**
     * Obtiene el usuario actual, o false si no está autenticado
     *
     * @return mixed Devuelve un array con los datos de usuario, o false para sesiones no autenticadas
     */
    public function getIdentity() {
        $this->initialize(false);
        return (isset($_SESSION['auth']))
            ? $_SESSION['auth']
            : false;
    }

    /**
     * Establece el usuario actual
     *
     * @param array $user Usuario actual
     * @param integer $lifetime Tiempo de validez
     * @param string $domain Dominio de la cookie, por ejemplo 'www.php.net'. Para hacer las cookies visibles en todos los sub-dominios, el dominio debe ser prefijado con un punto, como '.php.net'.
     * @return string $sessionId Devuelve el id de sesión para la sesión actual
     */
    public function setIdentity(array $user, int $lifetime = 0, string $domain = NULL) {
        $sessionId = $this->initialize(true, $lifetime, $domain);
        $_SESSION['auth'] = $user;
        return $sessionId;
    }

    /**
     * Desvincula el usuario de la sesión actual, y destruye todos los datos de sesión.
     *
     * @return void
     */
    public function clearIdentity() : bool {
        unset($_SESSION['auth']);
        $res = session_destroy();
        if($res) { // eliminamos la cookie de sesión
            $cookieName = session_name();
            unset($_COOKIE[$cookieName]);
            $res = setcookie($cookieName, '', time() - 3600);
            $this->initialized = true;
            return $res;
        }
        return $res;
    }
    
    /**
     * Recupera la sesión actual
     *
     * @param boolean $force Indica si se debe iniciar una sesión aunq no haya datos previamente
     * @param integer $lifetime Tiempo de vida de la cookie de sesión, definido en segundos
     * @param string $domain Dominio de la cookie, por ejemplo 'www.php.net'. Para hacer las cookies visibles en todos los sub-dominios, el dominio debe ser prefijado con un punto, como '.php.net'.
     * @return void
     */
    public function initialize($force = false, $lifetime = 0, $domain = NULL) {
        if(session_status() == PHP_SESSION_ACTIVE) {
            return session_id();
        }
        $cookieName = session_name();

        if (isset($_COOKIE[$cookieName]) ||
            $force !== false) {
            if($domain == NULL) {
                session_set_cookie_params($lifetime);
            } else {
                session_set_cookie_params($lifetime, '/', $domain);
            }
            session_start();
            global $application;
            $application->registerCallback('render',  [$this, "appRender"]);
            return session_id();
       }
       return false;
    }

    /**
     * Escribir información de sesión y finalizar la sesión
     *
     * @return void
     */
    public function appRender() {
        session_write_close();
    }

    public function getId() {
        return session_id();
    }
}
