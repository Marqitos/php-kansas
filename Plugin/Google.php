<?php
/**
 * Plugin el procesamiento de archivos javascript. Une, compacta y devuelve el codigo solicitado
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\ArgumentException;
use System\ArgumentOutOfRangeException;
use System\Version;
use Kansas\Application;
use Kansas\Controller\Index;
use Kansas\Plugin\PluginInterface;
use Google\Client;
use function Kansas\Http\Request\get as RequestGet;
use function http_build_query;
use function implode;
use function json_decode;
use function strpbrk;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Google extends Configurable implements PluginInterface {
        
    private $client;

    public function __construct(array $options = []) {
        parent::__construct($options);
        global $application;
        if($this->options['ga_tracker']) {
            require_once 'Kansas/Application.php';
            require_once 'Kansas/Controller/Index.php';
            Index::addAction('gaTracker', [self::class, 'gaAction']); // Añade una acción al controlador principal
            $application->registerCallback(Application::EVENT_PREINIT, [$this, 'appPreInit']);
            $application->registerCallback(Application::EVENT_C_VIEW, [$this, 'appCreateView']);
        }
    }
  
	/// Miembros de Kansas\Plugin\Interface
	public function getDefaultOptions(string $environment) : array {
        return [
            'api_key'	=> false,
            'cse_cx'		=> false,
            'ga_tracker'	=> false];
    }

    public function getVersion() : Version {
		global $environment;
		return $environment->getVersion();
	}

    /// Eventos de la aplicación
	public function appPreInit() {
        // Devolver router para acción de ga_tracker

    }

    public function appCreateView() {
        // Incrustar javascript de google analitycs

    }

    
    public function customSearch(string $query) {
        if(!$this->options['cse_cx']) {
            require_once 'System/ArgumentException.php';
            throw new ArgumentException('Google::cse_cx');
        }
            
        require_once 'Google/Google_Client.php';
        require_once 'Google/contrib/Google_CustomsearchService.php';

        $search = new Google_CustomsearchService($this->getClient());
        return $search->cse->listCse($query, ['cx' => $this->options['cse_cx']]);
    }
    
    protected function getClient() {
        if($this->client == null) {
            require_once 'System/ArgumentException.php';
            require_once 'Google/Client.php';
            if(!$this->options['AppName']) {
                throw new ArgumentException('Google::AppName');
            }
            if(!$this->options['api_key']) {
                throw new ArgumentException('Google::ApiKey');
            }
    
    
            $this->client = new Client();
            $this->client->setApplicationName($this->getOptions('AppName'));
            $this->client->setDeveloperKey($this->options['api_key']);
        }
        return $this->client;
    }

    public function getGeoCodeFromComponents(array $components, array $options = []) {
        if(!$this->options['api_key']) {
            require_once 'System/ArgumentException.php';
            throw new ArgumentException('Google::api_key');
        }
        $componentsValues = [];
        foreach($components as $key => $value) {
            if(strpbrk($key, ':|') || strpbrk($value, ':|')) {
                require_once 'System/ArgumentOutOfRangeException.php';
                throw new ArgumentOutOfRangeException('components');
            }
            $componentsValues[] = $key . ':' . $value;
        }
        require_once 'Kansas/Http/Request/get.php';
        $options['components']  = implode('|', $componentsValues);
        $options['key']         = $this->options['api_key'];
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($options);
        return json_decode(RequestGet($url), true);
    }
  
    public function getGeoCodeFromLatLng(float $latitude, float $longitude, array $options = []) {
        if(!$this->options['api_key']) {
            require_once 'System/ArgumentException.php';
            throw new ArgumentException('Google::api_key');
        }
        require_once 'Kansas/Http/Request/get.php';
        $options['latlng']      = $latitude . ',' . $longitude;
        $options['key']         = $this->options['api_key'];
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($options);
        return json_decode(RequestGet($url), true);
    }

}
