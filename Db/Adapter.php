<?php
/**
 * Maneja una conexión a la base de datos usando mysqli
 *
 * Description. Representa una capa de acceso a datos MySQL
 *
 * @package Kansas
 * @author Marcos Porto
 * @since v0.1
 * PHP 5 >= 5.3.0, PHP 7
 */

namespace Kansas\Db;

use Exception;
use System\ArgumentOutOfRangeException;
use System\NotSupportedException;
use mysqli;

class Adapter {

    private $con; 

    public function __construct(array $options) {
        if(!isset($options['driver'])) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('options', 'El array options debe contener la clave driver');
        }
        if($options['driver'] == 'mysqli') { // Conexión a la BBDD mediante mysqli
            $this->con = new mysqli($options['hostname'], $options['username'], $options['password'], $options['database']);

            if($this->con == null) {
                die('Error de Conexión con la base de datos.');
            } elseif ($this->con->connect_error) {
                die('Error de Conexión (' . $this->con->connect_errno . ') ' . $this->con->connect_error);
            }
            if(isset($options['charset'])) {
                $this->con->set_charset($options['charset']);
            }
        } else {
            require_once 'System/NotSupportedException.php';
            throw new NotSupportedException();
        }
    }

    /**
     * Realiza una consulta a la base de datos
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|int|bool En caso de ser una consulta tipo select, devuelve un array. En consultas insert, update, ... devuelve el número de filas afectadas. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */
    public function query($sql) {
        if($this->con->real_query($sql)) {
            if($this->con->field_count == 0) { // La consulta no es select
                return ($this->con->errno == 0)
                    ? $this->con->affected_rows
                    : false;
            }
            try {
                $result = $this->con->store_result();
                $rows   = $result->fetch_all(MYSQLI_ASSOC);
            } catch(Exception $ex) {
                $rows   = false;
            } finally {
                $result->close();
            }
            return $rows;
        }
        return false;
    }

    /**
     * Realiza una consulta a la base de datos que solo debe devolver una fila
     * 
     * @param string $sql Consulta sql a ejecutar
     * @return array|bool En caso de ser una consulta devuelva una fila, devuelve un array. Y false en caso de que la consulta esté mal formulada o se produzca un error
     */

    public function queryRow($sql) {
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
        return false;
    }

    /**
     * Remplaza los caracteres especiales en una cadena para usarla en una consulta, teniendo en cuenta el juego de caracteres utilizado
     * 
     * @param string $escapestr Cadena a verificar
     * @return string Cadena segura para la consulta
     */
    public function escape($escapestr) {
        return $this->con->real_escape_string($escapestr);
    }

}