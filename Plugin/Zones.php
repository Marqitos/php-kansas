<?php
/**
 * Plugin para clasificar varias rutas base con formas de interaccion diferentes (Web html, API, ...).
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\NotSupportedException;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\Plugin\Zone\ZoneInterface;

use function System\String\startWith;

require_once 'Kansas/Plugin/Zone/ZoneInterface.php';
require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Zones extends Configurable implements PluginInterface {
  
	// Campos
	private $zones = [];
	private $zone;

	/**
	 * Establece la contiguración e inicializa la clase
	 *
	 * @param array $options
	 */
	public function __construct(array $options) {
		parent::__construct($options);
		global $application;
		$application->registerCallback('preinit', [$this, 'appPreInit']);
	}

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
		case 'production':
		case 'development':
		case 'test':
			return [];
		default:
			require_once 'System/NotSupportedException.php';
			throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}

	public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}
	
	public function appPreInit() {
		$this->getZone();
	}
	
	/// Metodos publicos
	/**
	 * Obtiene la zona actual,
	 * y realiza la configuración si hay una zona
	 *
	 * @return mixed Kansas\Plugin\Zone\ZoneInterface o false
	 */
	public function getZone() {
		if($this->zone === null) {
			global $environment;
			$path = trim($environment->getRequest()->getUri()->getPath(), '/');
			$this->zone = false;
			require_once 'System/String/startWith.php';
			foreach($this->zones as $basePath => $zone) {
				if(startWith($path, $basePath)) {
					$this->zone = $zone;
					$this->zone->setUp();
					break;
				}
			}
		}
		return $this->zone;
	}
	
	/**
	 * Agrega una nueva zona
	 *
	 * @param ZoneInterface $zone Zona a agregar
	 * @return void
	 */
	public function addZone(ZoneInterface $zone) {
		$this->zones[$zone->getBasePath()] = $zone;
		if($this->zone === false) { // resetea la zona actual
			unset($this->zone);
			$this->getZone();
		}
	}

}