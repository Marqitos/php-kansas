<?php declare(strict_types = 1);
/**
  * Representa un error producido durante una consulta MySQL mediante mysqli_stmt;
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */


namespace Kansas\Db;

use RuntimeException;
use mysqli_stmt;

class MysqliStmtException extends RuntimeException {

    public function __construct(mysqli_stmt $stmt) {
        parent::__construct($stmt->error, $stmt->errno);
    }

    public static function validate(mysqli_stmt $stmt) {
        if ($stmt->errno != 0) {
            throw new self($stmt);
        }
    }

}
