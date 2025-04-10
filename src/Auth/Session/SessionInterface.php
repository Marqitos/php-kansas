<?php declare(strict_types = 1);
/**
  * Representa el manejo de los datos de sesión que identifican un usuario
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Auth\Session;

/**
 * Representa los datos de sesión de un usuario
 */
interface SessionInterface {
    /**
     * Obtiene el usuario actual, o false si no está autenticado
     *
     * @return mixed Devuelve un array con los datos de usuario, o false para sesiones no autenticadas
     */
    public function getIdentity();
    /**
     * Establece el usuario actual
     */
    public function setIdentity(array $user, int $lifetime = 0, ?string $domain = null);
    /**
     * Elimina la información del usuario
     */
    public function clearIdentity() : bool;
    /**
     * Obtiene el identificador del usuario actual
     */
    public function getId();
}
