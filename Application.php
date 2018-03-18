<?php
require_once 'System/Configurable/Abstract.php';

class Kansas_Application extends System_Configurable_Abstract {

    private $_providers = [];
    private $_db;

    private $_loaders = [
        'controller' => ['Kansas_Controller_' => 'Kansas/Controller/'],
        'module'     => ['Kansas_Module_'	=> 'Kansas/Module/'],
        'provider'	 => ['Kansas_Db_' => 'Kansas/Db/']
    ];

    private $_modules	    = [];
    private $_modulesLoaded = false;

    private $_routers;
    
    private $_view;
    private $_title;
    
    private $_logCallback = ['Kansas_Environment', 'log'];
    private $_errorCallback;
    
    // Eventos
    private $_callbacks = [
        'preinit' 			=> [],
        'route'   			=> [],
        'render'  			=> [],
        'dispatch' 			=> [],
        'createProvider' 	=> [],
        'createView' 		=> []
    ];
    
    protected static $_instance;

    public function __construct($options) {
        set_error_handler([$this, 'error_handler']);
        set_exception_handler([$this, 'exception_handler']);
        $this->_errorCallback = [$this, 'errorManager'];
        $this->_routers = new SplPriorityQueue();
        $this->registerOptionChanged([$this, 'onOptionChanged']);
        parent::__construct($options);
    }

    /// Miembros de System_Configurable_Interface
    public function getDefaultOptions($environment) {
        switch ($environment) {
        case 'production':
        case 'development':
        case 'test':
            return [
            'db' => false,
            'default_domain' => '',
            'view' => [],
            'loader' => [
                'controller' => [],
                'helper'     => [],
                'module'     => [],
                'provider'	 => []
            ],
            'title' => '',
            'module' => [],
            'theme' => ['shared']
            ];
        default:
            require_once 'System/NotSupportedException.php';
            throw new System_NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function onOptionChanged($optionName) {
        switch ($optionName) {
        case 'loader':
            if(!is_array($this->options['loader'])){
                require_once 'System/ArgumentOutOfRangeException.php';
                throw new System_ArgumentOutOfRangeException();
            }
            foreach($this->options['loader'] as $loaderName => $options) {
                if(!isset($this->_loaders[$loaderName]))
                    continue;
                if($this->_loaders[$loaderName] instanceof Kansas_PluginLoader_Interface)
                    foreach($options as $prefix => $path)
                    $loader->addPrefixPath($prefix, $path);
                elseif(is_array($this->_loaders[$loaderName]))
                    $this->_loaders[$loaderName] = array_merge($this->_loaders[$loaderName], $options);
                else{
                    require_once 'System/ArgumentOutOfRangeException.php';
                    throw new System_ArgumentOutOfRangeException();
                }
            }
            break;
        case 'theme':
            global $environment;
            $environment->setTheme($this->options['theme']);
            break;
            
        }
    }

    public function getLoader($loaderName) {
        if(!isset($this->_loaders[$loaderName]))
            return false;
        if(!($this->_loaders[$loaderName] instanceof Kansas_PluginLoader_Interface))
            $this->_loaders[$loaderName] = new Kansas_PluginLoader($this->_loaders[$loaderName]);
        return $this->_loaders[$loaderName];
    }

    public function getModules() {
        $result = [];
        foreach($this->options['module'] as $moduleName => $options) {
        $module = $this->getModule($moduleName);
        $result[$moduleName] = [
            'options'	=> $module->getOptions(),
            'version'	=> 'v' . (string)$module->getVersion()
        ];
        }
        $this->_modulesLoaded = true;
        return $result;
    }

    public function getModule($moduleName) { // Obtiene el modulo seleccionado, lo carga si es necesario
        if(!is_string($moduleName)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
        }
        $moduleName = ucfirst($moduleName);
        if(isset($this->_modules[$moduleName]))
            return $this->_modules[$moduleName];
        $options = (isset($this->options['module'][$moduleName]) && is_array($this->options['module'][$moduleName]))
            ? $this->options['module'][$moduleName]
            : [];
        try {
            $moduleClass = $this->getLoader('module')->load($moduleName);
            $module = new $moduleClass($options);
        } catch(Exception $e) {
            $this->log(E_USER_NOTICE, $e);        
            $module = false;
        }
        $this->_modules[$moduleName] = $module;
        return $module;
    }
    public function setModule($moduleName, $options) { // Guarda la configuración del modulo, y lo carga si el resto ya han sido cargados
        if(!is_string($moduleName))
            throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
        $moduleName = ucfirst($moduleName);
        if(isset($this->_modules[$moduleName]) && ($this->_modules[$moduleName] instanceof Kansas_Module_Interface)) {
            if(!is_array($options)) $options = [];
                $this->_modules[$moduleName]->setOptions($options);
        } else
            $this->_modules[$moduleName] = $options;
        if($this->_modulesLoaded)
            $this->getModule($moduleName);
    }
    public function hasModule($moduleName) { // Obtiene el modulo seleccionado si está cargado o false en caso contrario
        if(!is_string($moduleName))
            throw new System_ArgumentOutOfRangeException('moduleName', 'Se esperaba una cadena', $moduleName);
        $moduleName = ucfirst($moduleName);
        return isset($this->_modules[$moduleName]) && ($this->_modules[$moduleName] instanceof Kansas_Module_Interface)
            ? $this->_modules[$moduleName]
            : false;
    }
    
    public function getProvider($providerName) {
        if(!is_string($providerName))
            throw new System_ArgumentOutOfRangeException('providerName', $providerName, 'Se esperaba una cadena');
        $providerName = ucfirst($providerName);
        if(!isset($this->_providers[$providerName])) {
            $providerClass = $this->getLoader('provider')->load($providerName);
            $provider = new $providerClass($this->getDb());
            $this->_providers[$providerName] = $provider;
            $this->raiseCreateProvider($provider, $providerName);
        }
        return $this->_providers[$providerName];
    }
    
    public function dispatch($params) {
        $params     = $this->raiseDispatch($params);
        $controller = isset($params['controller'])
                    ? ucfirst($params['controller'])
                    : 'Index';
        $action     = isset($params['action'])
                    ? $params['action']
                    : 'Index';
        $controllerClass = $this->getLoader('controller')->load($controller);
        $class      = new $controllerClass();
        $action     = $params['action'];
        unset($params['controller']);
        unset($params['action']);
        $class->init($params);
        return $class->callAction($action, $params);
    }
    
    public function run() {
        global $environment;
        $this->getModules();
        $params = false;
        if($environment->getStatus() == 'install') {
            $router = new Kansas_Router_Install();
            $params = $router->match();
        } else {
            // PreInit
            $this->raisePreInit();
            foreach($this->_routers as $router) {
                if($params = $router->match())
                break;
            }
        }
        if($params) {  // Route event
            $params = array_merge($this->raiseRoute($params), $this->getDefaultParams());
            $result = $this->dispatch($params);
        }
        if(!isset($result) || $result == null)
            throw new System_Net_WebException(404);
        // Render
        $this->raiseRender($result);
        $result->executeResult();
    }
    
    // Asocia los parametros indicados y los básicos a la petición actual
    public static function getDefaultParams() {
        global $environment;
        $request = $environment->getRequest();
        return [
            'url'           => trim($request->getUri()->getPath(), '/'),
            'uri'           => $request->getUriString(),
            'requestType'   => $request->isXmlHttpRequest() ? 'XMLHttpRequest' : 
                                $request->isFlashRequest()  ? 'flash'
                                                            : 'HttpRequest'
        ];
    }
    
    /* Eventos */
    public function registerCallback($hook, $callback) {
        if(is_callable($callback) && isset($this->_callbacks[$hook]))
            $this->_callbacks[$hook][] = $callback;
    }

    protected function raisePreInit() {
        foreach ($this->_callbacks['preinit'] as $callback)
            call_user_func($callback);
    }
    protected function raiseRoute($params = array()) {
        global $environment;
        $request	= $environment->getRequest();
        foreach ($this->_callbacks['route'] as $callback)
            $params = array_merge($params, call_user_func($callback, $request, $params));
        return $params;
    }
    protected function raiseRender(Kansas_View_Result_Interface $result) {
        foreach ($this->_callbacks['render'] as $callback)
            call_user_func($callback, $result);
    }
    protected function raiseDispatch($params = array()) {
        global $environment;
        $request	= $environment->getRequest();
        foreach ($this->_callbacks['dispatch'] as $callback)
            $params = array_merge($params, call_user_func($callback, $request, $params));
        return $params;
    }
    protected function raiseCreateProvider($provider, $providerName) {
        foreach ($this->_callbacks['createProvider'] as $callback)
            $params = call_user_func($callback, $provider, $providerName);
    }
    protected function raiseCreateView($view) {
        foreach ($this->_callbacks['createView'] as $callback)
            $params = call_user_func($callback, $view);
    }

    public function getDb() {
        if($this->_db == NULL) {
            if($this->options['db'] instanceof Zend_Db)
                $this->_db = $this->options['db'];
            if(is_array($this->options['db']))
                $this->_db = Zend_Db::factory($this->options['db']['adapter'], $this->options['db']['params']);
        }
        return $this->_db;
    }

    /* Miembros de singleton */
    public static function getInstance($options) {
        global $application;
        if($application == null)
            $application = self::$_instance = new self($options);	
        return $application;
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
                        $this->setModule($module, $options);
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
        require_once 'Kansas/View/Smarty.php';
        if($this->_view == null) {
        $this->_view = new Kansas_View_Smarty($this->options['view']);
        if($this->_view->getCaching())
            $this->_view->setCacheId($environment->getRequest()->getRequestUri());
        $this->raiseCreateView($this->_view);
        }
        return $this->_view;
    }
    
    public function createTitle() {
        if($this->_title == NULL) {
        $titleClass = (isset($this->options['title']['class']))
            ?	$this->options['title']['class']
            : 'Kansas_TitleBuilder_Default';
        unset($this->options['title']['class']);
        $this->_title = new $titleClass($this->options['title']);
        }
        return $this->_title;
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
        @call_user_func($this->_errorCallback, $errData);
        return true; // No ejecutar el gestor de errores interno de PHP
    }
    
    public function exception_handler(Exception $ex) {
        $errData = self::getErrorData($ex);
        if(error_reporting() != 0)
        call_user_func($this->_logCallback, E_USER_ERROR, $errData);
        @call_user_func($this->_errorCallback, $errData);
        exit(1);
    }
    
    public function log($level, $message) {
        if($message instanceof Exception)
        $message = self::getErrorData($message);
        call_user_func($this->_logCallback, $level, $message);
    }
    
    public function errorManager($params) {
        $result = $this->dispatch(array_merge($params, [
        'controller'	=> 'Error',
        'action'			=> 'Index'
        ], $this->getDefaultParams()));
        $result->executeResult();
    }
    
    public static function getErrorData(Exception $ex) {
        return [
        'exception'   => get_class($ex),
        'errorLevel'	=> E_USER_ERROR,
        'code'				=> ($ex instanceof System_Net_WebException ? $ex->getStatus() : 500),
        'message'			=> $ex->getMessage(),
        'trace'				=> $ex->getTrace(),
        'line'				=> $ex->getLine(),
        'file'				=> $ex->getFile()
        ];
    }
    
    /* Enrutamiento */
    public function addRouter(Kansas_Router_Interface $router, $priority = 0) {
        $this->_routers->insert($router, $priority);
    }
  
}