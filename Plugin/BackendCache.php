<?php
/**
 * Plugin para el almacenamiento en cache
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\Version;
use Kansas\Cache;
use Kansas\Plugin\PluginInterface;
use function array_merge;
use function md5;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class BackendCache extends Configurable implements PluginInterface {
  
    /// Campos
    private $caches = [];

    /// Constructor
    public function __construct(array $options) {
        require_once 'Kansas/Cache.php';
        require_once 'Kansas/Cache/CacheInterface.php';
        parent::__construct($options);
        $this->caches['.'] = Cache::Factory( // Creamos el almacenamiento de cache
            $this->options['cache_type'],
            $this->options['cache_options']
        );

    }
  
    /// Miembros de ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        return [
            'cache_type'    => 'File',
            'cache_options' => []];
    }
  
    /// Miembros de PluginInterface
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }
  
    public function getCache(string $category = '.', string $cacheType = null, array $cacheOptions = []) {
        if(!isset($this->caches[$category])) {
            if(empty($cacheType)) {
                $cacheType = $this->options['cache_type'];
            }
            $cacheOptions = array_merge($this->options['cache_options'], $cacheOptions);
            $cacheOptions['file_name_prefix'] = $category;
            $this->cache[$category] = Cache::Factory( // Creamos el almacenamiento de cache
                $cacheType,
                $cacheOptions
            );
        }
        return $this->caches[$category];
    }
  
    /// Miembros de Kansas_Cache_Interface
    public function setDirectives(array $directives) {
        return $this->caches['.']->setDirectives($directives);
    }
  
    public function save($data, $id, array $tags = [], $specificLifetime = false) {
        return $this->caches['.']->save($data, $id, $tags, $specificLifetime);
    }
  
    public function load($id, $doNotTestCacheValidity = false) {
        return $this->caches['.']->load($id, $doNotTestCacheValidity);
    }
  
    public function test($cacheId) {
        return $this->caches['.']->test($cacheId);
    }
  
    public function clean($mode = Cache::CLEANING_MODE_ALL, array $tags = []) {
        return $this->caches['.']->clean($mode, $tags);
    }
  
    public function remove($id) {
        return $this->caches['.']->remove($id);
    }
  
    /// Miembros de Kansas_Cache_ExtendedInterface
    public function getIdsMatchingTags($tags = []) {
        return $this->caches['.']->getIdsMatchingTags($tags);
    }
    
    /**
     * Devuelve un id para identificar la llamada a una función con unos parámetros específicos
     * 
     * @param array $args Lista de argumentos originales de la función
     * @param string $functionName Nombre de la función
     * @param string $className Nombre de la clase a la que pertenece la función, recomendable con su espacio de nombres. (Opcional)
     * @return string Clave identificativa relativa a los parámetros facilitados
     */
    private static function cacheId(array $args, string $functionName, string $className = null) : string {
        $key    = md5(serialize($args));
        $key   .= ($className === null)
                ? '-' . $functionName
                : '-' . $className . '-' . $functionName;
        return $key;
    }

    /**
     * Recupera de cache el valor identificado por una función y sus parámetros.
     * Para uso solamente con funciones puras, sin efectos secundarios
     * 
     * @param array $args Lista de argumentos originales de la función
     * @param string $functionName Nombre de la función
     * @param mixed &$data Parámetro de salida donde se almacenan los datos obtenidos de caché
     * @param string $className Nombre de la clase a la que pertenece la función, recomendable con su espacio de nombres. (Opcional)
     * @return bool true en caso de que hubiese datos en cache, false en caso contrario
     */
    public function memoize(array $args, string $functionName, &$data, string $className = null) : bool {
        $key    = self::cacheId($args, $functionName, $className);
        if($this->caches['.']->test($key)) {
            $data = unserialize($this->caches['.']->load($key));
            return true;
        }
        return false;
    }

    /**
     * Almacena en cache el valor relativo a una función y sus parámetros.
     * Para uso solamente con funciones puras, sin efectos secundarios
     * 
     */
    public function memoized(array $args, string $functionName, $data, string $className = null, array $tags = []) {
        $key    = self::cacheId($args, $functionName, $className);
        $data   = serialize($data);
        $this->caches['.']->save($data, $key, $tags);
    }

}
