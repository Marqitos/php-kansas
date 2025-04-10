<?php declare(strict_types = 1);
/**
  * Define los posibles errores de la api para POST raw
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas\API;

/**
  * Lista de errores de la API de silvalvi para recibir datos en formato JSON
  */
class APIJsonErrors {
    const E_NO_DATA         = 0x0001; // No se han recibido datos
    const E_DATA_NO_JSON    = 0x0002; // Los datos no tiene formato JSON
    const FILTER_JSON       = 0x0003; // Filtro con todos los valores posibles
}
