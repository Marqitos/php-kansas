<?php declare(strict_types = 1);
/**
 * Proporciona métodos estáticos para encriptación y validación de contraseñas
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas;

use function base64_decode;
use function base64_encode;
use function bin2hex;
use function crypt;
use function hex2bin;
use function hexdec;
use function intval;
use function password_hash;
use function sprintf;
use function strlen;
use function strtr;
use function substr;
use const PASSWORD_BCRYPT;

class Hash {

    /**
     * Prefijo hexadecimal para hash BCRYPT
     */
    const HEX_BCRYPT = '00db'; //sprintf('%04s', bin2hex(base64_decode(PASSWORD_BCRYPT)))

    /**
     * Devuelve una cadena hash hexadecimal, usando encriptación con Blowfish.
     * 
     * @param string $password Contraseña para crear el hash
     * @param int $cost (opcional) Coste de encriptación, entre 04-31
     * @return string Cadena hexadecimal de 86 caracteres, el hash es diferente aun para misma contraseña
     */
    public static function hexBcrypt(string $password, int $cost = null) : string {
		$options = $cost !== null
			? ['cost '	=> $cost]
			: [];
		$hash 		= password_hash($password, PASSWORD_BCRYPT, $options);
		$cost 		= intval(substr($hash, 4, 2));
		$hexHash	= self::HEX_BCRYPT;
        $hexHash   .= sprintf('%02x', $cost);
		$hexHash   .= bin2hex(substr(base64_decode(strtr(substr($hash, 7, 22), '.', '+') . "A="), 0, 16));
		$hexHash   .= bin2hex(substr(base64_decode(strtr(substr($hash, 29), '.', '+') . "A"), 0, 24));
        return $hexHash;
	}

    /**
     * Comprueba si un hash hexadecimal corresponde a una contraseña
     * 
     * @param string $hash Hash hexadecimal a comprobar
     * @param string $password Contraseña a comprobar
     * @return bool true si el hash es válido y corresponde a la contraseña
     */
    public static function validateHash(string $hash, string $password) : bool {
        if(strtolower(substr($hash, 0, 4)) == self::HEX_BCRYPT &&
           strlen($hash) == 86) {
            return self::validateBcrypt(substr($hash, 4), $password);
        }
        return false;
    }

    private static function validateBcrypt(string $hash, string $password) : bool {
        // descomponemos el hash
        $cost 		= hexdec(substr($hash, 0, 2));
        $salt 		= hex2bin(substr($hash, 2, 32) . 'e0');
        $passHash 	= hex2bin(substr($hash, 34));
        // comprobamos que equivale a la contraseña
        $cryptHash 	= '$' . PASSWORD_BCRYPT .
                      '$' . sprintf('%02d', $cost) .
                      '$' . substr(strtr(base64_encode($salt), '+', '.'), 0, 22);
        $bcrypt 	= crypt($password, $cryptHash);
        $cryptHash .= substr(strtr(base64_encode($passHash), '+', '.'), 0, 31);
        return $bcrypt == $cryptHash;
    }

}
