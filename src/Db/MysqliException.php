<?php declare(strict_types = 1);
/**
 * Representa un error producido durante una consulta MySQL mediante mysqli;
 */


namespace Kansas\Db;

use Exception;
use mysqli;

class MysqliException extends Exception {

    public function __construct(mysqli $link) {
        parent::__construct($link->error, $link->errno);
    }

}
