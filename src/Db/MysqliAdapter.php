<?php declare(strict_types = 1);
/**
  * Maneja una conexión a la base de datos usando improved MySQL (mysqli)
  *
  * Description. Representa una capa de acceso a datos mediente improved MySQL
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
  * @version    v0.6
  */

namespace Kansas\Db;

use mysqli;
use Kansas\Db\Adapter;
use Kansas\Db\MysqliConnectionException;
use Kansas\Db\MysqliStmtException;
use System\ArgumentOutOfRangeException;
use System\DisposableInterface;
use System\Guid;

use function is_a;
use function is_int;
use function is_object;
use function is_string;
use function strval;
use const MYSQLI_ASSOC;

class MysqliAdapter extends Adapter implements DisposableInterface {

    private $con;
    private $disposed = false;
    const FORMAT_DATE = 'Y-m-d'; //YYYY-MM-DD
    const FORMAT_TIME = 'H:i:s'; //HH:MM:SS
    const FORMAT_DATETIME = 'Y-m-d H:i:s'; //YYYY-MM-DD HH:MM:SS

    public function __construct(string $hostname, string $username, string $password, string $database, string $charset = 'utf8mb4') {
        $this->con = new mysqli($hostname, $username, $password, $database);


        require_once 'Kansas/Db/MysqliConnectionException.php';
        MysqliConnectionException::validate($this->con);

        if ($charset != null &&
            $this->con->set_charset($charset)) {
            $this->con->query("SET NAMES $charset;");
            $this->con->query("SET CHARACTER SET $charset;");
        }
    }

    /**
     * Realiza una consulta a la base de datos
     *
     * @param string $sql Consulta sql a ejecutar
     * @return array|int En caso de ser una consulta tipo select, devuelve un array. En consultas insert, update, ... devuelve el número de filas afectadas. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */
    public function query(string $sql, &$id = null) {
        if ($this->con->real_query($sql)) {
            if ($this->con->field_count == 0) { // La consulta no es select
                $id = $this->con->insert_id;
                return $this->con->affected_rows;
            } else {
                try {
                    $result = $this->con->store_result();
                    if($this->con->errno == 0) {
                        return $result->fetch_all(MYSQLI_ASSOC);
                    }
                } finally {
                    $result->close();
                }
            }
        }

        require_once 'Kansas/Db/MysqliConnectionException.php';
        MysqliConnectionException::validate($this->con);

        return false;
    }

    public function fetch(string $sql, array $params = []): array {
        if ($stmt = $this->con->prepare($sql)) {
            require_once 'Kansas/Db/MysqliStmtException.php';
            MysqliStmtException::validate($stmt);
            if (! empty($params)) {
                list($types, $values) = self::parseParams($params);
                $stmt->bind_param($types, ...$values);
                MysqliStmtException::validate($stmt);
            }
            if ($stmt->execute()) {
                try {
                    $result = $stmt->get_result();
                    MysqliStmtException::validate($stmt);
                    return $result->fetch_all(MYSQLI_ASSOC);
                } finally {
                    $result->close();
                }
            }
            MysqliStmtException::validate($stmt);
        }

        require_once 'Kansas/Db/MysqliConnectionException.php';
        MysqliConnectionException::validate($this->con);

        return false;
    }

    /**
      * Realiza una consulta a la base de datos que solo debe devolver una fila
      *
      * @param string $sql Consulta sql a ejecutar
      * @return array|bool En caso de ser una consulta devuelva una fila, devuelve un array. Y false en caso de que la consulta esté mal formulada o se produzca un error
      */
      public function queryRow(string $sql) {
        if ($this->con->real_query($sql)) {
            try {
                $result = $this->con->store_result();
                if ($result->num_rows > 0) { // Devolvemos el resultado en caso de que solo se devuelva una fila
                    return $result->fetch_assoc();
                }
            } finally {
                $result->close();
            }
        }

        require_once 'Kansas/Db/MysqliConnectionException.php';
        MysqliConnectionException::validate($this->con);

        return false;
    }

    public function fetchRow(string $sql, array $params = []): array|false {
        if ($stmt = $this->con->prepare($sql)) {
            try {
                require_once 'Kansas/Db/MysqliStmtException.php';
                MysqliStmtException::validate($stmt);
                if (! empty($params)) {
                    list($types, $values) = self::parseParams($params);
                    $stmt->bind_param($types, ...$values);
                    MysqliStmtException::validate($stmt);
                }
                if ($stmt->execute()) {
                    try {
                        $result = $stmt->get_result();
                        MysqliStmtException::validate($stmt);
                        if ($result->num_rows > 0) { // Devolvemos el resultado en caso de que solo se devuelva una fila
                            return $result->fetch_assoc();
                        }
                    } finally {
                        $result->close();
                    }
                }
                MysqliStmtException::validate($stmt);
            } finally {
                $stmt->close();
            }
        }

        require_once 'Kansas/Db/MysqliConnectionException.php';
        MysqliConnectionException::validate($this->con);

        return false;
    }

    /**
      * Remplaza los caracteres especiales en una cadena para usarla en una consulta, teniendo en cuenta el juego de caracteres utilizado
      *
      * @param string $escapestr Cadena a verificar
      * @return string Cadena segura para la consulta
      */
    public function escape(string $escapestr): string {
        return $this->con->real_escape_string($escapestr);
    }

    protected static function parseParams(array $params) : array {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_string($param)) {
                $types .= 's';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif ($param instanceof Guid) {
                $param = $param->getHex();
                $types .= 's';
            } else {
                require_once 'System/ArgumentOutOfRangeException.php';
                throw new ArgumentOutOfRangeException('params');
            }
            $values[] = $param;
        }
        return [$types, $values];
    }

    /**
      * Devuelve una cadena a partir de un objeto, para poder almacenarlo en la base de datos
      *
      * @param  mixed   $object             Objeto a verificar
      * @param  ?string $type               (Opcional) Tipo de dato que se desea guardar
      * @return string                      Cadena segura para la consulta
      * @throws ArgumentOutOfRangeException Si el objeto no se puede procesar;
      */
    public function format($object, ?string $type = null) : string {
        $result = '';
        if ($object === null) {
            if ($type == self::TYPE_NOT_NULL) {
                $result = "''";
            } else {
                $result = 'NULL';
            }
        } elseif (is_a($object, 'DateTime')) {
            if ($type == self::TYPE_DATE) {
                $result = "'" . $object->format(self::FORMAT_DATE) . "'";
            } elseif ($type == self::TYPE_TIME) {
                $result = "'" . $object->format(self::FORMAT_TIME) . "'";
            } else {
                $result = "'" . $object->format(self::FORMAT_DATETIME) . "'";
            }
        } elseif (is_int($object)) {
            $result = strval($object);
        } elseif (is_string($object)) {
            $result = "'" . $this->con->real_escape_string($object) . "'";
        } elseif (is_object($object)) {
            $result = "'" . $this->con->real_escape_string($object->__toString()) . "'";
        } else {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('object');
        }
        return $result;
    }

## Miembros de System\DisposableInterface
    /**
      * @inheritDoc
      *
      * @return void
      */
    public function dispose() : void {
        if (!$this->disposed) {
            mysqli_close($this->con);
        }
        $this->disposed = true;
    }

    /**
      * @inheritDoc
      *
      * @return bool
      */
      public function isDisposed() : bool {
        return $this->disposed;
    }
## -- DisposableInterface

    public function __destruct() {
        $this->dispose();
    }

}
