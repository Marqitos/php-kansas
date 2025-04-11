<?php declare(strict_types = 1);
/**
  * Proporciona la funcionalidad basica de un router (MVC)
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas;

use Kansas\Environment;
use Kansas\Plugin\BackendCache;
use Kansas\Router\RouterInterface;
use Kansas\Configurable;
use System\EnvStatus;
use function array_merge;
use function mb_strlen;
use function mb_substr;
use function trim;
use function System\String\startWith;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Router/RouterInterface.php';

class Router extends Configurable implements RouterInterface {

    protected function getParams(array $params) : array {
        return [...$this->options['params'], ...$params];
    }

## Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'base_path' => '',
            'params'  => []
        ];
    }
## -- ConfigurableInterface

## Miembros de Kansas\Router\RouterInterface
    public function getBasePath(): string {
        return $this->options['base_path'];
    }
## -- RouterInterface

    public function match(): array|false {
        global $application;
        $cachePlugin = $application->hasPlugin('BackendCache');
        if ($cachePlugin) {
            $path   = self::getPath($this);
            if ($path === false) {
                return false;
            }

            $cache = $cachePlugin->getCache(
                'router',
                BackendCache::TYPE_FILE, [
                'cache_dir' => Environment::getSpecialFolder(Environment::SF_V_CACHE)]
            );
            if ($cache->test($path)) {
                $match = unserialize($cache->load($path));
                if (is_array($match)) {
                    $match['action'] = 'content';
                    return $this->getParams($match);
                }
            }
        }
        return false;
    }

    public function assemble($data = [], $reset = false, $encode = false): string {
        return isset($data['basepath'])
            ? $data['basepath']
            : '/' . $this->getBasePath();
    }

    public function setBasePath(string $basePath) {
        $this->options['base_path'] = trim($basePath, '/');
    }

    public static function getPath(RouterInterface $router): string|false {
        require_once 'System/String/startWith.php';
        $path = trim(Environment::getRequest()->getUri()->getPath(), '/');
        $basePath = $router->getBasePath();
        if (mb_strlen($basePath) == 0) {
            $result = $path;
        } elseif(!startWith($path, $basePath)) {
            return false;
        } else {
            $result = trim(mb_substr($path, mb_strlen($basePath)), '/');
        }
        return $result === false
            ? ''
            : $result;
    }

}
