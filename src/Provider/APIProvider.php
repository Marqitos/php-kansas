<?php declare(strict_types = 1);
/**
  * Proveedor que realiza peticiones a una API
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.6
  */

namespace Kansas\Provider;

/**
  * Proveedor de peticiones a la API
  */
class APIProvider {

  protected $cache;

  /**
    * Crea una nueva instancia del proveedor
    *
    * Crea un cache en caso de estar configurado
    */
  public function __construct() {
    global $application;
    $this->cache = $application->hasPlugin('BackendCache');
  }

  /**
    * Obtiene el idioma para realizar la petición
    *
    * @param string $lang
    * @return void
    */
  protected function fillLang(string &$lang = null) : void {
    if($lang == null) {
      global $application, $lang;

      // Obtenemos el idioma de la petición actual
      if(!isset($lang) &&
          $localizationPlugin = $application->hasPlugin('localization')) {
          $localizationPlugin->getLocale();
      }
    }

    global $options;
    if (!$lang) {
      // Establecemos el idioma por defecto
      $lang = $options['lang'];
    }
  }

}
