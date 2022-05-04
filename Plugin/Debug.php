<?php declare(strict_types = 1);
/**
 * Plugin para depuración mediante Maurina.
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2022, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use Kansas\Plugin\PluginInterface;
use Maurina\Debug as MaurinaDebug;
use System\Configurable;
use System\Version;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Debug extends Configurable implements PluginInterface {

    private $debug;

	public function __construct(array $options) {
		global $application;
		parent::__construct($options);
        require_once 'Maurina/Debug.php';
        $debug = new MaurinaDebug($this->options['server_ip'], $this->options['server_port'], $this->options['tab_captions']);
	}

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions(string $environment) : array {
        return [
            'server_ip'     => '', 
            'server_port'   => null, 
            'tab_captions'  => []];
	}

    // Miembros de PluginInterface
	public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

}