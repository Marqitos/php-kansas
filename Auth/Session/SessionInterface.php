<?php declare(strict_types = 1 );
/**
 * Representa el manejo de los datos de sesión que identifican un usuario
 *
 * @package System
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Auth\Session;

/**
 * Representa los datos de sesión de un usuario
 */
interface SessionInterface {
    /**
     * Obtiene el usuario actual
     */
    public function getIdentity();
    /**
     * Establece el usuario actual
     */
    public function setIdentity(array $user, $lifetime = 0, $domain = null);
    /**
     * Elimina la información del usuario
     */
    public function clearIdentity() : bool;
    /**
     * Obtiene el identificador del usuario actual
     */
    public function getId();
}