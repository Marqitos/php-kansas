<?php declare(strict_types = 1);
/**
  * Excepción que se produce cuando un plugin no es del tipo esperado
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Loader;

use System\Collections\KeyNotFoundException;
use Kansas\Localization\Resources;
use function sprintf;

require_once 'System/Collections/KeyNotFoundException.php';

/**
  * Excepción que se produce cuando un plugin no es del tipo esperado
  */
class NotCastException extends KeyNotFoundException {

    public function __construct(string $name, string $type) {
        require_once 'Kansas/Localization/Resources.php';
        $message = sprintf(Resources::LOADER_NOT_CAST_EXCEPTION_FORMAT, $name, $type);
        parent::__construct($message);
    }

}
