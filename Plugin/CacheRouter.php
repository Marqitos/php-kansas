<?php
namespace Kansas\Plugin;

use Psr\Http\Message\ServerRequestInterface;
use System\Configurable;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\Cache as RouterCache;
use System\NotSupportedException;

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
        $application->registerCallback('preinit', [$this, 'appPreInit']);
        if($this->options['cacheRouting']) { // Cache de rutas
            $application->registerCallback('route', [$this, "appRoute"]);
        }
    }
  
    /// Miembros de ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        switch ($environment) {
        case 'production':
        case 'development':
        case 'test':
            return [
            'cache_category'    => 'router',
            'cache_type'        => null,
            'cache_options'     => []
            ];
        default:
            throw new NotSupportedException("Entorno no soportado [$environment]");
        }
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
        $application->addRouter($this->getRouter(), 10);
    }

    public function appRoute(ServerRequestInterface $request, $params) { // Guardar ruta en cache
        if(!isset($params['cache']) && !isset($params['error'])) {
            $this->getCache()->save(serialize($params), self::getCacheId($request));
        }
        return [];
    }
      
    public static function getCacheId(ServerRequestInterface $request) {
        global $application;
        $roles = $application->getPlugin('Auth')->getCurrentRoles();
        $permsId = md5(serialize($roles));
        var_dump($permsId);
        return urlencode($permsId . '|' . $request->getUriString());
    }
    
    public function getRouter() {
        if(!isset($this->router)) {
            require_once 'Kansas/Router/Cache.php';
            $this->router = new RouterCache($this->getCache());
        }
        return $this->router;
    }
  
}
		
	