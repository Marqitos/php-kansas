<?php declare(strict_types = 1);
/**
  * Plugin para registrar los datos del dispositivo desde que se accede
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Psr\Http\Message\RequestInterface;
use Kansas\Configurable;
use System\EnvStatus;
use System\NotSupportedException;
use System\Version;
use Kansas\Application;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\TrailResources;

use function Kansas\Request\getTrailData;
use function Kansas\Request\getUserAgentData;
use function Kansas\Request\getRemoteAddressData;
use function error_get_last;
use function getallheaders;
use function ignore_user_abort;
use function ini_set;
use function serialize;

// Tracker basado en bbclone
class Tracker extends Configurable implements PluginInterface {

    private $trail;
    private $router;

    /// Constructor
    public function __construct(array $options) {
        global $application;
        parent::__construct($options);
        $headers = getallheaders();
        if (isset($headers['DNT']) &&
            $headers['DNT'] == '1') { // Deshabilita el almacenaje de datos en caso de que el header DO NOT TRACK esté activo
            $this->setOption('trail', false);
        }
        $application->registerCallback(Application::EVENT_PREINIT,      [$this, 'appPreInit']);
        if($this->options['trail']) {
            $application->registerCallback(Application::EVENT_ROUTE,    [$this, "appRoute"]);
            $application->registerCallback(Application::EVENT_C_VIEW,   [$this, "appCreateView"]);
            $application->registerCallback(Application::EVENT_DISPOSE,  [$this, "appShutdown"]);
            ignore_user_abort(true);
            ini_set('display_errors', 0);
        }
    }

    /// Miembros de Kansas_Module_Interface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'trail'          => true,
            'request_type'   => 'HttpRequest',
            'response_type'  => 'resource',
            'remote_plugin'  => null];
    }

    public function getVersion() : Version {
        return Environment::getVersion();
    }

    /// Eventos de la aplicación
    public function appPreInit() {
        global $application;
        $application->addRouter($this->getRouter(), 100);
    }

    public function appRoute(RequestInterface $request, $params) { // Añadir rastro
        global $application;
        if(!isset($this->trail)) {
            $this->initialize();
        }
        if(isset($params['requestType'])) { // Obtenemos el tipo de request
            $this->trail['requestType'] = $params['requestType'];
        }
        if(isset($params['identity'])) {
            $identity = $params['identity']; // Obtenemos los datos de usuario y sesión
        }
        if($authPlugin = $application->hasPlugin('auth')) {
            $session = $authPlugin->getSession();
            if(!isset($identity)) {
                $identity = $session->getIdentity();
            }
            $this->trail['session'] = $session->getId();
        }
        if($identity) {
            $this->trail['user'] = $identity['id'];
        }
        return [ // Devolvemos los datos de rastreo
            'trail' => $this->trail
        ];
    }

    public function appCreateView($view) {
        if(!isset($this->trail)) {
            $this->initialize();
        }
        $this->trail['responseType'] = 'page';
    }

    public function appShutdown() {
        if(!isset($this->trail)) {
            $this->initialize();
        }
        $error = error_get_last();
        if($error !== null) {
            $this->trail['lastError'] = $error;
        }
        $this->trail['executionTime'] = Environment::getExecutionTime();
        $this->saveTrailData();
    }


    protected function initialize() { // obtiene los datos de solicitud actual
        require_once 'Kansas/Request/getTrailData.php';
        $request = Environment::getRequest();
        $this->trail = getTrailData($request);
        $this->trail['requestType'] = $this->options['request_type'];
        $this->trail['responseType'] = $this->options['response_type'];
    }

    /**
     * Almacena el rastro de navegación, en un fichero
     *
     * @return void
     */
    protected function saveTrailData() {
        require_once 'Kansas/Environment.php';
        $trackPath = Environment::getSpecialFolder(Environment::SF_TRACK);
        $trail = $this->trail;
        $modifyHits = function($read) use ($trail) { // Función lambda de modificar archivo de solicitudes
            $hits = unserialize($read);
            if (!is_array($hits)) {
                $hits = [];
            }
            if ($trail['responseType'] == 'page') {
                echo "<!-- \n";
                var_dump($trail);
                echo " -->";
            }
            $hits[] = $trail;
            return serialize($hits);
        };
        $modifyIndex = function($read) use ($modifyHits, $trackPath) { // Función lambda de modificar archivo indice
            $index = unserialize($read);
            if(!is_array($index)) {
                $index = [];
            }
            $c = 0;
            do {
                $c++;
                if(isset($index['hits-' . $c . '.ser'])) {
                    $count = $index['hits-' . $c . '.ser'];
                } else {
                    $count = 0;
                }
            } while($count > 99);
            $hitsFile = Environment::getFile($trackPath . 'hits-' . $c . '.ser'); // Guardar cambios de solicitud
            $hitsFile->modify($modifyHits);
            $index['hits-' . $c . '.ser'] = $count + 1;
            return serialize($index);
        };
        $indexFile = Environment::getFile($trackPath . 'index.ser');
        $indexFile->modify($modifyIndex);
    }

    public function fillTrailData(array $trail = null) {
        require_once 'Kansas/Request/getUserAgentData.php';
        require_once 'Kansas/Request/getRemoteAddressData.php';
        $useThis = ($trail == null);
        if($useThis) {
            if(!isset($this->trail)) {
                $this->initialize();
            }
            $trail = $this->trail;
        }

        $userAgent = getUserAgentData($trail['userAgent']);
        $remote = getRemoteAddressData($trail['remoteAddress'], $this->options['remote_plugin']);

        if($useThis) {
            $this->trail = array_merge(
                $trail,
                $userAgent,
                $remote
            );
            return $this->trail;
        } else {
            return array_merge(
                $trail,
                $userAgent,
                $remote
            );
        }
    }

    /**
     * Obtiene el router que gestiona el acceso a los recursos para ilustrar las estadísticas de navegación
     *
     * @return Kansas\Router\TrailResources
     */
    public function getRouter() {
        if(!isset($this->router)) {
            require_once 'Kansas/Router/TrailResources.php';
            $this->router = new TrailResources([]);
        }
        return $this->router;
    }
}
