<?php declare(strict_types = 1 );
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
     * @param string $lang Idioma para datos localizados
	 * @param string $country (Opcional) Código de región especifica del idioma
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function getById($id, string $lang, string $country = null);

    /**
     * Devuelve un usuario por su email, comprobando su contraseña
     * 
     * @param string $email Dirección de correo el usuario
     * @param string $lang Idioma para datos localizados
     * @param bool $login (Parametro de salida) true en caso de que el usuario exista la contraseña coincida, false en caso contrario
     * @param string $password (opcional) Contraseña del usuario
	 * @param string $country (Opcional) Código de región especifica del idioma
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function getByEmail(string $email, string $lang, bool &$login = false, string $password = null, string $country = null);

    /**
     * Devuelve un usuario por su nombre de usuario o teléfono, comprobando su contraseña
     * 
     * @param string $username Nombre de usuario o número de teléfono
     * @param string $lang Idioma para datos localizados
     * @param bool $login  (Parametro de salida) true en caso de que el usuario exista la contraseña coincida, false en caso contrario
     * @param string $password  (opcional) Contraseña del usuario
	 * @param string $country (Opcional) Código de región especifica del idioma
     * @return array|false array con la tupla del usuario en caso de que exista, false en caso contrario
     */
    public function searchUser(string $username, string $lang, bool &$login = false, string $password = null, string $country = null);

}
