<?php declare(strict_types = 1);
/**
  * Plugin de localización de la aplicación
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Kansas\Configurable;
use System\EnvStatus;
use System\Version;
use Kansas\Application;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\View\Template;

use function array_pad;
use function strcasecmp;
use function strtolower;
use function strtoupper;
use function substr;
use function uasort;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Localization extends Configurable implements PluginInterface {

  private $appLangs;
  private $userLangs;

  public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    $application->registerCallback(Application::EVENT_PREINIT, [$this, 'appPreInit']);
    $application->registerCallback(Application::EVENT_C_VIEW, [$this, 'createView']);
  }

  /// Miembros de Kansas\Configurable\Interface
  public function getDefaultOptions(EnvStatus $environment) : array {
    return [
      'lang'      => 'es',
      'country'   => null];
  }

  public function getVersion() : Version {
    return Environment::getVersion();
  }

  public function appPreInit() { // obtener idioma del cliente
    $this->init();
  }

  public function createView($view) {
    Template::setDatacontext('lang', $this->options['lang']);
    Template::setDatacontext('country', $this->options['country']);
    Template::setDatacontext('langQ', $this->options['q']);
    Template::setDatacontext('cultureCode', (string) $this);
  }

  public function init() {
    if (isset($this->options['q'])) {
      return;
    }
    global $lang;
    $request    = Environment::getRequest();
    $uri        = $request->getUri();
    $path       = $uri->getPath();
    if (substr($path, 3, 1) == '/') {
      $l = substr($path, 1, 2);
      foreach($this->getAppLangs() as $appLang) {
        if ($appLang['country'] == null &&
            strcasecmp($l, $appLang['lang']) == 0) { // obtenemos el idioma de la url
          $this->options['lang']      = $appLang['lang'];
          $this->options['country']   = null;
          $this->options['q']         = true;
          $uri = $uri->withPath(substr($path, 3));
          break;
        }
      }
    } elseif (substr($path, 3, 1) == '-' &&
              substr($path, 6, 1) == '/') {
      $l = substr($path, 1, 2);
      $c = substr($path, 4, 2);
      foreach ($this->getAppLangs() as $appLang) {
        if (strcasecmp($l, $appLang['lang']) == 0 &&
            strcasecmp($c, $appLang['country']) == 0) { // obtenemos el idioma y la región de la url
          $this->options['lang']      = $appLang['lang'];
          $this->options['country']   = $appLang['country'];
          $this->options['q']         = true;
          $uri = $uri->withPath(substr($path, 6));
          break;
        }
      }
    }
    if (isset($this->options['q']) &&
        $this->options['q'] === true) { // Si el idioma estaba incrustado en la url, modificamos la url
      $request = $request->withUri($uri);
      Environment::setRequest($request);
    } else {
      foreach ($this->getUserLangs() as $userLang){
        foreach ($this->getAppLangs() as $appLang) {
          if (strcasecmp($userLang['lang'], $appLang['lang']) == 0 &&
              $appLang['country'] != null &&
              $userLang['country'] != null &&
              strcasecmp($userLang['country'], $appLang['country']) == 0) { // obtenemos el idioma de las preferencias del navegador del usuario
            $this->options['lang']      = $appLang['lang'];
            $this->options['country']   = $appLang['country'];
            $this->options['q']         = $userLang['q'];
            break 2;
          } elseif (strcasecmp($userLang['lang'], $appLang['lang']) == 0 &&
                    ($userLang['country'] != null &&
                     $appLang['country'] == null) ||
                    ($userLang['country'] == null &&
                     $appLang['country'] != null)) { // obtenemos el idioma de las preferencias, pero no la región
            $this->options['lang']      = $appLang['lang'];
            $this->options['country']   = $appLang['country'];
            $this->options['q']         = 0;
          }
        }
      }
    }
    if (!isset($this->options['q'])) {
      $this->options['q'] = false;
    }
    $lang = $this->options['lang']; // establecemos la variable global
    header('Content-Language: ' . (string) $this); // establecemos la cabecera
  }

  public function getAppLangs(): array {
    global $application;
    if (!isset($this->appLangs)) {
      $localizationProvider = $application->getProvider('Localization');
      $this->appLangs = $localizationProvider->getLanguages();
    }
    return $this->appLangs;
  }

  /**
   * Obtiene los idiomas del navegador del usuario, ordenados por preferencia
   *
   * @return array Lista de idiomas con los valores 'lang', 'country' y 'q', para cada idioma
   */
  public function getUserLangs() {
    if (!isset($this->userLangs)) {
      $locales = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])
                : [];
      $this->userLangs = [];
      foreach($locales as $locale) {
        list($l, $q) = array_pad(explode(';q=', $locale), 2, null);
        if ($q == null) {
          $q = 1;
        }
        $lc = explode('-', $l);
        if ($lc == null) {
            $lc = [$l];
        }
        $this->userLangs[] = [
          'lang'      => $lc[0],
          'country'   => isset($lc[1])
            ? $lc[1]
            : null,
          'q'         => floatval($q)
        ];
      }
      uasort($this->userLangs, ['self', 'compareQ']);
    }
    return $this->userLangs;
  }

  public function getLocale() : array {
    $this->init();
    return $this->options;
  }

  public function setLocale(string $lang, ?string $country = null, $q = true) {
    $this->options['lang']      = $lang;
    $this->options['country']   = $country;
    $this->options['q']         = $q;
  }

  public function __toString() : string {
    $lang = strtolower($this->options['lang']);
    if ($this->options['country'] != null) {
      $lang .= '-' . strtoupper($this->options['country']);
    }
    return $lang;
  }

  public static function compareQ($a, $b) {
    return $a['q'] - $b['q'];
  }

}
