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
    public const ARGUMENT_OUT_OF_RANGE_ARRAY_STRING_EXPECTED_MESSAGE = 'Se esperaba una cadena o un array.';
    public const IO_EXCEPTION_NO_TEMP_DIR_MESSAGE = 'No se puede determinar un directorio temporal, especifique uno manualmente.';
    public const NOT_ACTION_IMPLEMENTED_EXCEPTION_FORMAT = 'No se ha implementado %s en el controlador %s.';

}

global $lang;
if(!isset($lang)) {
    $lang = 'es';
}