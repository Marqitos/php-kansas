<?php

namespace Kansas\Db;

use mysqli;

class Adapter {

    private $con; 

    public function __construct(array $options) {
        switch($options['driver']) {
            case 'mysqli':
                $this->con = new mysqli($options['hostname'], $options['username'], $options['password'], $options['database']);
    
                if ($this->con->connect_error) {
                    die('Error de Conexión (' . $con->connect_errno . ') ' . $con->connect_error);
                }
                if(isset($options['charset'])) {
                    $this->con->set_charset($options['charset']);
                }
                break;
        }

    }

    public function query($sql) {
        if($this->con->real_query($sql)) {
            if ($this->con->field_count) {
                $result = $this->con->store_result();
                try {
                    return $result->fetch_all(MYSQLI_ASSOC);
                } finally {
                    $result->close();
                }
            }
            return true;
        }
        return false;
    }

    public function escape($escapestr) {
        return $this->con->real_escape_string($escapestr);
    }

}