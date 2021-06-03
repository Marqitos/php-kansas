<?php declare(strict_types = 1);
/**
 * Representa y crea una conexión a la base de datos
 *
 * Description. Representa una capa de acceso a datos MySQL
 *
 * @package Kansas
 * @author Marcos Porto
 * @since v0.1
 * PHP 5 >= 5.3.0, PHP 7
 */

namespace Kansas\Db;

use System\ArgumentOutOfRangeException;
use System\NotSupportedException;
use Kansas\Db\MysqliAdapter;
use Kansas\Localization\Resources;

use function sprintf;

abstract class Adapter {

    public const DRIVER_MYSQLI = 'mysqli'; // Improved MySQL
    
    private const DRIVER_OPTIONS    = [
        self::DRIVER_MYSQLI         => [
            'hostname',
            'username',
            'password',
            'database']];

    public static function Create(string $driver, array $options) : self {
        if($driver == self::DRIVER_MYSQLI) {
            foreach(self::DRIVER_OPTIONS[self::DRIVER_MYSQLI] as $key) {
                if(!isset($options[$key])) {
                    require_once 'System/ArgumentOutOfRangeException.php';
                    require_once 'Kansas/Localization/Resources.php'; // TODO: Localizar mensaje de error
                    throw new ArgumentOutOfRangeException('options', sprintf(Resources::ARGUMENT_OUT_OF_RANGE_EXCEPTION_ADAPTER_OPTIONS_CONTAINS_FORMAT, $driver, $key));
                }
            }
            $hostname   = (string) $options['hostname'];
            $username   = (string) $options['username'];
            $password   = (string) $options['password'];
            $database   = (string) $options['database'];
            $charset    = isset($options['charset'])
                        ? (string) $options['charset']
                        : null;
            return new MysqliAdapter($hostname, $username, $password, $database, $charset);
        }
        require_once 'System/NotSupportedException.php';
        throw new NotSupportedException();
    }

    /**
     * Realiza una consulta a la base de datos
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|int En caso de ser una consulta tipo select, devuelve un array. En consultas insert, update, ... devuelve el número de filas afectadas. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */
    public abstract function query(string $sql, &$id = null);

    /**
     * Realiza una consulta a la base de datos que solo debe devolver una fila
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|bool En caso de ser una consulta devuelva una fila, devuelve un array. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */

    public abstract function queryRow(string $sql);

    /**
     * Remplaza los caracteres especiales en una cadena para usarla en una consulta, teniendo en cuenta el juego de caracteres utilizado
     * 
     * @param string $escapestr Cadena a verificar
     * @return string Cadena segura para la consulta
     */
    public abstract function escape(string $escapestr) : string;

}