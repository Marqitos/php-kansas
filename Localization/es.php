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
    public const ARGUMENT_OUT_OF_RANGE_EXCEPTION_ADAPTER_OPTIONS_CONTAINS_FORMAT = 'El adaptador %s requiere la opción de configuración "%s"';
    public const DB_CONNECTION_ERROR_MESSAGE = 'Error de conexión con la base de datos.';
    public const DB_CONNECTION_ERROR_FORMAT = 'Error de conexión (%d: %s).';
    public const IO_EXCEPTION_NO_TEMP_DIR_MESSAGE = 'No se puede determinar un directorio temporal, especifique uno manualmente.';
    public const LOADER_NOT_CAST_EXCEPTION_FORMAT = 'El Plugin de nombre "%s" no es del tipo esperado, se esperaba: "%s"';
    public const LOADER_NOT_FOUNT_EXCEPTION_FORMAT = 'El Plugin de nombre "%s" no se encuentra en el registro, usando las rutas:';
    public const NOT_IMPLEMENTED_EXCEPTION_ACTION_FORMAT = 'No se ha implementado %s en el controlador %s.';
    public const API_OPTIONS_METHOD_RESERVED = 'El método OPTIONS es de uso reservado';

    const FILE_UPLOAD_ERRORS = [
        UPLOAD_ERR_OK         => 'El archivo se ha subido con exito',
        UPLOAD_ERR_INI_SIZE   => 'El tamaño del archivo supera el maximo permitido (directiva upload_max_filesize en php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'El tamaño del archivo supera el maximo permitido (directiva MAX_FILE_SIZE en el formulario HTML)',
        UPLOAD_ERR_PARTIAL    => 'El archivo solo se ha subido parcialmente',
        UPLOAD_ERR_NO_FILE    => 'El archivo no se ha subido',
        UPLOAD_ERR_NO_TMP_DIR => 'No se encuentra el archivo temporal en el servidor',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el sistema de archivos del servidor',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP ha interrumpido la carga del archivo.',
    ];

}

global $lang;
if(!isset($lang)) {
    $lang = 'es';
}

/* en
    const ERROR_MESSAGES = [
        UPLOAD_ERR_OK         => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
*/
