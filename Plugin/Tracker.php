<?php

namespace Kansas\Plugin;

use System\Configurable;
use System\NotSuportedException;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\TrailResources;
use Psr\Http\Message\RequestInterface;

use function Kansas\Request\getTrailData;
use function Kansas\Request\getUserAgentData;
use function Kansas\Request\getRemoteAddressData;
use function error_get_last;

// Tracker basado en bbclone
class Tracker extends Configurable implements PluginInterface {

  private $trail;
  private $router;

  /// Constructor
  public function __construct(array $options) {
    global $application;
    parent::__construct($options);
    $headers = getallheaders();
    if(isset($headers['DNT']) && $headers['DNT'] == '1')
      $this->setOption('trail', false);
    $application->registerCallback('preinit',       [$this, 'appPreInit']);
    if($this->options['trail']) {
      $application->registerCallback('route',       [$this, "appRoute"]);
      $application->registerCallback('createView',  [$this, "appCreateView"]);
      register_shutdown_function(                   [$this, 'shutdown']);
      ignore_user_abort(true);
      ini_set('display_errors', 0);
    }
  }

  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
    case 'production':
    case 'development':
    case 'test':
      return [
        'trail'          => true,
        'request_type'   => 'HttpRequest',
        'response_type'  => 'resource',
        'remote_plugin'  => null
      ];
    default:
      require_once 'System/NotSuportedException.php';
      throw new NotSuportedException("Entorno no soportado [$environment]");
    }
  }

    public function getVersion() {
        global $environment;
        return $environment->getVersion();
    }

    /// Eventos de la aplicación
    public function appPreInit() {
        global $application;
        $application->addRouter($this->getRouter(), 100);
    }

    public function appRoute(RequestInterface $request, $params) { // Añadir rastro
        if(!isset($this->trail))
            $this->initialize();
        if(isset($params['requestType']))
            $this->trail['requestType'] = $params['requestType'];
        return [
            'trail' => $this->trail
        ];
    }

    public function appCreateView($view) {
        if(!isset($this->trail))
            $this->initialize();
        $this->trail['responseType'] = 'page';
    }

    protected function initialize() { // obtiene los datos de solicitud actual
        require_once 'Kansas/Request/getTrailData.php';
        global $environment;
        $request = $environment->getRequest();
        $this->trail = getTrailData($request);
        $this->trail['requestType'] = $this->options['request_type'];
        $this->trail['responseType'] = $this->options['response_type'];
    }

    public function shutdown() {
        global $environment;
        if(!isset($this->trail))
            $this->initialize();
        $error = error_get_last();
        if($error !== null)
            $this->trail['lastError'] = $error;
        $this->trail['executionTime'] = $environment->getExecutionTime();
        $this->saveTrailData();
    }

    protected function saveTrailData() {
        require_once('Kansas/Environment.php');
        if($this->trail['environment'] == Environment::ENV_DEVELOPMENT &&
            $this->trail['responseType'] == 'page') {
            echo "<!-- \n";
            var_dump($this->trail);
            echo " -->";
        }

    }

    public function fillTrailData(array $trail = null) {
        $useThis = false;
        if($trail == null) {
            $trail = $this->trail;
            $useThis = true;
        }
        
        require_once 'Kansas/Request/getUserAgentData.php';
        $userAgent = getUserAgentData($trail['userAgent']);
        require_once 'Kansas/Request/getRemoteAddressData.php';
        $remote = getRemoteAddressData($trail['remoteAddress'], $this->options['remote_plugin']);
        
        if($useThis) {
            $this->trail = array_merge(
                $trail,
                $userAgent,
                $remote
            );
            return $this->trail;
        } else 
            return array_merge(
                $trail,
                $userAgent,
                $remote
            );
      }

      public function getRouter() {
          if(!isset($this->router)) {
            require_once 'Kansas/Router/TrailResources.php';
            $this->router = new TrailResources([]);
          }
          return $this->router;
      }
}