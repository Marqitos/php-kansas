<?php

namespace Kansas;

use Exception;
use SplPriorityQueue;
use Throwable;
use System\ArgumentOutOfRangeException;
use System\Configurable;
use System\NotSupportedException;
use System\Net\WebException;
use Kansas\Environment;
use Kansas\Loader\PluginInterface;
use Kansas\PluginLoader;
use Kansas\Router\RouterInterface;
use Kansas\View\Smarty;
use Kansas\View\Result\ViewResultInterface;
use Kansas\Db\Adapter as DbAdapter;

use function array_merge;
use function is_string;
use function is_array;
use function set_error_handler;
use function set_exception_handler;
use function Kansas\Request\getRequestType;
use function ucfirst;
use function get_class;

class Application extends Configurable {
	
	private $_providers = [];
	private $db;

	private $_modules	    = [];
	private $_modulesLoaded = false;
    private $plugins;

	private $_routers;
	
	private $_view;
	private $_title;
	
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

	public function __construct(array $options) {
		set_error_handler([$this, 'errorHandler']);
		set_exception_handler([$this, 'exceptionHandler']);
		$this->_routers = new SplPriorityQueue();
		$this->registerOptionChanged([$this, 'onOptionChanged']);
		parent::__construct($options);
	}

	// Miembros de System\Configurable\ConfigurableInterface
	public function getDefaultOptions($environment) : array {
		switch ($environment) {
		case 'production':
		case 'development':
		case 'test':
			return [
				'db' => false,
				'default_domain' => '',
				'error' => [$this, 'errorManager'],
				'loader' => [
					'controller' => [],
					'plugin'     => [],
					'provider'	 => []
				],
				'log' => ['Kansas\\Environment', 'log'],
				'plugin' => [],
				'theme' => ['shared'],
				'title' => [
					'class'      => 'Kansas\\TitleBuilder\\DefaultTitleBuilder'
				],
				'view' => [],
			];
		default:
			require_once 'System/NotSupportedException.php';
			throw new NotSupportedException("Entorno no soportado [$environment]");
		}
	}

	public function onOptionChanged($optionName) {
		global $environment;

		switch ($optionName) {
			case 'loader':
				if(!is_array($this->options['loader'])){
					require_once 'System/ArgumentOutOfRangeException.php';
					throw new ArgumentOutOfRangeException();
				}
				foreach($this->options['loader'] as $loaderName => $options) {
					$environment->addLoaderPaths($loaderName, $options);
				}
				break;
			case 'theme':
				$environment->setTheme($this->options['theme']);
				break;
		}
	}

    public function getPlugin($pluginName) { // Obtiene el modulo seleccionado, lo carga si es necesario
        if(!is_string($pluginName)) { // Comprobar parametros
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('pluginName', 'Se esperaba una cadena', $pluginName);
		}
        $pluginName = ucfirst($pluginName);
        // Devolver plugin
		$this->loadPlugins();
		$options = (isset($this->options['plugin'][$pluginName]) && is_array($this->options['plugin'][$pluginName]))
			? $this->options['plugin'][$pluginName]
			: [];
        return isset($this->plugins[$pluginName])
            ? $this->plugins[$pluginName]
            : $this->loadPlugin($pluginName, $options);
    }

    public function setPlugin($pluginName, array $options = []) { // Guarda la configuración del modulo, y lo carga si el resto ya han sido cargados
        if(!is_string($pluginName)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('pluginName', 'Se esperaba una cadena', $pluginName);
        }
        $pluginName = ucfirst($pluginName);
        if(!is_array($options)) $options = [];
        if(is_array($this->plugins)) {
            if(isset($this->plugins[$pluginName])) {
				$this->plugins[$pluginName]->setOptions($options);
				return $this->plugins[$pluginName];
			} else {
                $this->options['plugin'][$pluginName] = $options;
                return $this->loadPlugin($pluginName, $options);
            }
        } else
			$this->options['plugin'][$pluginName] = $options;
		return false;
    }

    public function hasPlugin($pluginName) { // Obtiene el modulo seleccionado si está cargado o false en caso contrario
        if(!is_string($pluginName)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('pluginName', 'Se esperaba una cadena', $pluginName);
        }
        $pluginName = ucfirst($pluginName);
        return  (is_array($this->plugins) && isset($this->plugins[$pluginName]))
            ? $this->plugins[$pluginName]
            : false;
    }

    public function getPlugins() {
        $this->loadPlugins();
        $result = [];
        foreach($this->plugins as $pluginName => $plugin) {
            if($plugin != false) {
                $result[$pluginName] = [
                    'type'      => get_class($plugin),
                    'options'	=> $plugin->getOptions(),
                    'version'	=> 'v' . (string)$plugin->getVersion()
                ];
            }
        }
        return $result;
    }

    public function loadPlugins() {
        if(is_array($this->plugins))
			return;
        $this->plugins = [];
        foreach(array_keys($this->options['plugin']) as $pluginName) {
			$this->getPlugin($pluginName);
		}
    }

    protected function loadPlugin($pluginName, array $options) {
		global $environment;
        try {
			$plugin = $environment->createPlugin($pluginName, $options);
        } catch(Throwable $e) {
            $this->log(E_USER_NOTICE, $e);
            $plugin = false;
        }
        $this->plugins[$pluginName] = $plugin;
		return $plugin;
    }

	public function getProvider($providerName) {
		if(!is_string($providerName)) {
			require_once 'System/ArgumentOutOfRangeException.php';
			throw new ArgumentOutOfRangeException('providerName', 'Se esperaba una cadena', $providerName);
		}
		global $environment;
		$providerName = ucfirst($providerName);
		if(!isset($this->_providers[$providerName])) {
			$provider = $environment->createProvider($providerName);
			$this->_providers[$providerName] = $provider;
			$this->raiseCreateProvider($provider, $providerName);
		}
		return $this->_providers[$providerName];
	}
	
	public function dispatch($params) {
		global $environment;
		$params         = $this->raiseDispatch($params);
		$controllerName = isset($params['controller'])
						? ucfirst($params['controller'])
						: 'Index';
		unset($params['controller']);
		$action         = isset($params['action'])
						? $params['action']
						: 'Index';
		unset($params['action']);
		$controller     = $environment->createController($controllerName);
		$controller->init($params);
		return $controller->callAction($action, $params);
	}
	
	public function run() {
		global $environment;
		$this->loadPlugins();
		$params = false;
		if($environment->getStatus() == 'install') {
			require_once 'Kansas/Router/Install.php';
			// use Kansas\Router\Install as RouterInstall;
			$router = new RouterInstall();
			$params = $router->match();
		} else {
			$this->raisePreInit(); // PreInit event
			foreach($this->_routers as $router) {
				if($params = $router->match()) {
					$params['router'] = get_class($router);
					break;
				}
			}
		}
		if($params) {
		  $params = array_merge($params, $this->getDefaultParams());
		  $params = $this->raiseRoute($params); // Route event
		  $result = $this->dispatch($params);
		}
		if(!isset($result) || $result === null) {
			require_once 'System/Net/WebException.php';
			throw new WebException(404);
		}
		// Render
		$this->raiseRender($result);
		$result->executeResult();
	}
	
	// Asocia los parametros indicados y los básicos a la petición actual
	public static function getDefaultParams() {
		require_once 'Kansas/Request/getRequestType.php';
		global $environment;
		$request = $environment->getRequest();
		return [
			'url'         => trim($request->getUri()->getPath(), '/'),
			'uri'         => $request->getRequestTarget(),
			'requestType' => getRequestType($request)
		];
	}
	
	/* Eventos */
	public function registerCallback($hook, callable $callback) {
		if(isset($this->_callbacks[$hook]))
			$this->_callbacks[$hook][] = $callback;
	}

	protected function raisePreInit() {
		foreach ($this->_callbacks['preinit'] as $callback)
			call_user_func($callback);
	}
	protected function raiseRoute($params = []) {
		global $environment;
		$request	= $environment->getRequest();
		foreach ($this->_callbacks['route'] as $callback)
			$params = array_merge($params, call_user_func($callback, $request, $params));
		return $params;
	}
	protected function raiseRender(ViewResultInterface $result) {
		foreach ($this->_callbacks['render'] as $callback)
			call_user_func($callback, $result);
	}
	protected function raiseDispatch($params = []) {
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
		require_once 'Kansas/Db/Adapter.php';
//		require_once 'Zend/Db/Adapter/Adapter.php';
		if($this->db == NULL) {
			if($this->options['db'] instanceof DbAdapter)
				$this->db = $this->options['db'];
			if(is_array($this->options['db']))
				$this->db = new DbAdapter($this->options['db']);
		}
		return $this->db;
	}

	/* Miembros de singleton */
	public static function getInstance($options) {
		global $application;
		if($application == null)
			$application = self::$_instance = new self($options);	
		return $application;
	}
	
	public function getView() {
		global $environment;


		if($this->_view == null) {
			//require_once 'Kansas/View/Smarty.php';
			//$this->_view = new Smarty($this->options['view']);
			$viewClass = $this->options['view']['class'];
			unset($this->options['view']['class']);
			$this->_view = new $viewClass($this->options['view']);
			if($this->_view->getCaching())
				$this->_view->setCacheId($environment->getRequest()->getUri());
			$this->raiseCreateView($this->_view);
		}
		return $this->_view;
	}
	
	public function createTitle() {
		if($this->_title == NULL) {
		$titleClass = (isset($this->options['title']['class']))
			? $this->options['title']['class']
			: 'Kansas\\TitleBuilder\\DefaultTitleBuilder';
		unset($this->options['title']['class']);
		$this->_title = new $titleClass($this->options['title']);
		}
		return $this->_title;
	}

	public function getConfig() {
		return $this->_config;
	}
	
	/* Gestion de errores */
	public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if (!(error_reporting() & $errno))
			return false; // Este código de error no está incluido en error_reporting
		$trace = debug_backtrace();
		array_shift($trace);
		
		$errData = [
			'exception'   	=> null,
			'errorLevel'	=> $errno,
			'code'			=> 500,
			'message'		=> $errstr,
			'trace'			=> $trace,
			'line'			=> $errline,
			'file'			=> $errfile,
			'context'		=> $errcontext
		];
		if(error_reporting() != 0)
			@call_user_func($this->options['log'], $errno, $errData);
		if($errno == E_USER_ERROR) 
			@call_user_func($this->options['error'], $errData);
		return true; // No ejecutar el gestor de errores interno de PHP
	}
	
	public function exceptionHandler(Throwable $ex) {
		$errData = self::getErrorData($ex);
		if(error_reporting() != 0)
			@call_user_func($this->options['log'], E_USER_ERROR, $errData);
		@call_user_func($this->options['error'], $errData);
		exit(1);
	}
	
	public function log($level, $message) {
		if($message instanceof Throwable)
			$message = self::getErrorData($message);
		call_user_func($this->options['log'], $level, $message);
	}
	
	public function errorManager($params) {
		$result = $this->dispatch(array_merge($params, [
			'controller'	=> 'Error',
			'action'		=> 'Index'
			], $this->getDefaultParams()));
		$result->executeResult();
	}
	
	public static function getErrorData(Throwable $ex) {
		require_once 'System/Net/WebException.php';
		return [
			'exception'     => get_class($ex),
			'errorLevel'	=> E_USER_ERROR,
			'code'			=> ($ex instanceof WebException ? $ex->getStatus() : 500),
			'message'		=> $ex->getMessage(),
			'trace'			=> $ex->getTrace(),
			'line'			=> $ex->getLine(),
			'file'			=> $ex->getFile()
		];
	}
	
	/* Enrutamiento */
	public function addRouter(RouterInterface $router, $priority = 0) {
		$this->_routers->insert($router, $priority);
	}
  
}