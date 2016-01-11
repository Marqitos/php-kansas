<?php

class Kansas_Application
	implements Kansas_Application_Interface {

	private $_providers = [];
	private $_db;
	private $_config;

	private $_loaders = [
		'controller' => ['Kansas_Controllers_' => 'Kansas/Controllers/'],
		'helper'     => ['Kansas_Helpers_' => 'Kansas/Helpers/'],
		'module'     => ['Kansas_Application_Module_'	=> 'Kansas/Application/Module/'],
		'provider'	 => ['Kansas_Db_' => 'Kansas/Db/']
	];

	private $_modules	= [];
	private $_modulesLoaded = false;

	private $_routers;
	private $_pages	= [];  
	
	private $_viewClass = 'Kansas_View_Smarty';
	private $_viewOptions = [];
	
	private $_titleClass = 'Kansas_TitleBuilder_Default';
	private $_titleOptions;
  
  private $_logCallback = ['Kansas_Environment', 'log'];
  private $_errorCallback;
  
	// Eventos
	private $_preinitCallbacks = [];
	private $_routeCallbacks = [];
	private $_renderCallbacks = [];
	private $_dispatchCallbacks = [];
	private $_createProviderCallbacks = [];
  private $_createViewCallbacks = [];
	
	protected static $_instance;

	protected function __construct() {
		set_error_handler([$this, 'error_handler']);
		set_exception_handler([$this, 'exception_handler']);
    $this->_errorCallback = [$this, 'errorManager'];
    $this->_routers = new SplPriorityQueue();
    Kansas_Environment::getInstance();
	}

	/* Miembros de Kansas_Application_Interface */

	public function getLoader($loaderName) {
		if(!isset($this->_loaders[$loaderName]))
			return false;
		if(!($this->_loaders[$loaderName] instanceof Kansas_PluginLoader_Interface))
			$this->_loaders[$loaderName] = new Kansas_PluginLoader($this->_loaders[$loaderName]);
		return $this->_loaders[$loaderName];
	}

	private function loadModules() {
		foreach($this->_modules as $moduleName => $options)
			$this->getModule($moduleName);
		$this->_modulesLoaded = true;
	}
	public function getModule($moduleName) { // Obtiene el modulo seleccionado, lo carga si es necesario
		if(!is_string($moduleName))
			throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
		$moduleName = ucfirst($moduleName);
		if(!isset($this->_modules[$moduleName]) || !($this->_modules[$moduleName] instanceof Kansas_Application_Module_Interface)) {
			try {
				$moduleClass = $this->getLoader('module')->load($moduleName);
				$options = isset($this->_modules[$moduleName]) && is_array($this->_modules[$moduleName]) ?
					$this->_modules[$moduleName]:
					[];
				$module = new $moduleClass($options);
			} catch(Exception $e) {
        call_user_func($this->_logCallback, E_USER_NOTICE, $e);        
				$module = false;
			}
			$this->_modules[$moduleName] = $module;
		}
		return $this->_modules[$moduleName];
	}
	public function setModule($moduleName, array $options = []) { // Guarda la configuración del modulo, y lo carga si el resto ya han sido cargados
		if(!is_string($moduleName))
			throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
		$moduleName = ucfirst($moduleName);
		if(!isset($this->_modules[$moduleName]) || !($this->_modules[$moduleName] instanceof Kansas_Application_Module_Interface))
			$this->_modules[$moduleName] = $options;
		else
			$this->_modules[$moduleName]->setOptions($options);
		if($this->_modulesLoaded)
			$this->getModule($moduleName);
	}
	public function hasModule($moduleName) { // Obtiene el modulo seleccionado si está cargado o false en caso contrario
		if(!is_string($moduleName))
			throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
    $moduleName = ucfirst($moduleName);
		return isset($this->_modules[$moduleName]) && ($this->_modules[$moduleName] instanceof Kansas_Application_Module_Interface) ?
      $this->_modules[$moduleName]:
      false;
	}
	public function getModules() {
		$result = [];
		foreach($this->_modules as $name => $module)
			$result[$name] = [
				'options'	=> $module->getOptions(),
				'version'	=> 'v' . $module->getVersion()->__toString()
			];
		return $result;
	}
	
	public function getProvider($providerName) {
		if(!is_string($providerName))
			throw new System_ArgumentOutOfRangeException('providerName', $providerName, 'Se esperaba una cadena');
		$providerName = ucfirst($providerName);
		if(!isset($this->_providers[$providerName])) {
			$providerClass = $this->getLoader('provider')->load($providerName);
			$provider = new $providerClass($this->getDb());
			$this->_providers[$providerName] = $provider;
			$this->fireCreateProvider($provider, $providerName);
		}
		return $this->_providers[$providerName];
	}
	
	public function getRequest() {
		global $environment;
		return $environment->getRequest();
	}
	
	public function dispatch($params) {
		$params = $this->fireDispatch($params);
		$controller = isset($params['controller']) ?
			ucfirst($params['controller']):
			'Index';
		$action = isset($params['action']) ?
			$params['action']:
			'Index';
		$controllerClass = $this->getLoader('controller')->load($controller);
		$class = new $controllerClass();
    unset($params['controller']);
    unset($params['action']);
		$class->init($params);
		if(!is_callable([$class, $action]))
			throw new System_NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($class));
		return $class->$action($params);
	}
	
	public function run() {
    global $environment;
    
		$this->loadModules();
 		$params = false;
    if($this->getEnvironment() == 'install') {
      $router = new Kansas_Router_Install();
      $params = $router->match();
    } else {
      // PreInit
  		$this->firePreInit();
  		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
      if($path == '' && isset($this->_pages['.']))
  			$params = $this->_pages['.'];
  		elseif(isset($this->_pages[$path]))
  			$params = $this->_pages[$path];
  		else	
  			foreach($this->_routers as $router)
  				if($params = $router->match()) {
            $params['router'] = $router;
            break;
          }
    }
		if($params) {
			// Route event
			$params = array_merge($this->fireRoute($params), $this->getDefaultParams());
			$result = $this->dispatch($params);
		}
		if(!isset($result) || $result == null)
			throw new System_Net_WebException(404);
		// Render
		$this->fireRender($result);
		$result->executeResult();
	}
	
	// Asocia los parametros indicados y los básicos a la petición actual
	public static function getDefaultParams() {
    global $environment;
		$request = $environment->getRequest();
		return [
			'url'					=> trim($request->getUri()->getPath(), '/'),
			'uri'					=> $request->getUriString(),
			'requestType' => $request->isXmlHttpRequest() ? 'XMLHttpRequest' : 
											 $request->isFlashRequest()   ? 'flash'
											                              : 'HttpRequest'
		];
	}
	
	public function getEnvironment() {
		global $environment;
		return $environment->getStatus();
	}
	
	/* Eventos */
	
	public function registerPreInitCallbacks($callback) {
		if(is_callable($callback))
			$this->_preinitCallbacks[] = $callback;
	}
	public function registerRouteCallbacks($callback) {
		if(is_callable($callback))
			$this->_routeCallbacks[] = $callback;
	}
	public function registerRenderCallbacks($callback) {
		if(is_callable($callback))
			$this->_renderCallbacks[] = $callback;
	}
	public function registerDispatchCallbacks($callback) {
		if(is_callable($callback))
			$this->_dispatchCallbacks[] = $callback;
	}
	public function registerCreateProviderCallbacks($callback) {
		if(is_callable($callback))
			$this->_createProviderCallbacks[] = $callback;
	}
	public function registerCreateViewCallbacks($callback) {
		if(is_callable($callback))
			$this->_createViewCallbacks[] = $callback;
	}
  	
	protected function firePreInit() {
		foreach ($this->_preinitCallbacks as $callback)
			call_user_func($callback);
	}
	protected function fireRoute($params = array()) {
		$request	= $this->getRequest();
		foreach ($this->_routeCallbacks as $callback)
			$params = array_merge($params, call_user_func($callback, $request, $params));
		return $params;
	}
	protected function fireRender(Kansas_View_Result_Interface $result) {
		foreach ($this->_renderCallbacks as $callback)
			call_user_func($callback, $result);
	}
	protected function fireDispatch($params = array()) {
		$request	= $this->getRequest();
		foreach ($this->_dispatchCallbacks as $callback)
			$params = array_merge($params, call_user_func($callback, $request, $params));
		return $params;
	}
	protected function fireCreateProvider($provider, $providerName) {
		foreach ($this->_createProviderCallbacks as $callback)
			$params = call_user_func($callback, $provider, $providerName);
	}
  protected function fireCreateView($view) {
		foreach ($this->_createViewCallbacks as $callback)
			$params = call_user_func($callback, $view);
	}


	public function getDb() {
		if(is_array($this->_db))
			$this->_db = Zend_Db::factory($this->_db['adapter'], $this->_db['params']);
		return $this->_db;
	}
	public function setDb($value) {
		if($value instanceof Zend_Db || is_array($value))
			$this->_db = $value;
		else
			throw new System_ArgumentOutOfRangeException('db'); 
	}

	/* Miembros de singleton */
	public static function getInstance() {
		global $application;
		if(self::$_instance == null)
			$application = self::$_instance = new self();	
		return self::$_instance;
	}
	
  public function loadIni($filename, array $options = []) {
    global $environment;
    $this->set(Kansas_Config::ParseIni($filename, $options, $environment->getStatus()));
  }
	public function set($key, $value = null) {
		if(is_array($key)) {
			foreach($key as $item => $value)
				$this->set($item, $value);
			$this->_config = $key;
		}
		elseif(is_string($key)) {
			switch($key) {
				case 'db':
					$this->setDb($value);
					break;
				case 'loader':
					foreach($value as $loaderName => $options) {
						if($loader = $this->getLoader($loaderName)) {
							foreach($options as $prefix => $path)
								$loader->addPrefixPath($prefix, realpath($path));
						} else
							throw new System_ArgumentOutOfRangeException();
					}
					break;
				case 'module':
					foreach($value as $module => $options)
						if(empty($options)) $this->setModule($module, []);
						else $this->setModule($module, (array) $options);
					break;
				case 'route':
					foreach($value as $route => $params)
						$this->setRoute($route, $params);
					break;
				case 'view':
					if(isset($value['class']))
						$this->_viewClass = $value['class'];
					unset($value['class']);
					$this->_viewOptions = $value;
					break;
				case 'title':
					if(isset($value['class']))
						$this->_titleClass = $value['class'];
					unset($value['class']);
					$this->_titleOptions = $value;
          break;
        case 'log':
          if(is_callable($value))
            $this->_logCallback = $value;
          break;
        case 'error':
          if(is_callable($value))
            $this->_errorCallback = $value;
          break;
        case 'theme':
          global $environment;
          $environment->setTheme($value);
          break;
			}
			
		}
	}
	
	public function getView() {
    global $environment;
    if(!$this->_viewClass instanceof Kansas_View_Interface) {
      $defaultScriptPaths = $environment->getViewPaths();
      $this->_viewClass = new $this->_viewClass(array_replace_recursive(['scriptPath' => $defaultScriptPaths], $this->_viewOptions));
      if($this->_viewClass->getCaching())
        $this->_viewClass->setCacheId($this->getRequest()->getRequestUri());
      $this->fireCreateView($this->_viewClass);
    }
		return $this->_viewClass;
	}
	
	public function createTitle() {
    return new $this->_titleClass((array)$this->_titleOptions ?: []);
	}

	public function getConfig() {
		return $this->_config;
	}
  
  /* Gestion de errores */
	public function error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
		if (!(error_reporting() & $errno))
				return false; // Este código de error no está incluido en error_reporting
    $trace = debug_backtrace();
    array_shift($trace);
    
    $errData = [
      'exception'   => null,
      'errorLevel'	=> $errno,
      'code'				=> 500,
      'message'			=> $errstr,
      'trace'				=> $trace,
      'line'				=> $errline,
      'file'				=> $errfile,
      'context'			=> $errcontext
    ];
    if(error_reporting() != 0)
      call_user_func($this->_logCallback, $errno, $errData);
		if($errno == E_USER_ERROR) 
      call_user_func($this->_errorCallback, $errData);
    return true; // No ejecutar el gestor de errores interno de PHP
	}
	
	public function exception_handler(Exception $ex) {
    $errData = [
			'exception'   => get_class($ex),
			'errorLevel'	=> E_USER_ERROR,
			'code'				=> ($ex instanceof System_Net_WebException ? $ex->getStatus() : 500),
			'message'			=> $ex->getMessage(),
			'trace'				=> $ex->getTrace(),
			'line'				=> $ex->getLine(),
			'file'				=> $ex->getFile()
		];
    if(error_reporting() != 0)
      call_user_func($this->_logCallback, E_USER_ERROR, $errData);
    call_user_func($this->_errorCallback, $errData);
		exit(1);
	}
  
  public function errorManager($params) {
		$result = $this->dispatch(array_merge($params, [
			'controller'	=> 'Error',
			'action'			=> 'Index'
    ], $this->getDefaultParams()));
		$result->executeResult();
  }
  
	/* Enrutamiento */
	public function addRouter(Kansas_Router_Interface $router, $priority = 0) {
		$this->_routers->insert($router, $priority);
	}
	
	public function setRoute($page, array $params) {
		$this->_pages[$page] = $params;
	}  
}