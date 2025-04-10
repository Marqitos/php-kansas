<?php declare(strict_types = 1);
/**
  * Lista de paises registrados en la Unión Europea para operaciones transfronterizas de bienes y servicios.
  * Utilizado para validar los DNI, ... respectivos de cada pais
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas;

use Kansas\Localization\Resources;

require_once 'Kansas/Localization/Resources.php';

// Patrónes comunes
$P8D = '/^[0-9]{8}$/'; // Un bloque de 8 cifras
$P9D = '/^[0-9]{9}$/'; // Un bloque de 9 cifras
$P10D = '/^[0-9]{10}$/'; // Un bloque de 10 cifras
$P11D = '/^[0-9]{11}$/'; // Un bloque de 11 cifras

// https://ec.europa.eu/taxation_customs/vies/faq.html?locale=es
/* 9: Una cifra
   X: Una letra o una cifra
   S: Una letra; una cifra; "+" o "*"
   L: Una letra
*/
return [
    'AT'    => [
        'name'  => Resources::COUNTRIES['AT'],
        'patern'=> '/^U[0-9]{8}$/'], // ATU99999999 - Un bloque de 9 caracteres
    'BE'    => [
        'name'  => Resources::COUNTRIES['BE'],
        'patern'=> '/^[0-1][0-9]{9}$/'], // BE0999999999, BE1999999999 - Un bloque de 10 cifras, empezando por 0 o 1
    'BG'    => [
        'name'  => Resources::COUNTRIES['BG'],
        'patern'=> '/^[0-9]{9,10}$/'], // BG999999999, BG9999999999 - Un bloque de 9 cifras o un bloque de 10 cifras
    'CY'    => [
        'name'  => Resources::COUNTRIES['CY'],
        'patern'=> '/^[0-9]{8,10}[A-Z]$/'], // CY99999999L - Un bloque de 9 caracteres
    'CZ'    => [
        'name'  => Resources::COUNTRIES['CZ'],
        'patern'=> '/^[0-9]{8,10}$/'], // CZ99999999, CZ999999999, CZ9999999999 - Un bloque de 8, 9 o 10 cifras
    'DE'    => [
        'name'  => Resources::COUNTRIES['DE'],
        'patern'=> $P9D], // DE999999999 - Un bloque de 9 cifras
    'DK'    => [
        'name'  => Resources::COUNTRIES['DK'],
        'patern'=> '/^[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/'], // DK99 99 99 99 - Cuatro bloques de 2 cifras
    'EE'    => [
        'name'  => Resources::COUNTRIES['EE'],
        'patern'=> $P9D], // EE999999999 - Un bloque de 9 cifras
    'EL'    => [
        'name'  => Resources::COUNTRIES['EL'],
        'patern'=> $P9D], // EL999999999 - Un bloque de 9 cifras
    'ES'    => [
        'name'  => Resources::COUNTRIES['ES'],
        'patern'=> '/(^[A-Z]{1}[0-9]{8}$)|(^[0-9]{8}[A-Z]{1}$)|(^[A-Z]{1}[0-9]{7}[A-Z]{1}$)/'], // ESX9999999X - Un bloque de 9 caracteres
        // El primer carácter y el último deben ser alfabéticos o numéricos; pero no pueden ser los dos numéricos.
    'FI'    => [
        'name'  => Resources::COUNTRIES['FI'],
        'patern'=> $P8D], // FI99999999 - Un bloque de 8 cifras
    'FR'    => [
        'name'  => Resources::COUNTRIES['FR'],
        'patern'=> '/^[A-Z0-9]{2} [0-9]{9}$/'], // FRXX 999999999 - Un bloque de 2 caracteres y un bloque de 9 cifras
    'HR'    => [
        'name'  => Resources::COUNTRIES['HR'],
        'patern'=> $P11D], // HR99999999999 - Un bloque de 11 cifras
    'HU'    => [
        'name'  => Resources::COUNTRIES['HU'],
        'patern'=> $P8D], // HU99999999 - Un bloque de 8 cifras
    'IE'    => [
        'name'  => Resources::COUNTRIES['IE'],
        'patern'=> '/(^[0-9]{1}[A-Z0-9\+\*]{1}[0-9]{5}[A-Z]{1}$)|(^[0-9]{7}WI$)$/'], // IE9S99999L, IE9999999WI - Un bloque de 8 caracteres o un bloque de 9 caracteres
    'IT'    => [
        'name'  => Resources::COUNTRIES['IT'],
        'patern'=> $P11D], // IT99999999999 - Un bloque de 11 cifras
    'LT'    => [
        'name'  => Resources::COUNTRIES['LT'],
        'patern'=> '/(^[0-9]{9}$)|(^[0-9]{12}$)/'], // LT999999999, LT999999999999 - Un bloque de 9 cifras o un bloque de 12 cifras
    'LU'    => [
        'name'  => Resources::COUNTRIES['LU'],
        'patern'=> $P8D], // LU99999999 - Un bloque de 8 cifras
    'LV'    => [
        'name'  => Resources::COUNTRIES['LV'],
        'patern'=> $P11D], // LV99999999999 - Un bloque de 11 cifras
    'MT'    => [
        'name'  => Resources::COUNTRIES['MT'],
        'patern'=> $P8D], // MT99999999 - Un bloque de 8 cifras
    'NL'    => [
        'name'  => Resources::COUNTRIES['NL'],
        'patern'=> '/^[A-Z0-9\+\*]{12}$/'], // NLSSSSSSSSSSSS - Un bloque de 12 caracteres
    'PL'    => [
        'name'  => Resources::COUNTRIES['PL'],
        'patern'=> $P10D], // PL9999999999 - Un bloque de 10 cifras
    'PT'    => [
        'name'  => Resources::COUNTRIES['PT'],
        'patern'=> $P9D], // PT999999999 - Un bloque de 9 cifras
    'RO'    => [
        'name'  => Resources::COUNTRIES['RO'],
        'patern'=> '/^[0-9]{2,10}$/'], // RO999999999 - Un bloque de mínimo 2 cifras y máximo 10 cifras
    'SE'    => [
        'name'  => Resources::COUNTRIES['SE'],
        'patern'=> '/^[0-9]{12}$/'], // SE999999999999 - Un bloque de 12 cifras
    'SI'    => [
        'name'  => Resources::COUNTRIES['SI'],
        'patern'=> $P8D], // SI99999999 - Un bloque de 8 cifras
    'SK'    => [
        'name'  => Resources::COUNTRIES['SK'],
        'patern'=> $P10D], // SK9999999999 - Un bloque de 10 cifras
    'XI'    => [
        'name'  => Resources::COUNTRIES['XI'],
        'patern'=> '/(^[0-9]{3} [0-9]{4} [0-9]{2}$)|(^[0-9]{3} [0-9]{4} [0-9]{2} [0-9]{3}$)|(^GD[0-9]{3}$)|(^HA[0-9]{3}$)/'],
        // XI999 9999 99, XI999 9999 99 999, XIGD999, XIHA999 1 block of 3 digits, 1 block of 4 digits and 1 block of 2 digits; or the above followed by a block of 3 digits; or 1 block of 5 characters
        // XI999 9999 99 999    Identifica a los operadores del sector.
        // XIGD999              Identifica a los ministerios.
        // XIHA999              Identifica a las autoridades sanitarias.
];
