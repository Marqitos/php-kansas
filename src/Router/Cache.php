<?php declare(strict_types = 1);
/**
  * Router que devuelve archivos en cache
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Router;

use Kansas\Router;
use Kansas\Router\RouterInterface;
use Kansas\Cache\CacheInterface;
use System\EnvStatus;


require_once 'Kansas/Router.php';

class Cache extends Router implements RouterInterface {

    public function __construct(private CacheInterface $cache) {
        parent::__construct(['cache' => $this->cache]);
    }

## Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment): array {
        return [
            'base_path' => '',
            'cache'     => null,
            'params'    => [
                'controller'    => 'Index',
                'action'        => 'content'
            ]
        ];
    }
## -- ConfigurableInterface

    public function match(): array|false {
        $path   = self::getPath($this);
        if ($path === false) {
            return false;
        }

        if ($this->cache->test($path)) {
            $params = unserialize($this->cache->load($path));
            return $this->getParams($params);
        }

        return false;
    }
}
