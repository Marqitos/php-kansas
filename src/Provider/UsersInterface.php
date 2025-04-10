<?php declare(strict_types = 1);
/**
  * Representa un proveedor para acceso a datos de usuarios
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Provider;

interface UsersInterface {

    /**
      * Devuelve un usuario por su id
      *
      * @param  mixed   $id         Id del usuario
      * @return array|false         Array con la tupla del usuario en caso de que exista, false en caso contrario
      */
    public function getById($id): array|false;

    /**
      * Devuelve un usuario por su email
      *
      * @param  string  $email      Dirección de correo el usuario
      * @return array|false         array con la tupla del usuario en caso de que exista, false en caso contrario
      */
    public function getByEmail(string $email): array|false;

    /**
      * Devuelve la lista de usuarios
      *
      * @return array
      */
    public function listUsers(?int $roles): array;

    /**
      * Devuelve un usuario por su nombre de usuario o teléfono, comprobando su contraseña
      *
      * @param  string  $username   Nombre de usuario o número de teléfono
      * @return array|false         Array con la tupla del usuario en caso de que exista, false en caso contrario
      */
    public function searchUser(string $username): array|false;

}
