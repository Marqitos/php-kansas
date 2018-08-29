<?php

namespace Kansas\Module;

use System\Configurable;
use System\NotSuportedException;
use Kansas\Bbclone\Marker;
use Kansas\Module\ModuleInterface;
use Psr\Http\Message\RequestInterface;

use function Kansas\Request\getTrackData;

// Traker basado en bbclone

class Bbc extends Configurable implements ModuleInterface {

  private $track;    

  /// Constructor
  public function __construct(array $options) {
    global $application;
    parent::__construct($options);
    $headers = getallheaders();
    if(isset($headers['DNT']) && $headers['DNT'] == '1')
      $this->setOption('track', false);
    else {
      $application->registerCallback('preinit',     [$this, "appPreInit"]);
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
        'requestType'   => 'HttpRequest',
        'responseType'  => 'resource'
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
  public function appPreInit() { // obtiene los datos de solicitud actual
    require_once 'Kansas/Request/getTrackData.php';
    global $environment;
    $request = $environment->getRequest();
    //$marker = new Marker();
    $this->track = getTrackData($request);
  }

  public function appRoute(RequestInterface $request, $params) { // Añadir estado track
    if(isset($params['requestType']))
      $this->setOption('requestType', $params['requestType']);
    return [
      'track' => $this->options['track']
    ];
  }

  public function appCreateView($view) {
    $this->setOption('responseType', 'page');
  }

  public function shutdown() {
    global $environment;
    $this->track['error'] = error_get_last();
    $this->track['executionTime'] = $environment->getExecutionTime();
    $this->track['responseType'] = $this->options['responseType'];
    echo "<!-- \n";
    var_dump($this->track);
    echo " -->";
    //if ($bbc_marker->ignored === true) return bbc_msg(false, "i");
    //else $msg = $bbc_marker->bbc_write_entry();
  }
  

}