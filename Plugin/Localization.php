<?php
/**
 * Plugin de localización de la aplicación
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

use function strcasecmp;
use function strtolower;
use function strtoupper;
use function substr;
use function uasort;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Localization extends Configurable implements PluginInterface {

	private $appLangs;
	private $userLangs;

	public function __construct(array $options) {
		parent::__construct($options);
		global $application;
		$application->registerCallback('preinit', [$this, 'appPreInit']);
		$this->options['q'] = false;
	}

	/// Miembros de System\Configurable\Interface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
		case 'production':
		case 'development':
		case 'test':
			return [
				'lang' 		=> 'es',
				'country'	=> null];
		default:
			require_once 'System/NotSupportedException.php';
			throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}
	
	public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
	}	  

	public function appPreInit() { // obtener idioma del cliente
		if(!isset($this->options['q'])) {
			$this->init();
		}
	}

	public function init() {
		global $environment, $lang;
		$request	= $environment->getRequest();
		$uri		= $request->getUri();
		$path 		= $uri->getPath();
		if(substr($path, 3, 1) == '/') { 
			$l = substr($path, 1, 2);
			foreach($this->getAppLangs() as $appLang) {
				if($appLang['country'] == null &&
					strcasecmp($l, $appLang['lang']) == 0) { // obtenemos el idioma de la url
					$this->options['lang'] 		= $appLang['lang'];
					$this->options['country'] 	= null;
					$this->options['q'] 		= true;
					$uri = $uri->withPath(substr($path, 3));
					break;
				}
			}
		} elseif(substr($path, 3, 1) == '-' &&
			substr($path, 6, 1) == '/') { 
			$l = substr($path, 1, 2);
			$c = substr($path, 4, 2);
			foreach($this->getAppLangs() as $appLang) {
				if(strcasecmp($l, $appLang['lang']) == 0 &&
				   strcasecmp($c, $appLang['country']) == 0) { // obtenemos el idioma y la región de la url
					$this->options['lang'] 		= $appLang['lang'];
					$this->options['country'] 	= $appLang['country'];
					$this->options['q'] 		= true;
					$uri = $uri->withPath(substr($path, 6));
					break;
				}
			}
		}
		if($this->options['q'] === true) { // Si el idioma estaba incrustado en la url, modificamos la url
			$request = $request->withUri($uri);
			$environment->setRequest($request);
		} else {
			foreach($this->getUserLangs() as $userLang){
				foreach($this->getAppLangs() as $appLang) {
					if(strcasecmp($userLang['lang'], $appLang['lang']) == 0 &&
						strcasecmp($userLang['country'], $appLang['country']) == 0) { // obtenemos el idioma de las preferencias del navegador del usuario
						$this->options['lang'] 		= $appLang['lang'];
						$this->options['country'] 	= $appLang['country'];
						$this->options['q'] 		= $userLang['q'];
						break 2;
					} elseif(strcasecmp($userLang['lang'], $appLang['lang']) == 0 &&
						$userLang['country'] != null &&
						$appLang['country'] == null ) { // obtenemos el idioma de las preferencias, pero no la región
						$this->options['lang'] 		= $appLang['lang'];
						$this->options['country'] 	= $appLang['country'];
						$this->options['q'] 		= 0;
					}
				}
			}
		}
		if(!isset($this->options['q'])) {
			$this->options['q']	= false;
		}
		$lang = $this->options['lang']; // establecemos la variable global
		header('Content-Language: ' . (string) $this); // establecemos la cabecera
	}

	public function getAppLangs() {
		global $application;
		if(!isset($this->appLangs)) {
			$localizationProvider = $application->getProvider('Localization');
			$this->appLangs = $localizationProvider->getLanguages();
		}
		return $this->appLangs;
	}

	public function getUserLangs() {
		if(!isset($this->userLangs)) {
			$locales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$this->userLangs = [];
			foreach($locales as $locale) {
				list($l, $q) = array_merge(explode(';q=', $locale), [1]);
				$lc = explode('-', $l);
				$this->userLangs[] = [
					'lang' 		=> $lc[0],
					'country'	=> isset($lc[1])
								? $lc[1]
								: null,
					'q'			=> (float) $q
				];
			}
			uasort($this->userLangs, ['self', 'compareQ']);
		}
		return $this->userLangs;
	}

	public function getLocale() {
		if(!isset($this->options['q'])) {
			$this->init();
		}
		return $this->options;
	}

	public function setLocale($lang, $country, $q = true) {
		$this->options['lang'] 		= $lang;
		$this->options['country'] 	= $country;
		$this->options['q'] 		= $q;
	}
	
	public function __toString() {
		$lang = strtolower($this->options['lang']);
		if($this->options['country'] != null) {
			$lang .= '-' . strtoupper($this->options['country']);
		}
		return $lang;
	}

	public static function compareQ($a, $b) {
		return $a['q'] - $b['q'];
	}

}