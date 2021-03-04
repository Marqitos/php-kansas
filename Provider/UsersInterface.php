<?php
/**
 * Representa un proveedor para acceso a datos de usuarios
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Provider;

interface UsersInterface {

    /**
     * Devuelve un usuario por su id
     * 
     * @param mixed $id Id del usuario
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function getById($id);

    /**
     * Devuelve un usuario por su email, comprobando su contraseña
     * 
     * @param email string Dirección de correo el usuario
     * @param password string (opcional) Contraseña del usuario
     * @param login bool (Parametro de salida) true en caso de que el usuario exista la contraseña coincida, false en caso contrario
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function getByEmail($email, $password = null, &$login = false);

    /**
     * Devuelve un usuario por su nombre de usuario o teléfono, comprobando su contraseña
     * 
     * @param string $username Nombre de usuario o número de teléfono
     * @param string $password  (opcional) Contraseña del usuario
     * @param bool $login  (Parametro de salida) true en caso de que el usuario exista la contraseña coincida, false en caso contrario
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function searchUser($username, $password = null, &$login = false);

}
