<?php

class Kansas_Application_Module_Admin
  extends Kansas_Application_Module_Abstract
  implements Kansas_Router_Interface {
	
	private $_basePath;
  private $_callbacks = [
    'tasks' => [],
    'menu'  => []
  ];
  
	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
		global $application;
    $application->getModule('zones');
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
 
	public function appPreInit() { // añadir router
		global $application;
    $zones = $application->getModule('zones');
    if($zones->getZone() == 'admin') {
      $application->addRouter($this);
      $this->_basePath = $zones->getBasePath();
    }
	}

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}

	public function match() {
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    if(Kansas_String::startWith($path, $this->_basePath))
      $path = substr($path, strlen($this->_basePath));
    else
			return false;
      
    // Fire task callbacks
		$tasks = [];
		foreach ($this->_callbacks['tasks'] as $callback)
			$tasks = array_merge($tasks, call_user_func($callback));

    // Fire menu callbacks
		$menu = [];
		foreach ($this->_callbacks['menu'] as $callback)
			$menu = array_merge($menu, call_user_func($callback));

    if($path == '') { // Escritorio
      $params = array_merge($this->getOptions('params'), [
        'controller'  => 'admin',
        'action'      => 'index'
      ]);
    } elseif($path == '/tasks') { // Panel de tareas
      $params = array_merge($this->getOptions('params'), [
        'controller'  => 'admin',
        'action'      => 'tasks'
      ]);
    } elseif(substr($path, 0, 7) == '/tasks/') { // Tareas
      $path = substr($path, 7);
      if(isset($tasks[$path]))
        $params = array_merge($this->getOptions('params'), [
          'controller'  => 'admin',
          'action'      => 'dispatch',
          'dispatch'    => $tasks[$path]['dispatch']          
        ]);
    } else { // Elementos personalizados
      $slugs = explode('/', substr($path, 1));
      if(count($slugs) == 1 && isset($menu[$slugs[0]])) {
        $params = array_merge($this->getOptions('params'), [
          'controller'  => 'admin',
          'action'      => 'dispatch',
          'dispatch'    => $menu[$slugs[0]]['dispatch']          
        ]);
      } elseif(count($slugs) == 2 && isset($menu[$slugs[0]]['menuItems'][$slugs[1]])) {
        $params = array_merge($this->getOptions('params'), [
          'controller'  => 'admin',
          'action'      => 'dispatch',
          'dispatch'    => $menu[$slugs[0]]['menuItems'][$slugs[1]]['dispatch']          
        ]);
      }
    }
    
    if($params) {
      $notifications = [];
      foreach($tasks as $slug => $task) {
        if(isset($task['notifications'])) {
          $notifications['tasks'][$slug] = $task['notifications'];
          $notifications['tasks'][$slug]['title'] = $task['title'];
        }
      }
		  $params['notifications']  = $notifications;
      $params['tasks']          = $tasks;
      $params['menu']           = $menu;
      $params['basePath']       = $this->_basePath;
    }
		
		return $params;
	}
  
  public function registerTaskCallbacks($callback) {
		if(is_callable($callback))
			$this->_callbacks['tasks'][] = $callback;
	}
  public function registerMenuCallbacks($callback) {
		if(is_callable($callback))
			$this->_callbacks['menu'][] = $callback;
	}
    
  public function getBasePath() {
    return $this->_basePath;
  }
  
  public function assemble($data = [], $reset = false, $encode = false) {
    return $this->_basePath;
  }
  
}