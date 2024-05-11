<?php declare(strict_types = 1);
/**
 * Representa un error producido durante la conexión a la base de datos, mediante mysqli;
 */

namespace Kansas\Db;

use Exception;
use mysqli;
use Kansas\Localization\Resources;
use function sprintf;

class MysqliConnectionException extends Exception {

    public function __construct(mysqli $link = null) {
        require_once 'Kansas/Localization/Resources.php';
        if($link == null) {
            parent::__construct(Resources::DB_CONNECTION_ERROR_MESSAGE);
        } else {
            parent::__construct(sprintf(Resources::DB_CONNECTION_ERROR_FORMAT, $link->connect_errno, $link->connect_error), $link->connect_errno);
        }
    }

}
