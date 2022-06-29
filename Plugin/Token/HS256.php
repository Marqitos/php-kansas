<?php declare(strict_types = 1);
/**
 * Verificación  y creación de tokens firmados mediante HS256
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.5
 */

namespace Kansas\Plugin\Token;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token as JWToken;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

require_once 'Lcobucci/JWT/Builder.php';
require_once 'Lcobucci/JWT/Token.php';

/**
 * Comprueba si la firma HS256 del token es válida
 *
 * @param JWToken $token Token JWT
 * @param string $secret Clave de firmado del token
 * @return boolean true si la firma es válida, false en caso contrario.
 */
function verifyToken(JWToken $token, string $secret) : bool {
    require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
    $signer = new Sha256();
    return $token->verify($signer, $secret);
}

/**
 * Devuelve un token firmado mediante HS256
 *
 * @param Builder $builder
 * @param string $secret Clave de firmado del token
 * @return JWToken Token JWT
 */
function buildToken(Builder $builder, string $secret) : JWToken {
    require_once 'Lcobucci/JWT/Signer/Key.php';
    require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
    $signer = new Sha256();
    $key = new Key($secret);
    return $builder->getToken($signer, $key);
}
