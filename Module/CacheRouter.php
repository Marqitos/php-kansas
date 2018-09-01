<?php
namespace Kansas\Module;

use System\Configurable;
use Kansas\Module\ModuleInterface;
use Kansas\Router\Cache as RouterCache;
use System\NotSupportedException;
use Exception;

class CacheRouter extends Configurable implements ModuleInterface {
  
    /// Campos
    private $router;
    private $cache;

    /// Constructor
    public function __construct(array $options) {
        global $application;
        parent::__construct($options);
        $application->registerCallback('preinit', [$this, 'appPreInit']);
        if($this->options['cacheRouting']) // Cache de rutas
            $application->registerCallback('route', [$this, "appRoute"]);
    }
  
    /// Miembros de ConfigurableInterface
    public function getDefaultOptions($environment) {
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
  
    /// Miembros de ModuleInterface
    public function getVersion() {
		global $environment;
		return $environment->getVersion();
    }
  
    public function getCache() {
        if(!isset($this->cache)) {
            global $application;
            $cacheModule = $application->getModule('BackendCache');
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

    public function appRoute(Kansas_Request $request, $params) { // Guardar ruta en cache
        if(!isset($params['cache']) && !isset($params['error'])) {
            $cache = $this->getCache();
            $cache->save(serialize($params), self::getCacheId($request));
        }
        return [];
    }
      
    public static function getCacheId(Kansas_Request $request) {
        global $application;
        $roles = $application->getModule('auth')->getCurrentRoles();
        $permsId = md5(serialize($roles));
        var_dump($permsId);
        return urlencode($permsId . '|' . $request->getUriString());
    }
    
    public function getRouter() {
        if(!isset($this->router)) {
            $cache = $this->getCache();
            require_once 'Kansas/Router/Cache.php';
            $this->router = new RouterCache($cache);
        }
        return $this->router;
    }
  
}
		
	