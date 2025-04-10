<?php declare(strict_types = 1);

namespace Kansas\Plugin;

use Kansas\Application;
use Psr\Http\Message\ServerRequestInterface;
use System\Configurable;
use System\EnvStatus;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\Cache as RouterCache;

require_once 'Psr/Http/Message/ServerRequestInterface.php';
require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class CacheRouter extends Configurable implements PluginInterface {

    /// Campos
    private $router;
    private $cache;

    /// Constructor
    public function __construct(array $options) {
        global $application;
        parent::__construct($options);
        $application->registerCallback(Application::EVENT_PREINIT, [$this, 'appPreInit']);
        if($this->options['cache_routing']) { // Cache de rutas
            $application->registerCallback(Application::EVENT_ROUTE, [$this, "appRoute"]);
        }
    }

    /// Miembros de ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'cache_category'    => 'router',
            'cache_type'        => null,
            'cache_options'     => [],
            'cache_routing'     => false
        ];
    }

    /// Miembros de PluginInterface
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }

    public function getCache() {
        if(!isset($this->cache)) {
            global $application;
            $cacheModule = $application->getPlugin('BackendCache');
            $this->cache = $cacheModule->getCache(
                $this->options['cache_category'],
                $this->options['cache_type'],
                $this->options['cache_options']);
        }
        return $this->cache;
    }

    /// Eventos de la aplicación
    public function appPreInit() {
        global $application;
        $application->addRouter($this->getRouter());
    }

    public function appRoute(ServerRequestInterface $request, $params) { // Guardar ruta en cache
        if(! isset($params['cache']) &&
           ! isset($params['error'])) {
            $this->getCache()->save(serialize($params), self::getCacheId($request));
        }
        return [];
    }

    public static function getCacheId(ServerRequestInterface $request) {
        global $application;
        $role = $application->getPlugin('Auth')->getRole();
        $permsId = md5(serialize($role));
        return urlencode($permsId . '|' . $request->getUri()->String());
    }

    public function getRouter() {
        if(!isset($this->router)) {
            require_once 'Kansas/Router/Cache.php';
            $this->router = new RouterCache($this->getCache());
        }
        return $this->router;
    }

}

