<?php
/**
 * Representa un proveedor para acceso a datos para registros de inicio de sesión
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */


interface SignInsInterface {
	/**
	 * Registra un intento de inicio de sesión
	 * 
	 * @param array $user Tupla de la tabla Usuarios
	 * @param array $trackData Datos del dispositivo que realiza la petición
	 * @param bool $login Resultado del inicio de sesión  
	 */
	public function registerSignIn(array &$user, array $trackData, $login);
}
