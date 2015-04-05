<?php

class Kansas_Application
	implements Kansas_Application_Interface {

	private $_providerLoader;
	private $_providers = [];
	private $_db;
	private $_config;

	private $_moduleLoader;
	private $_modules	= [];
	private $_modulesLoaded = false;

	private $_controllerLoader;
	private $_helperLoader;

	private $_request;
	
	private $_errorPlugin;
	private $_constructionPage;
	private $_router;
	
	private $_log;
	private $_logWriter;
	
	private $_viewClass = 'Smarty_View';
	private $_viewOptions;
	
	private $_title;
	private $_titleClass = 'Kansas_TitleBuilder_Default';
	private $_titleOptions;
	
	// Eventos
	private $_preinitCallbacks = [];
	private $_routeCallbacks = [];
	private $_renderCallbacks = [];
	private $_dispatchCallbacks = [];
	private $_createProviderCallbacks = [];
	
	protected static $_instance;
	
	/* Miembros de Kansas_Application_Interface */

	private function loadModules() {
		foreach($this->_modules as $moduleName => $options)
			$this->getModule($moduleName);
		$this->_modulesLoaded = true;
	}
	public function getModuleLoader() {
		if($this->_moduleLoader == null)
			$this->_moduleLoader = new Zend_Loader_PluginLoader(['Kansas_Application_Module'	=> 'Kansas/Application/Module']);
		return $this->_moduleLoader;
	}
	public function getModule($moduleName) {
		if(!is_string($moduleName))
			throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
		$moduleName = ucfirst($moduleName);
		if(!isset($this->_modules[$moduleName]) || !($this->_modules[$moduleName] instanceof Kansas_Application_Module_Interface)) {
			try {
				$moduleClass = $this->getModuleLoader()->load($moduleName);
				$options = isset($this->_modules[$moduleName]) && $this->_modules[$moduleName] instanceof Zend_Config ?
					$this->_modules[$moduleName]:
					new Zend_Config([]);
				$module = new $moduleClass($options);
			} catch(Exception $e) {
				trigger_error($e->getMessage());
				$module = false;
			}
			$this->_modules[$moduleName] = $module;
		}
		return $this->_modules[$moduleName];
	}
	public function setModule($moduleName, $options = []) {
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
	public function hasModule($moduleName) {
		$moduleName = ucfirst($moduleName);
		return isset($this->_modules[$moduleName]) && ($this->_modules[$moduleName] instanceof Kansas_Application_Module_Interface);
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
	
	public function getProviderLoader() {
		if($this->_providerLoader == null)
			$this->_providerLoader = new Zend_Loader_PluginLoader(['Kansas_Db_' => 'Kansas/Db/']);
		return $this->_providerLoader;
	}
	public function getProvider($providerName) {
		if(!is_string($providerName))
			throw new System_ArgumentOutOfRangeException('providerName', $providerName, 'Se esperaba una cadena');
		$providerName = ucfirst($providerName);
		if(!array_key_exists($providerName, $this->_providers)) {
			$providerClass = $this->getProviderLoader()->load($providerName);
			$provider = new $providerClass($this->getDb());
			$this->_providers[$providerName] = $provider;
			$this->fireCreateProvider($provider, $providerName);
		}
		return $this->_providers[$providerName];
	}
	
	public function getRequest() {
		if($this->_request == null)
			$this->_request = new Zend_Controller_Request_Http();
		return $this->_request;
	}
	
	public function getControllerLoader() {
		if($this->_controllerLoader == null)
			$this->_controllerLoader = new Zend_Loader_PluginLoader(['Kansas_Controllers_' => 'Kansas/Controllers/']);
		return $this->_controllerLoader;
	}
	public function getHelperLoader() {
		if($this->_helperLoader == null)
			$this->_helperLoader = new Zend_Loader_PluginLoader(['Kansas_Helpers_' => 'Kansas/Helpers/']);
		return $this->_helperLoader;
	}
	
	public function dispatch($params) {
		$request = $this->getRequest();
		// Dispatch
		$params = $this->fireDispatch($params);
		$controller = isset($params['controller']) ?
			ucfirst($params['controller']):
			'Index';
		$action = isset($params['action']) ?
			$params['action']:
			'Index';
		$controllerClass = $this->getControllerLoader()->load($controller);
		$class = new $controllerClass();
		$class->init($request);
		if(!is_callable([$class, $action]))
			throw new System_NotImplementedException('No se ha implementado ' . $action . ' en el controlador ' . get_class($class));
		return $class->$action($params);
	}
	
	public function run() {
		if(Kansas_Enviroment::getInstance()->getStatus() ==  Kansas_Enviroment::DEVELOPMENT) {
			error_reporting(E_ALL);
			ini_set("display_errors", 1);
		} else
			error_reporting(E_ERROR);

			error_reporting(E_ALL);
			ini_set("display_errors", 1);

		$this->loadModules();
		// PreInit
		$this->firePreInit();
		try {
			$params = [];
			if($params = $this->getRouter()->match($this->getRequest())) {
				// Route
				$params = $this->fireRoute($params);
				$this->setRequestParams($params);
				$result = $this->dispatch($params);
			}
			if(!isset($result) || $result == null)
				$result = $this->createErrorResult(new System_Net_WebException(404));
		} catch(Exception $e) {
			$result = $this->createErrorResult($e);
		}
		// Render
		$this->fireRender($result);
		$result->executeResult();
	}
	
	// Asocia los parametros indicados y los básicos a la petición actual
	public function setRequestParams($params) {
		$request = $this->getRequest();
		$params['application']	= $this;
		$params['url'] 					= trim($request->getPathInfo(), '/');
		$params['uri'] 					= $request->getRequestUri();
		$request->setParams($params);
	}
	
	public function getEnviroment() {
		return Kansas_Enviroment::getInstance()->getStatus();
	}
	
	public function getErrorPlugin() {
		if($this->_errorPlugin == null)
			$this->_errorPlugin = new Kansas_Router_Error([
				'controller'	=> 'Error',
				'action'			=> 'Index',
			]);				
		return $this->_errorPlugin;
	}
	
	public function setErrorPlugin(Kansas_Router_Error_Plugin_Interface $errorPlugin) {
		$this->_errorPlugin = $errorPlugin;
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

	public function setConstructionPage($constructionPage) {
		$this->_constructionPage = $constructionPage;
		return $this;
	}

	public function getDb() {
		if($this->_db instanceof Zend_Config)
			$this->_db = Zend_Db::factory($this->_db);
		return $this->_db;
	}
	public function setDb($value) {
		if($value instanceof Zend_Db || $value instanceof Zend_Config)
			$this->_db = $value;
		else
			throw new System_ArgumentOutOfRangeException(); 
	}

	/* Miembros de singleton */
	public static function getInstance() {
		global $application;
		if(self::$_instance == null)
			$application = self::$_instance = new self();	
		return self::$_instance;
	}
	
	public function set($key, $value = null) {
		if($key instanceof Zend_Config) {
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
						$loader = null;
						switch($loaderName) {
							case 'controller':
								$loader = $this->getControllerLoader();
								break;
							case 'provider':
								$loader = $this->getProviderLoader();
								break;
							case 'module':
								$loader = $this->getModuleLoader();
								break;
						}
						if($loader == null)
							throw new System_ArgumentOutOfRangeException();
							
						foreach($options->toArray() as $prefix => $path)
							$loader->addPrefixPath($prefix, realpath($path));
					}
					break;
				case 'module':
					foreach($value as $module => $options)
						if(empty($options)) $this->setModule($module, new Zend_Config([]));
						else $this->setModule($module, $options);
					break;
				case 'route':
					foreach($value as $route => $params)
						if($route == 'index') $this->getRouter()->setRoute('', $params->toArray());
						else $this->getRouter()->setRoute($route, $params->toArray());
					break;
				case 'router':
					if($this->_router instanceof Zend_Config)
						$this->_router->merge($value);
					elseif($this->_router instanceof Kansas_Router_Interface)
						$this->_router->setOptions($value);
					else
						$this->_router = new Zend_Config($value->toArray(), true);
					break;
				case 'log':
					$this->_log = Zend_Log::factory($value);
					break;
				case 'view':
					$this->_viewClass = $value->class;
					$options = $value->toArray();
					unset($options['class']);
					$this->_viewOptions = $options;
					break;
				case 'title':
					if($value->class)
						$this->_titleClass = $value->class;
					$options = $value->toArray();
					unset($options['class']);
					$this->_titleOptions = $options;
					break;
				default:
					var_dump($key);
					break;
			}
			
		}
	}
	
	public function setWriter(Zend_Log_Writer_Abstract $writer) {
		if($this->_log != null)
			$this->_log->addWriter($writer);
		else
			$this->_writer = $writer;
	}
	
	public function log($message, $priority) {
		if($this->_log == null) {
			if($this->_writer == null) 
				$this->_writer = new Zend_Log_Writer_Null();
			$this->_log = new Zend_Log($this->_writer);
		}
		$time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		$this->_log->log($time . '> ' . $message, $priority);
	}
	
	public function createView() {
		if($this->_viewOptions == null)
			$this->_viewOptions = new Zend_Config([]);
		$view = new $this->_viewClass($this->_viewOptions);
		if($view->getCaching())
			$view->setCacheId($this->getRequest()->getRequestUri());
		return $view;
	}
	
	public function getTitle() {
		if($this->_title == null) {
			if($this->_titleOptions == null) $this->_titleOptions = [];
			$this->_title = new $this->_titleClass(new Zend_Config($this->_titleOptions));
		}
		return $this->_title;
	}

	public function getRouter() {
		if($this->_router == null || !($this->_router instanceof Kansas_Router_Interface)) {
				$class = isset($this->_router->class) ? $this->_router->class:
																								'Kansas_Router_Default';
				unset($this->_router->class);
			$this->_router = new $class($this->_router);
		}
		return $this->_router;
	}
	
	public function getConfig() {
		return $this->_config->toArray();
	}
	
	// devuelve una respuesta de solicitud, correspondiente al error indicado
	public function createErrorResult(Exception $e) {
		$params = $this->getErrorPlugin()->getParams($e);
		$params = $this->fireRoute($params);
		$this->setRequestParams($params);
		return $this->dispatch($params);
	}

}