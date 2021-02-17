<?php

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
    public function clearIdentity();
    /**
     * Obtiene el identificador del usuario actual
     */
    public function getId();
}