<?php declare(strict_types = 1);
/**
  * Representa un error producido durante la conexión a la base de datos, mediante mysqli
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
  * @version    v0.6
  */

namespace Kansas\Db;

use Kansas\Localization\Resources;
use mysqli;
use RuntimeException;
use function sprintf;

class MysqliConnectionException extends RuntimeException {

    public function __construct(?mysqli $link = null) {
        require_once 'Kansas/Localization/Resources.php';
        if ($link == null) {
            parent::__construct(Resources::E_DB_CONNECTION);
        } else {
            parent::__construct(sprintf(Resources::E_DB_CONNECTION_FORMAT, $link->connect_errno, $link->connect_error), $link->connect_errno);
        }
    }

    public static function validate(?mysqli $link) {
        if ($link == null ||                // No se ha creado la conexión
            $link->connect_errno != 0) {    // Ha habído un error en la conexión
            throw new self($link);
        }
    }

}
