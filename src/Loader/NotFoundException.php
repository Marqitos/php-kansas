<?php declare(strict_types = 1);
/**
  * Excepción que se produce cuando no se puede encontrar un plugin.
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Loader;

use System\Collections\KeyNotFoundException;
use Kansas\Localization\Resources;
use function implode;
use function sprintf;
use const PATH_SEPARATOR;

require_once 'System/Collections/KeyNotFoundException.php';
/**
  * Excepción que se produce cuando no se puede encontrar un plugin.
  */
class NotFoundException extends KeyNotFoundException {

    public function __construct(string $name, array $registry) {
        require_once 'Kansas/Localization/Resources.php';
        $message = sprintf(Resources::LOADER_NOT_FOUNT_EXCEPTION_FORMAT, $name);
        foreach ($registry as $prefix => $paths) {
            $message .= "\n$prefix: " . implode(PATH_SEPARATOR, $paths);
        }
        parent::__construct($message);
    }
}
