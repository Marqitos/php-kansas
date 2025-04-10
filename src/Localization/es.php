<?php
/**
  * Contiene cadenas en español para mensajes de error y otros
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  * @version    v0.6
  */

namespace Kansas\Localization;

class Resources {
    public const ARGUMENT_OUT_OF_RANGE_EXCEPTION_ARRAY_STRING_EXPECTED_MESSAGE = 'Se esperaba una cadena o un array.';
    public const ARGUMENT_OUT_OF_RANGE_EXCEPTION_ADAPTER_OPTIONS_CONTAINS_FORMAT = 'El adaptador %s requiere la opción de configuración "%s"';
    public const E_DB_CONNECTION = 'Error de conexión con la base de datos.';
    public const E_DB_CONNECTION_FORMAT = 'Error de conexión (%d: %s).';
    public const E_LINE_ERROR_FORMAT = "Línea %s en %s";
    public const E_FILE_ERROR_FORMAT = "En %s";
    public const IO_EXCEPTION_NO_TEMP_DIR_MESSAGE = 'No se puede determinar un directorio temporal, especifique uno manualmente.';
    public const LOADER_NOT_CAST_EXCEPTION_FORMAT = 'El Plugin de nombre "%s" no es del tipo esperado, se esperaba: "%s"';
    public const LOADER_NOT_FOUNT_EXCEPTION_FORMAT = 'El Plugin de nombre "%s" no se encuentra en el registro, usando las rutas:';
    public const NOT_IMPLEMENTED_EXCEPTION_ACTION_FORMAT = 'No se ha implementado %s en el controlador %s.';
    public const API_OPTIONS_METHOD_RESERVED = 'El método OPTIONS es de uso reservado';
    public const BROWSER_HAPPY_HTML = 'Está utilizando un navegador <strong>anticuado</strong>. Por favor, <a href="http://browsehappy.com/">actualice su navegador</a> para mejorar su experiencia.';
    public const COUNTRIES = [
        'AT'    => 'Austria',
        'BE'    => 'Bélgica',
        'BG'    => 'Bulgaria',
        'CY'    => 'Chipre',
        'CZ'    => 'Chequia',
        'DE'    => 'Alemania',
        'DK'    => 'Dinamarca',
        'EE'    => 'Estonia',
        'EL'    => 'Grecia',
        'ES'    => 'España',
        'FI'    => 'Finlandia',
        'FR'    => 'Francia',
        'HR'    => 'Croacia',
        'HU'    => 'Hungría',
        'IE'    => 'Irlanda',
        'IT'    => 'Italia',
        'LT'    => 'Lituania',
        'LU'    => 'Luxemburgo',
        'LV'    => 'Letonia',
        'MT'    => 'Malta',
        'NL'    => 'Países Bajos',
        'PL'    => 'Polonia',
        'PT'    => 'Portugal',
        'RO'    => 'Rumania',
        'SE'    => 'Suecia',
        'SI'    => 'Eslovenia',
        'SK'    => 'Eslovaquia',
        'XI'    => 'Irlanda del Norte'
    ];

    public const ES_PROVINCES = [
        1   => 'Álava',
        2   => 'Albacete',
        3   => 'Alicante',
        4   => 'Almería',
        5   => 'Ávila',
        6   => 'Badajoz',
        7   => 'Baleares',
        8   => 'Barcelona',
        9   => 'Burgos',
        10  => 'Cáceres',
        11  => 'Cádiz',
        12  => 'Castellón',
        13  => 'Ciudad Real',
        14  => 'Córdoba',
        15  => 'A Coruña',
        16  => 'Cuenca',
        17  => 'Girona',
        18  => 'Granada',
        19  => 'Guadalajara',
        20  => 'Guipúzcoa',
        21  => 'Huelva',
        22  => 'Huesca',
        23  => 'Jaén',
        24  => 'León',
        25  => 'Lleida',
        26  => 'La Rioja',
        27  => 'Lugo',
        28  => 'Madrid',
        29  => 'Málaga',
        30  => 'Murcia',
        31  => 'Navarra',
        32  => 'Ourense',
        33  => 'Asturias',
        34  => 'Palencia',
        35  => 'Las Palmas',
        36  => 'Pontevedra',
        37  => 'Salamanca',
        38  => 'Santa Cruz de Tenerife',
        39  => 'Cantabria',
        40  => 'Segovia',
        41  => 'Sevilla',
        42  => 'Soria',
        43  => 'Tarragona',
        44  => 'Teruel',
        45  => 'Toledo',
        46  => 'Valencia',
        47  => 'Valladolid',
        48  => 'Vizcaya',
        49  => 'Zamora',
        50  => 'Zaragoza',
        51  => 'Ceuta',
        52  => 'Melilla'
    ];

    // Mensajes de error de la API
    const E_INTERNAL_SERVER_ERROR   = 'Error interno del servidor';
    const E_MESSAGES    = [
        0x0000  => 'Operación realizada correctamente',
        // Captura de datos
        0x0001  => 'No se han recibido datos',
        0x0002  => 'Los datos no tiene formato JSON',
        // Validación de permisos
        0x0004  => 'No se ha recibido un token de validación',
        0x0008  => 'La sesión no es válida, por favor reinicie sesión',
        0x000C  => 'La sesión ha caducado, por favor reinicie sesión',
        0x0010  => 'No tiene permisos para realizar la acción',
        0x0020  => 'Debe renovar el token',
        // Errores internos del servidor
        0x0014  => self::E_INTERNAL_SERVER_ERROR, // Error no controlado en el servidor.
        0x0018  => self::E_INTERNAL_SERVER_ERROR, // Error relacionado con la base de datos.
        0x001C  => self::E_INTERNAL_SERVER_ERROR, // Error producido por un componente.
        // Errores genéricos de validación de datos
        0x0040  => 'El nombre de usuario es obligatorio',
        0x0080  => 'La contraseña es obligatoria',
        0x0100  => 'Los credenciales del usuario no son correctos',
        0x0200  => 'El e-mail es obligatorio',
        0x0400  => 'El formato del e-mail no es válido',
        0x0800  => 'El teléfono es obligatorio',
        0x1000  => 'El formato del número de teléfono no es valido'
    ];

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
    const BROWSER_HAPPY_HTML    = 'You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.';
    const FILE_UPLOAD_ERRORS    = [
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
