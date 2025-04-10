<?php declare(strict_types = 1);
/**
  * Representa y crea una conexión a la base de datos
  *
  * Description. Representa una capa de acceso a datos MySQL
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
  * @version    v0.6
  */

namespace Kansas\Db;

use System\ArgumentOutOfRangeException;
use System\NotSupportedException;
use System\DisposableInterface;
use Kansas\Db\MysqliAdapter;
use Kansas\Localization\Resources;

use function sprintf;

require_once 'System/DisposableInterface.php';

abstract class Adapter implements DisposableInterface {

    public const TYPE_DATE = 'DATE';
    public const TYPE_TIME = 'TIME';
    public const TYPE_NOT_NULL = 'NOTNULL';

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
                    require_once 'Kansas/Localization/Resources.php';
                    throw new ArgumentOutOfRangeException('options', sprintf(Resources::ARGUMENT_OUT_OF_RANGE_EXCEPTION_ADAPTER_OPTIONS_CONTAINS_FORMAT, $driver, $key));
                }
            }
            $hostname   = (string) $options['hostname'];
            $username   = (string) $options['username'];
            $password   = (string) $options['password'];
            $database   = (string) $options['database'];

            if (isset($options['charset'])) {
                $charset = (string) $options['charset'];
                return new MysqliAdapter($hostname, $username, $password, $database, $charset);
            } else {
                return new MysqliAdapter($hostname, $username, $password, $database);
            }
        }
        require_once 'System/NotSupportedException.php';
        throw new NotSupportedException();
    }

## Miembros de System\DisposableInterface
    /**
      * Libera los recursos de la conexión con la base de datos
      *
      * @return void
      */
    abstract public function dispose() : void;

    /**
      * @inheritDoc
      *
      * @return bool
      */
    abstract public function isDisposed() : bool;
## -- DisposableInterface

    /**
      * Realiza una consulta a la base de datos
      *
      * @param string $sql Consulta sql a ejecutar
      * @return array|int En caso de ser una consulta tipo select, devuelve un array. En consultas insert, update, ... devuelve el número de filas afectadas. Y false en caso de que la consulta esté mal formulada o se produzca un error
      */
    abstract public function query(string $sql, &$id = null);

    /**
      * Realiza una consulta a la base de datos que solo debe devolver una fila
      *
      * @param string $sql Consulta sql a ejecutar
      * @return array|bool En caso de ser una consulta devuelva una fila, devuelve un array. Y false en caso de que la consulta esté mal formulada o se produzca un error
      */
    abstract public function fetchRow(string $sql, array $params = []): array|false;

    abstract public function fetch(string $sql, array $params = []): array;

    /**
      * Remplaza los caracteres especiales en una cadena para usarla en una consulta, teniendo en cuenta el juego de caracteres utilizado
      *
      * @param string $escapestr Cadena a verificar
      * @return string Cadena segura para la consulta
      */
    abstract public function escape(string $escapestr) : string;

}
