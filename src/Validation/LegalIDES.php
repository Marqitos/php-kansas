<?php declare(strict_types = 1);
/**
  * Realiza comprobaciones sobre NIE y NIF
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño <lib-kansas@marcospor.to>
  * @copyright  2025, Marcos Porto
  * @since      v0.6
  */

namespace Kansas\Validation;

use System\ArgumentOutOfRangeException;

const T_CIF = 'CIF'; // Código de identificación fiscal (Entidad jurídica), ahora NIF
const T_NIE = 'NIE'; // Número de identificación fiscal extranjería
const T_NIF = 'NIF'; // Número de identificación fiscal (Persona física)

require_once 'System/ArgumentOutOfRangeException.php';

function validateLegalID(string $legalID, string &$type = null) {

    // Comprobamos que tenga 9 caracteres
    if(strlen($legalID) != 9) {
        return false;
    }

    // Obtenemos el tipo de documento, y la función de validación
    $function = null;
    try {
        $type = getType($legalID, $function);
    } catch(ArgumentOutOfRangeException $ex) {
        return false;
    }

    // Comprueba que tenga el formato correcto
    return call_user_func($function, $legalID, $type);
}

/**
 * Devolvemos el tipo de documento por la primera letra del documento
 *
 * @param string $legalCode Cadena con un NIF, NIe o CIF
 * @param callable|null $validate Si corresponde con uno de los tipos, devuelve la función para validar el número de documento
 * @return string Tipo de documento
 */
function getType(string $legalCode, callable &$validate = null) : string {
    $firstChar = substr($legalCode, 0, 1);
    // Comprobamos si es un NIF
    if(preg_match('/\d/', $firstChar) == 1) {
        $validate = 'Kansas\LegalID\validateNIF';
        return T_NIF;
    }
    // Comprobamos si es un NIE
    if(preg_match('/[XYZ]/', $firstChar) == 1) {
        $validate = 'Kansas\LegalID\validateNIF';
        return T_NIE;
    }
    // Comprobamos si es un CIF
    if(preg_match('/[ABCDEFGHJPQRSUVNW]/', $firstChar) == 1) {
        $validate = 'Kansas\LegalID\validateCIF';
        return T_CIF;
    }
    throw new ArgumentOutOfRangeException('legalCode', 'El código no es reconocible',  $legalCode);
}

function validateCIF(string $legalCode, string $type) {
    // Definimos campos
    $pares      = 0;
    $impares    = 0;
    // Recorrido por todos los dígitos del número
    for($index = 1; $index < 8; $index++) {
        if(($number = filter_var(substr($legalCode, $index, 1), FILTER_VALIDATE_INT)) === false) {
            throw new ArgumentOutOfRangeException('legalCode', 'El código no tiene un formato válido', $legalCode);
        }

        if(($index) % 2 == 0) { // Si es una posición par, se suman los dígitos
            $pares += $number;
        } else { // Si es una posición impar, se multiplican los dígitos por 2
            $number *= 2;

            // se suman los dígitos de la suma
            $impares += sumarDigitos($number);
        }
    }
    // Se suman los resultados de los números pares e impares
    $total      = $pares + $impares;

    // Se obtiene el dígito de las unidades
    $unidades = $total % 10;

    // Si las unidades son distintas de 0, se restan de 10
    if ($unidades != 0) {
        $unidades = 10 - $unidades;
    }

    switch(substr($legalCode, 0, 1)) {
        // Sólo números
        case "A":
        case "B":
        case "E":
        case "H":
            $digitoControl = (string) $unidades;
            break;
        // Sólo letras
        case "K":
        case "P":
        case "Q":
        case "S":
        case "N";
            $digitoControl = substr('JABCDEFGHI', $unidades, 1);
            break;
        default:
            return false;
    }

    // Devuelve si el código de control coincide
    return substr($legalCode, 8, 1) == $digitoControl;
}

/**
 * Comprueba que un NIF o NIE sea válido
 *
 * @param string $legalCode
 * @param string $type Tipo de documento que debe validar
 * @return bool true si el documento es válido, false en caso contrario
 * @throws System\ArgumentOutOfRangeException Si el tipo de documento no es ni NIF ni NIE
 */
function validateNIF(string $legalCode, string $type) {
    // Obtenemos la parte númerica del documento
    if($type == T_NIF) {
        $number = filter_var(substr($legalCode, 0, 8), FILTER_VALIDATE_INT);
    } elseif($type == T_NIE) {
        $number = filter_var(substr($legalCode, 1, 7), FILTER_VALIDATE_INT);
    } else{
        throw new ArgumentOutOfRangeException('type', 'El tipo de documento no es válido',  $type);
    }
    if($number === false) {
        return false;
    }
    // Comprobamos el dígito de control
    return substr("TRWAGMYFPDXBNJZSQVHLCKET", $number % 23, 1) == substr($legalCode, 8, 1);
}


function sumarDigitos(int $number) : int {
    $total = 0;

    for($index = 0; $index < strlen((string)$number); $index++) {
        $total += intval(substr((string) $number, $index, 1));
    }

    return $total;
}
