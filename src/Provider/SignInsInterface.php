<?php
/**
  * Representa un proveedor para acceso a datos para registros de inicio de sesión
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Provider;

interface SignInsInterface {
    /**
     * Registra un intento de inicio de sesión
     *
     * @param array $user       Tupla de la tabla Usuarios
     * @param array $trackData  Datos del dispositivo que realiza la petición
     * @param bool  $login      Resultado del inicio de sesión
     */
    public function registerSignIn(array &$user, array $trackData, bool $login);
}
