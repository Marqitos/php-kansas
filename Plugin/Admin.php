<?php

namespace Kansas\Plugin;

use Kansas\Plugin\AbstractZone;
use Kansas\Plugin\Zone\ZoneInterface;
use Kansas\Router\RouterInterface;
use System\NotSuportedException;

use function System\String\startWith;

require_once 'Kansas/Plugin/AbstractZone.php';
require_once 'Kansas/Router/RouterInterface.php';

class Admin extends AbstractZone implements RouterInterface {
	
  /// Campos
  private $_callbacks = [
    'alerts'  => [],
    'menu'    => [],
    'tasks'   => []
  ];
  
  /// Constructor
	public function __construct(array $options) {
    parent::__construct($options);
		global $application;
	}
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [
          'base_path' => 'admin',
          'params' => []
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
  
  /// Miembros de Kansas_Router_Interface
	public function match() {
    echo 'test';
    exit;
    require_once 'System/String/startWith.php';
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    if(startWith($path, $this->getBasePath()))
      $path = substr($path, strlen($this->getBasePath()));
    else
			return false;
      
    // Fire task callbacks
		$tasks = [];
		foreach ($this->_callbacks['tasks'] as $callback)
			$tasks = array_merge($tasks, call_user_func($callback));
      
    // Fire alerts callbacks
		$alerts = [];
		foreach ($this->_callbacks['alerts'] as $callback)
			$alerts = array_merge($alerts, call_user_func($callback));
          
    // Fire menu callbacks
		$menu = [];
		foreach ($this->_callbacks['menu'] as $callback)
			$menu = array_merge($menu, call_user_func($callback));

    if($path == '') { // Escritorio
      $params = array_merge($this->options['params'], [
        'controller'  => 'admin',
        'action'      => 'index'
      ]);
    } elseif($path == '/tasks') { // Panel de tareas
      $params = array_merge($this->options['params'], [
        'controller'  => 'admin',
        'action'      => 'tasks'
      ]);
    } elseif(substr($path, 0, 7) == '/tasks/') { // Tareas
      $path = substr($path, 7);
      if(isset($tasks[$path]['dispatch']))
        $params = $this->dispatch($tasks[$path]['dispatch']);
    } elseif(substr($path, 0, 8) == '/alerts/') { // Alertas
      $path   = substr($path, 8);
      $slugs  = explode('/', $path);
      if(count($slugs) == 1 && isset($alerts[$slugs[0]]['dispatch']))
        $params = $this->dispatch($alerts[$path]['dispatch']);
      elseif(count($slugs) > 1 &&
         isset($alerts[$slugs[0]]['match']) &&
         $dispatch = call_user_func($alerts[$slugs[0]]['match'], $path))
        $params = $this->dispatch($dispatch);
    } else { // Elementos personalizados
      $path   = substr($path, 1);
      $slugs  = explode('/', $path);
      if(count($slugs) == 1 && isset($menu[$slugs[0]]))
        $params = $this->dispatch($menu[$slugs[0]]['dispatch']);
      elseif(count($slugs) == 2 && isset($menu[$slugs[0]]['menuItems'][$slugs[1]]))
        $params = $this->dispatch($menu[$slugs[0]]['menuItems'][$slugs[1]]['dispatch']);
      elseif(count($slugs) > 1 &&
         isset($menu[$slugs[0]]['match']) &&
         $dispatch = call_user_func($menu[$slugs[0]]['match'], $path))
        $params = $this->dispatch($dispatch);
    }
    
    if($params) {
      $notifications = [];
      foreach($tasks as $slug => $task) { // Creamos notificaciones de tareas
        if(isset($task['notifications'])) {
          $notifications['tasks'][$slug] = $task['notifications'];
          $notifications['tasks'][$slug]['title'] = $task['title'];
        }
      }
      foreach($alerts as $slug => $alert) { // Eliminamos las notificaciones que no se deben mostrar
        if(isset($alert['isDisabled']) && $alert['isDisabled'])
          unset($alerts[$slug]);
      }

		  $params['notifications']  = $notifications;
      $params['alerts']         = $alerts;
      $params['tasks']          = $tasks;
      $params['menu']           = $menu;
      $params['basePath']       = $this->getBasePath();
      if(isset($params['dispatch']))
        $params['dispatch']['basePath'] = $this->getBasePath();
    }
		
		return $params;
	}
  
  public function assemble($data = [], $reset = false, $encode = false) {
    return $this->getBasePath();
  }
  
  /// Eventos de la aplicación
	public function onAppPreInit($zone) { // añadir router
		global $application;
    if($zone instanceof Admin) {
      $application->addRouter($this);
    }
	}
  
  /// Registro de eventos
  public function registerAlertsCallbacks(callable $callback) {
			$this->_callbacks['alerts'][] = $callback;
	}
  public function registerMenuCallbacks(callable $callback) {
			$this->_callbacks['menu'][] = $callback;
	}
  public function registerTaskCallbacks(callable $callback) {
			$this->_callbacks['tasks'][] = $callback;
	}
  
  /// Metodos privados
  private function dispatch($dispatch) {
    return array_merge($this->options['params'], [
      'controller'  => 'admin',
      'action'      => 'dispatch',
      'dispatch'    => $dispatch          
    ]);
  }
}