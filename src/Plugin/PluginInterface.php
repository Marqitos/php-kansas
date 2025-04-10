<?php declare(strict_types = 1);
/**
  * Representa un complemento de la aplicación
  *
  * Depende de la librería System
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use System\Configurable\ConfigurableInterface;
use System\Version;

require_once 'System/Configurable/ConfigurableInterface.php';
require_once 'System/Version.php';

/**
  * Representa un complemento de la aplicación,
  * que admite opciones de configuración
  */
interface PluginInterface extends ConfigurableInterface {
    /**
      * Obtiene la versión del complemento
      *
      * @return Version Versión del complemento
      */
    public function getVersion() : Version;
}
