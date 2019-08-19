<?php
namespace Kansas\Plugin;

use System\Configurable;
use Kansas\Plugin\Zone\ZoneInterface;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/Zone/ZoneInterface.php';

/**
 * Implementación abstracta de un plugin de zona (Kansas\Plugin\Zone\ZoneInterface)
 */
abstract class AbstractZone extends Configurable implements ZoneInterface {
  
  protected $zones;

  /// Contructor
  /**
   * Establece la contiguración e inicializa la clase
   *
   * @param array $options
   */
  public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    $this->zones = $application->getPlugin('zones');
    $this->zones->addZone($this);
    $application->registerCallback('preinit', [$this, 'appPreInit']);
  }
  
  public function appPreInit() { // añadir router
    $zone = $this->zones->getZone();
    $this->onAppPreInit($zone);
  }

  protected abstract function onAppPreInit($zone);
  
  /// Miembros de ZoneInterface
  /**
   * Obtiene la ruta inicial de la zona
   *
   * @return string con la ruta inicial
   */  
  public function getBasePath() {
    return $this->options['base_path'];
  }
    
}