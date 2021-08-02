<?php declare(strict_types = 1);
/**
 * Representa el manejo permisos de un usuario
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Auth;

interface AuthorizationInterface {
    // Constantes
	// Roles predeterminadas
	const ROLE_ADMIN        = 'admin'; // Usuario con todos los permisos
	const ROLE_GUEST        = 'guest'; // Usuario no autenticado

    // Métodos
    /**
     * Devuelve si un usuario dispone de un permiso concreto
     * 
     * @param array|string $user Usuario o rol para comprobar los permisos
     * @param string $permisionName Nombre del permiso solicitado
     * @return bool true en caso de disponer de dicho permiso, o false en caso contrario
     */
    public function hasPermision($user, string $permisionName) : bool;
}
