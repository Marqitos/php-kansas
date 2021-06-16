<?php declare(strict_types = 1);
/**
 * Maneja una conexión a la base de datos usando improved MySQL (mysqli)
 *
 * Description. Representa una capa de acceso a datos mediente improved MySQL
 *
 * @package Kansas
 * @author Marcos Porto
 * @since v0.1
 * PHP 5 >= 5.3.0, PHP 7
 */

namespace Kansas\Db;

use Kansas\Db\Adapter;
use Kansas\Db\MysqliConnectionException;
use Kansas\Db\MysqliException;
use mysqli;

use const MYSQLI_ASSOC;

class MysqliAdapter extends Adapter {

    private $con;

    public function __construct(string $hostname, string $username, string $password, string $database, string $charset = null) {
        $this->con = new mysqli($hostname, $username, $password, $database);

        if($this->con == null || $this->con->connect_error) {
            include_once 'Kansas/Db/MysqliConnectionException.php';
            throw new MysqliConnectionException($this->con);
        }
        if($charset != null) {
            $this->con->set_charset($charset);
        }
    }

    /**
     * Realiza una consulta a la base de datos
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|int En caso de ser una consulta tipo select, devuelve un array. En consultas insert, update, ... devuelve el número de filas afectadas. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */
    public function query(string $sql, &$id = null) {
        if($this->con->real_query($sql) &&
           $this->con->errno == 0) {
            if($this->con->field_count == 0) { // La consulta no es select
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
        if($this->con->errno != 0) {
            require_once 'Kansas/Db/MysqliException.php';
            throw new MysqliException($this->con);
        }
        return false;
    }

    /**
     * Realiza una consulta a la base de datos que solo debe devolver una fila
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|bool En caso de ser una consulta devuelva una fila, devuelve un array. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */

    public function queryRow(string $sql) {
        if($this->con->real_query($sql) &&
           $this->con->errno == 0) {
            try {
                $result = $this->con->store_result();
                if($result->num_rows == 1) { // Devolvemos el resultado en caso de que solo se devuelva una fila
                    return $result->fetch_assoc();
                }
            } finally {
                $result->close();
            }
        }
        if($this->con->errno != 0) {
            require_once 'Kansas/Db/MysqliException.php';
            throw new MysqliException($this->con);
        }
        return false;
    }

    /**
     * Remplaza los caracteres especiales en una cadena para usarla en una consulta, teniendo en cuenta el juego de caracteres utilizado
     * 
     * @param string $escapestr Cadena a verificar
     * @return string Cadena segura para la consulta
     */
    public function escape(string $escapestr) : string {
        return $this->con->real_escape_string($escapestr);
    }

}