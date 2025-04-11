<?php declare(strict_types = 1);
/**
  * Clase principal de ejecución de la aplicación
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
  * @version    v0.6
  */

namespace Kansas;

use Throwable;
use Kansas\AppStatus;
use Kansas\Db\Adapter as DbAdapter;
use Kansas\Router\RouterInterface;
use Kansas\View\Result\ViewResultInterface;
use System\AggregateException;
use System\ArgumentOutOfRangeException;
use System\Configurable;
use System\DisposableInterface;
use System\EnvStatus;
use System\NotSupportedException;
use System\Net\WebException;

use function Kansas\Request\getRequestType;
use function array_keys;
use function array_merge;
use function call_user_func;
use function get_class;
use function is_array;
use function ucfirst;

require_once 'AppStatus.php';
require_once 'System/Configurable.php';
require_once 'System/DisposableInterface.php';
require_once 'System/AggregateException.php';

class Application extends Configurable implements DisposableInterface {

    // Constantes
    public const EVENT_PREINIT      = 'preinit';
    public const EVENT_ROUTE        = 'route';
    public const EVENT_RENDER       = 'render';
    public const EVENT_DISPATCH     = 'dispatch';
    public const EVENT_C_PROVIDER   = 'createProvider';
    public const EVENT_C_PLUGIN     = 'createPlugin';
    public const EVENT_C_VIEW       = 'createView';
    public const EVENT_ERROR        = 'error';
    public const EVENT_LOG          = 'log';
    public const EVENT_DISPOSE      = 'dispose';

    // Campos
    private $providers              = [];
    private $disposables            = [];
    private $status                 = AppStatus::START;
    private $db;

    private $plugins;

    private $router;

    private $view;
    private $title;

    // Eventos
    private $callbacks = [
        self::EVENT_PREINIT     => [],
        self::EVENT_ROUTE       => [],
        self::EVENT_RENDER      => [],
        self::EVENT_DISPATCH    => [],
        self::EVENT_C_PROVIDER  => [],
        self::EVENT_C_PLUGIN    => [],
        self::EVENT_C_VIEW      => [],
        self::EVENT_ERROR       => [],
        self::EVENT_LOG         => [],
        self::EVENT_DISPOSE     => []
    ];


    protected static $instance;

    /**
      * Crea una nueva instancia Kansas\Application
      *
      * @param array $options Configuración de la aplicación
      */
    public function __construct(array $options) {
        $this->registerEvent(self::EVENT_CHANGED, [$this, 'onOptionChanged']);
        parent::__construct($options);
        register_shutdown_function([$this, 'dispose']);
    }

    public function __destruct() {
        $this->dispose();
    }

## Miembros de System\Configurable\ConfigurableInterface

    /**
      * @inheritDoc
      */
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'db'                => false,
            'default_domain'    => '',
            'loader'            => [
                'controller'        => [],
                'plugin'            => [],
                'provider'          => []],
            'log'               => ['Kansas\\Environment', 'log'],
            'plugin'            => [],
            'title'             => [
                'class'             => 'Kansas\\TitleBuilder\\DefaultTitleBuilder'],
            'view'              => [],
        ];
    }

    public function onOptionChanged($optionName) {
        if ($optionName == 'loader') {
            if (!is_array($this->options['loader'])){
                require_once 'System/ArgumentOutOfRangeException.php';
                throw new ArgumentOutOfRangeException('optionName');
            }
            foreach ($this->options['loader'] as $loaderName => $options) {
                Environment::addLoaderPaths($loaderName, $options);
            }
        } elseif ($optionName == 'language') {
            Environment::setLanguage($this->options['language']);
        }
    }
## -- System\Configurable\ConfigurableInterface

## Miembros de System\DisposableInterface

    /**
      * @inheritDoc
      *
      * @return void
      */
    public function dispose() : void {
        $disposing = $this->status != AppStatus::DISPOSED;
        $this->status = AppStatus::DISPOSED;
        $this->disposing($disposing);
    }

    /**
      * @inheritDoc
      *
      * @return boolean
      */
    public function isDisposed() : bool {
        return $this->status == AppStatus::DISPOSED;
    }

    protected function disposing(bool $disposing) : AggregateException|true {
        $result = true;
        // Liberamos recursos mediante llamada a eventos
        foreach ($this->callbacks[self::EVENT_DISPOSE] as $callback) {
            try {
                call_user_func($callback, $disposing);
            } catch (Throwable $th) {
                if (is_a($result, 'System\AggregateException')) {
                    $result->addInnerException($th);
                } else {
                    $result = new AggregateException($th);
                }
            }
        }

        // Liberamos recursos de los objetos conocidos
        while (count($this->disposables) > 0) {
            $disposable = array_shift($this->disposables);
            try {
                $disposable->dispose();
            } catch (Throwable $th) {
                if (is_a($result, 'System\AggregateException')) {
                    $result->addInnerException($th);
                } else {
                    $result = new AggregateException($th);
                }
            }
        }

        return $result;
    }

## -- System\DisposableInterface

    protected function init(): AggregateException|true {
        $result = true;
        foreach ($this->callbacks[self::EVENT_PREINIT] as $callback) { // PreInit event
            try {
                call_user_func($callback);
            } catch (Throwable $th) {
                if (is_a($result, 'System\AggregateException')) {
                    $result->addInnerException($th);
                } else {
                    $result = new AggregateException($th);
                }
            }
        }
        $this->status = AppStatus::INIT;
        return $result;
    }

    protected function route($request, array $params): array {
        $this->status = AppStatus::ROUTE;
        $params     = [...$params, ...self::getDefaultParams()];
        foreach ($this->callbacks[self::EVENT_ROUTE] as $callback) { // Route event
            $params = [...$params, ...call_user_func($callback, $request, $params)];
        }
        return $params;
    }

    protected function render(ViewResultInterface $viewResult) {
        foreach ($this->callbacks[self::EVENT_RENDER] as $callback) { // Render event
            call_user_func($callback, $viewResult);
        }
    }

    protected function error(Throwable $th) {
        $result = false;
        $this->status = AppStatus::ERROR;
        foreach ($this->callbacks[self::EVENT_ERROR] as $callback) { // PreInit event
            try {
                $result = call_user_func($callback, $th);
                if ($result) {
                    return $result;
                }
            } catch (Throwable $th) {
                if (is_a($result, 'System\AggregateException')) {
                    $result->addInnerException($th);
                } else {
                    $result = new AggregateException($th);
                }
            }
        }
        if (! $result) {
            $result = $th;
        }
        return $result;
    }


    public function getPlugin(string $pluginName) { // Obtiene el modulo seleccionado, lo carga si es necesario
        $pluginName = ucfirst($pluginName);
        $this->loadPlugins();
        if (! isset($this->plugins[$pluginName])) {
            $options = (isset($this->options['plugin'][$pluginName]) && is_array($this->options['plugin'][$pluginName]))
                ? $this->options['plugin'][$pluginName]
                : [];
            return $this->loadPlugin($pluginName, $options);
        }
        return $this->plugins[$pluginName];
    }

    public function setPlugin(string $pluginName, array $options = []) { // Guarda la configuración del modulo, y lo carga si el resto ya han sido cargados
        $pluginName = ucfirst($pluginName);
        if (is_array($this->plugins)) {
            if (isset($this->plugins[$pluginName])) {
                $this->plugins[$pluginName]->setOptions($options);
                return $this->plugins[$pluginName];
            } else {
                $this->options['plugin'][$pluginName] = $options;
                return $this->loadPlugin($pluginName, $options);
            }
        } else {
            $this->options['plugin'][$pluginName] = $options;
        }
        return false;
  }

    public function hasPlugin(string $pluginName) { // Obtiene el modulo seleccionado si está cargado o false en caso contrario
        $pluginName = ucfirst($pluginName);
        return (is_array($this->plugins) && isset($this->plugins[$pluginName]))
            ? $this->plugins[$pluginName]
            : false;
    }

    public function getPlugins() {
        $this->loadPlugins();
        $result = [];
        foreach ($this->plugins as $pluginName => $plugin) {
            if(!$plugin) {
                $result[$pluginName] = [
                    'type'    => get_class($plugin),
                    'options' => $plugin->getOptions(),
                    'version' => 'v' . (string)$plugin->getVersion()
                ];
            }
        }
        return $result;
    }

    public function loadPlugins() {
        if (is_array($this->plugins)) {
            return;
        }
        $this->plugins = [];
        foreach (array_keys($this->options['plugin']) as $pluginName) {
            $this->getPlugin($pluginName);
        }
    }

    protected function loadPlugin($pluginName, array $options) {
        try {
            $plugin = Environment::createPlugin($pluginName, $options);
        } catch(Throwable $e) {
            $plugin = false;
            throw $e;
        }
        $this->plugins[$pluginName] = $plugin;
        return $plugin;
    }

    public function searchProvider(string $type) {
        foreach ($this->plugins as $pluginName => $options) {
            $plugin = $this->getPlugin($pluginName);
            if (is_a($plugin, $type)) {
                return $this->plugins[$pluginName];
            }
        }
        return false;
    }

    public function getProvider(string $providerName) {
        $providerName = ucfirst($providerName);
        if(!isset($this->providers[$providerName])) {
            $provider = Environment::createProvider($providerName);
            $this->providers[$providerName] = $provider;
            foreach ($this->callbacks[self::EVENT_C_PROVIDER] as $callback) {
                call_user_func($callback, $provider, $providerName);
            }
        }
        return $this->providers[$providerName];
    }

    public function dispatch(array $params) : ViewResultInterface {
        $request        = Environment::getRequest();
        foreach ($this->callbacks[self::EVENT_DISPATCH] as $callback) { // Dispatch event
            $params     = array_merge($params, call_user_func($callback, $request, $params));
        }
        $controllerName = isset($params['controller'])
                        ? ucfirst($params['controller'])
                        : 'Index';
        $action         = isset($params['action'])
                        ? $params['action']
                        : 'Index';
        unset($params['action']);
        unset($params['controller']);
        $controller     = Environment::createController($controllerName);
        $controller->init($params);
        $this->status = AppStatus::DISPATCH;
        return $controller->callAction($action, $params);
    }

    public function run() : void {
        $params     = false;
        $viewResult = false;
        $this->loadPlugins();
        $init = $this->init();
        if (is_a($init, 'Throwable')) {
            $result = $this->error($init);
            if (is_array($result)) {
                $params = $result;
            } elseif(is_a($result, 'Kansas\View\Result\ViewResultInterface')) {
                $viewResult = $result;
            } elseif (Environment::getStatus() == EnvStatus::DEVELOPMENT) {
                var_dump($result);
            }
        }
        if (! $params &&
            ! $viewResult) {
            if (is_array($this->router)) {
                foreach ($this->router as $router) {
                    $params = $router->match();
                    if ($params !== false) {
                        break;
                    }
                }
            } elseif (is_a($this->router, 'Kansas\Router\RouterInterface')) {
                $params = $this->router->match();
            }
            if (! $params) {
                require_once 'System/Net/WebException.php';
                $result = $this->error(new WebException(404));
                if (is_array($result)) {
                    $params = $result;
                } elseif(is_a($result, 'Kansas\View\Result\ViewResultInterface')) {
                    $viewResult = $result;
                }
            }
        }
        if ($params &&
            ! $viewResult) {
            $this->status = AppStatus::ROUTE;
            $request    = Environment::getRequest();
            $params     = $this->route($request, $params);
            $viewResult = $this->dispatch($params);
        }
        if (! $viewResult) {
            require_once 'System/Net/WebException.php';
            $result = $this->error(new WebException(500));
            if(is_a($result, 'Kansas\View\Result\ViewResultInterface')) {
                $viewResult = $result;
            } elseif(is_a($result, 'Throwable')) {
                throw $result;
            } else {
                throw new WebException(500);
            }
        }
        $this->render($viewResult);
        $viewResult->executeResult();
    }

    /**
     * Devuelve los parámetros básicos de la petición actual
     */
    public static function getDefaultParams() : array {
        require_once 'Kansas/Request/getRequestType.php';
        $request = Environment::getRequest();
        return [
            'url'         => trim($request->getUri()->getPath(), '/'),
            'uri'         => $request->getRequestTarget(),
            'requestType' => getRequestType($request)
        ];
    }

    /* Eventos */
    public function registerCallback(string $hook, callable $callback) : void {
        if (isset($this->callbacks[$hook])) {
            $this->callbacks[$hook][] = $callback;
        }
    }

    public function getDb() : DbAdapter {
        require_once 'Kansas/Db/Adapter.php';
        if ($this->db == null) {
            if ($this->options['db'] instanceof DbAdapter) {
                $this->db = $this->options['db'];
            }
            if (is_array($this->options['db'])) {
                if (isset($this->options['db']['driver'])) {
                    $driver = $this->options['db']['driver'];
                    unset($this->options['db']['driver']);
                    $this->db = DbAdapter::Create($driver, $this->options['db']);
                } else {
                    require_once 'System/NotSupportedException.php';
                    throw new NotSupportedException();
                }
            }
            if (is_a($this->db, 'System\DisposableInterface')) {
                $this->disposables[] = $this->db;
            }
        }
        return $this->db;
    }

    public function getView() {
        if($this->view == null) {
            $viewClass = $this->options['view']['class'];
            unset($this->options['view']['class']);
            $this->view = new $viewClass($this->options['view']);
            if($this->view->getCaching()) {
                $this->view->setCacheId(Environment::getRequest()->getUri());
            }
            foreach ($this->callbacks[self::EVENT_C_VIEW] as $callback) {
                call_user_func($callback, $this->view);
            }
        }
        return $this->view;
    }

    public function createTitle() {
        if($this->title == null) {
            $titleClass = (isset($this->options['title']['class']))
                ? $this->options['title']['class']
                : 'Kansas\\TitleBuilder\\DefaultTitleBuilder';
            unset($this->options['title']['class']);
            $this->title = new $titleClass($this->options['title']);
        }
        return $this->title;
    }

    /* Miembros de singleton */
    public static function getInstance(array $options) : self {
        global $application;
        if($application == null) {
            if(self::$instance == null) {
                self::$instance = new self($options);
            }
            $application = self::$instance;
        }
        return $application;
    }

    /* Enrutamiento */
    public function addRouter(RouterInterface $router) : void {
        if ($this->router === null) {
            $this->router = [];
        }
        $this->router[] = $router;
    }

}
