<?php declare(strict_types = 1);
/**
  * Proporciona métodos estáticos para encriptación y validación de contraseñas
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  * @version    v0.6
  */

namespace Kansas;

use function base64_decode;
use function base64_encode;
use function bin2hex;
use function crypt;
use function hex2bin;
use function hexdec;
use function intval;
use function mb_strtolower;
use function md5;
use function password_hash;
use function sprintf;
use function strlen;
use function strtolower;
use function strtr;
use function substr;
use const PASSWORD_BCRYPT;

class Hash {

    /**
      * Prefijo hexadecimal para hash BCRYPT
      */
    const HEX_BCRYPT    = '00db'; //sprintf('%04s', bin2hex(base64_decode(PASSWORD_BCRYPT)))
    /**
      * Indicativo usado por crypt, para indicar que se usará Blowfish
      */
    const BCRYPT_CODE   = '2y';

    const DEFAULT_COST  = 10; // Coste por defecto para la encriptación
    const MAX_COST      = 31; // Coste máximo para la encriptación
    const MIN_COST      = 4;  // Coste mínimo para la encriptación

    /**
      * Devuelve una cadena hash hexadecimal, usando encriptación con Blowfish.
      * Si no se especifica usuario, se genera un salt aleatorio, y se incluye en el hash.
      *
      * @param  string  $password   Contraseña para crear el hash
      * @param  string  $username   (Opcional) Nombre de usuario, para calcular el salt
      * @param  int     $cost       (Opcional) Coste de encriptación, entre 4-31
      * @return string              Cadena hexadecimal, el hash es diferente aun para la misma contraseña
      */
    public static function hexBcrypt(string $password, ?string $username = null, ?int $cost = null) : string {
        if ($cost !== null &&
            ($cost < self::MIN_COST ||
             $cost > self::MAX_COST)) {
            $cost = self::DEFAULT_COST;
        }
        if ($username != null) { // El salt se calcula en base al nombre de usuario, y no va incluido en el hash
            if ($cost == null) {
                $cost = self::DEFAULT_COST;
            }
            $salt = substr(md5($username), 0, 30) . 'e0';
            $cryptHash  = '$' . self::BCRYPT_CODE .
                          '$' . sprintf('%02d', $cost) .
                          '$' . substr(strtr(base64_encode($salt), '+', '.'), 0, 22);
            $hash     = crypt($password, $cryptHash);
        } else { // El hash lleva el salt, calculado por PHP
            $options = $cost !== null
                ? ['cost '  => $cost]
                : [];
            $hash       = password_hash($password, PASSWORD_BCRYPT, $options);
        }
        $cost       = intval(substr($hash, 4, 2));
        $hexHash    = self::HEX_BCRYPT;
        $hexHash   .= sprintf('%02x', $cost);
        if ($username == null) {
            $hexHash   .= bin2hex(substr(base64_decode(strtr(substr($hash, 7, 22), '.', '+') . "A="), 0, 16));
        }
        $hexHash   .= bin2hex(substr(base64_decode(strtr(substr($hash, 29), '.', '+') . "A"), 0, 24));
        return $hexHash;
    }

    /**
     * Comprueba si un hash hexadecimal corresponde a una contraseña
     *
     * @param   string $hexHash     Hash hexadecimal a comprobar
     * @param   string $password    Contraseña contra la que validar el hash
     * @return  bool                true si el hash es válido y corresponde a la contraseña, false en caso contrario
     */
    public static function validateHash(string $hexHash, string $password, ?string $username = null): bool {
        if(strtolower(substr($hexHash, 0, 4)) == self::HEX_BCRYPT) {
            if (strlen($hexHash) == 86) { // El hash está encriptado con Bcrypt / Blowfish y lleva incluido el salt
                return self::validateBcrypt(substr($hexHash, 4), $password);
            } elseif ($username != null &&
                      strlen($hexHash) == 56) { // El hash está encriptado con Bcrypt / Blowfish y se ha pasado el nombre de usuario
                $salt = substr(md5(mb_strtolower($username)), 0, 30) . 'e0';
                return self::validateBcrypt(substr($hexHash, 4), $password, $salt);
            }
        }

        return false;
    }

    /**
      * Comprueba si un hash corresponde a una contraseña
      *
      * @param  string  $hash       Cadena haxadecimal a comprobar, la longitud debe de ser de 82 caracteres
      * @param  string  $password   Contraseña contra la que validar el hash
      * @return boolean             true si el hash es válido y corresponde a la contraseña, false en caso contrario
     */
    protected static function validateBcrypt(string $hash, string $password, ?string $salt = null): bool {
        // descomponemos el hash
        $cost           = hexdec(substr($hash, 0, 2));
        if (strlen($hash) == 82) {
            $salt       = hex2bin(substr($hash, 2, 32) . 'e0');
            $passHash   = hex2bin(substr($hash, 34));
        } elseif($salt !== null &&
                 strlen($salt) == 30 &&
                 strlen($hash) != 52) {
            $salt       = hex2bin($salt);
            $passHash   = hex2bin(substr($hash, 2));
        } else {
            return false;
        }
        // comprobamos que equivale a la contraseña
        $cryptHash      = '$' . self::BCRYPT_CODE .
                          '$' . sprintf('%02d', $cost) .
                          '$' . substr(strtr(base64_encode($salt), '+', '.'), 0, 22);
        // ignore: CWE-328 - Insecure cryptography
        $bcrypt         = crypt($password, $cryptHash);
        $cryptHash     .= substr(strtr(base64_encode($passHash), '+', '.'), 0, 31);
        return $bcrypt == $cryptHash;
    }

}
