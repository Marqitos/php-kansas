<?php declare(strict_types = 1);
/**
  * Representa un proveedor para acceso a Tokens JWT
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Provider;

use System\Guid;
use Lcobucci\JWT\Token as jwtToken;

interface TokenInterface {
    // Constantes
    /**
      * Cabecera genérica para Tokens firmados mediante HMAC con SHA-256.
      * Firma simétrica
      */
    const HEADER_HS256 = '{"typ":"JWT","alg":"HS256"}';

    // Métodos
    /**
      * Obtiene un token
      * Comprueba la fecha de expiración y la firma
      *
      * @param mixed $id Id del token (claim jti)
      * @param string $signature Firma del token, si se especifica, se comprueba que coincida
      * @return mixed Token con el id solicitado, o false en caso de que no haya un token valido.
      */
    public function getToken($id, ?string $signature = null);

    /**
      * Guarda un token
      *
      * @param jwtToken $token Token que se debe insertar o actualizar
      * @return mixed Id del token guardado, o false en caso de error
      */
    public function saveToken(jwtToken $token);

    /**
      * Elimina un token
      *
      * @param mixed $id Id del token a eliminar (claim jti)
      */
    public function deleteToken($id);

}
