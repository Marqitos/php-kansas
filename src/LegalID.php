<?php declare(strict_types = 1);
/**
  * Realiza comprobaciones sobre NIE y NIF
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas;

use Kansas\Environment;

use function Kansas\Validation\validateLegalID;

class LegalID {

    protected static $country   = 'ES';
    protected static $type      = null;

    public static function tryParse(&$legalCode) {
        global $environment;
        // Eliminamos carácteres especiales y lo pasamos a mayúsculas
        $legalCode  = trim(strtoupper($legalCode));
        $legalCode  = preg_replace('/[^A-Z0-9]/', '', $legalCode);

        // En modo desarrollo todos los DNIS, son válidos
        if($environment->getStatus() == Environment::ENV_DEVELOPMENT) {
            return true;
        }

        // Obtener lista de paises
        $vies = require_once 'Kansas/VIES.php';
        // Comprobamos si el documento es un VIES, con uno de los paises disponibles
        $countryCode = substr($legalCode, 0, 2);
        if(isset($vies[$countryCode])) {
            self::$country = $vies[$countryCode];
            $legalCode = substr($legalCode, 2);
        } else {
            $countryCode = 'ES';
        }

        // Ver si tenemos validación, y si es así ejecutarla
        $fileName = __DIR__ . '/LegalID/' . $countryCode . '.php';
        if(file_exists($fileName)) { // Validar mediante validación expecifica
            require_once $fileName;
            // Validamos el documento
            return validateLegalID($legalCode, self::$type);
        } elseif(isset($vies[$countryCode]['patern'])) { // Validar mediante expresión regular
            // TODO: comprobar mediante expresión regular


        }
        return false;
    }

    public static function getCountry() {
        return self::$country;
    }

    public static function getType() {
        return self::$type;
    }

}
