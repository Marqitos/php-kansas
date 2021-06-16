<?php
/**
 * Contiene cadenas en español para mensajes de error y otros
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Localization;

class Resources {
    public const ARGUMENT_OUT_OF_RANGE_EXCEPTION_ARRAY_STRING_EXPECTED_MESSAGE = 'Se esperaba una cadena o un array.';
    public const ARGUMENT_OUT_OF_RANGE_EXCEPTION_ADAPTER_OPTIONS_CONTAINS_FORMAT = 'El adaptardor %s requiere la opción de configuración "%s"';
    public const DB_CONNECTION_ERROR_MESSAGE = 'Error de conexión con la base de datos.';
    public const DB_CONNECTION_ERROR_FORMAT = 'Error de conexión (%d: %s).';
    public const IO_EXCEPTION_NO_TEMP_DIR_MESSAGE = 'No se puede determinar un directorio temporal, especifique uno manualmente.';
    public const NOT_IMPLEMENTED_EXCEPTION_ACTION_FORMAT = 'No se ha implementado %s en el controlador %s.';

}

global $lang;
if(!isset($lang)) {
    $lang = 'es';
}