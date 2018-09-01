<?php

namespace Kansas\Module;

use System\Configurable;
use System\NotSuportedException;
use Kansas\Environment;
use Kansas\Module\ModuleInterface;
use Psr\Http\Message\RequestInterface;

use function Kansas\Request\getTrackData;

// Traker basado en bbclone

class Traker extends Configurable implements ModuleInterface {

  private $track;    

  /// Constructor
  public function __construct(array $options) {
    global $application;
    parent::__construct($options);
    $headers = getallheaders();
    if(isset($headers['DNT']) && $headers['DNT'] == '1')
      $this->setOption('track', false);
    else {
      $application->registerCallback('route',       [$this, "appRoute"]);
      $application->registerCallback('createView',  [$this, "appCreateView"]);
      register_shutdown_function(                   [$this, 'shutdown']);
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
        'track'         =>  true,
        'request_type'   => 'HttpRequest',
        'response_type'  => 'resource'
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
    public function appRoute(RequestInterface $request, $params) { // Añadir estado track
        if(!isset($$this->track))
            $this->initialize();
        if(isset($params['requestType']))
            $this->track['requestType'] = $params['requestType'];
        return [
            'track' => $this->track
        ];
    }

    public function appCreateView($view) {
        if(!isset($$this->track))
            $this->initialize();
        $this->track['responseType'] = 'page';
    }

    public function initialize() { // obtiene los datos de solicitud actual
        require_once 'Kansas/Request/getTrackData.php';
        global $environment;
        $request = $environment->getRequest();
        $this->track = getTrackData($request);
        $this->track['requestType'] = $this->options['request_type'];
        $this->track['responseType'] = $this->options['response_type'];
    }

    public function shutdown() {
        global $environment;
        $error = error_get_last();
        if($error !== null)
            $this->track['lastError'] = $error;
        $this->track['executionTime'] = $environment->getExecutionTime();

        require_once('Kansas/Environment.php');
        if($this->track['environment'] == Environment::ENV_DEVELOPMENT &&
        $this->track['responseType'] == 'page') {
            echo "<!-- \n";
            var_dump($this->track);
            echo " -->";
        }
        $this->saveTrackData();
    }

    protected function saveTrackData() {

    }

}