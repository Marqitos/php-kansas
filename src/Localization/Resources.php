<?php
/**
  * Carga los recursos localizados para mensajes de error y otros
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

global $lang, $application;

// Obtenemos el idioma de la petición actual
if(!isset($lang) &&
   isset($application) &&
   $localizationPlugin = $application->hasPlugin('localization')) {
  $localizationPlugin->getLocale();
}

// Cargamos el archivo de recursos
if(isset($lang) &&
   file_exists(__DIR__ . DIRECTORY_SEPARATOR . "$lang.php")) { // Cargamos el idioma correspondiente a la petición actual
  require_once __DIR__ . DIRECTORY_SEPARATOR . "$lang.php";
} elseif (isset($lang) &&
          strlen($lang) > 2 &&
          file_exists(__DIR__ . DIRECTORY_SEPARATOR . substr($lang, 0, 2) . '.php')) { // Cargamos el idioma correspondiente a la petición actual, sin referencia de región
  require_once __DIR__ . DIRECTORY_SEPARATOR . substr($lang, 0, 2) . '.php';
} else { // Cargamos el idioma por defecto, en español
  require_once 'es.php';
}
