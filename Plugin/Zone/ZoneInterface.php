<?php declare(strict_types = 1 );
/**
 * Representa un complemento que se hace cargo de una ruta de la aplicación
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin\Zone;

use Kansas\Plugin\PluginInterface;

require_once 'Kansas/Plugin/PluginInterface.php';

/**
 * Representa un plugin de zona
 */
interface ZoneInterface extends PluginInterface {
    /**
     * Obtiene la ruta inicial de la zona
     *
     * @return string con la ruta inicial
     */
    public function getBasePath() : string;

    /**
     * Realiza la configuración especifica de la zona
     */
    public function setUp() : void;
}