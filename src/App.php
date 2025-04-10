<?php declare(strict_types = 1);
/**
  * Proporciona métodos estáticos y tipados relativos a $application y $environment;
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */
namespace Kansas;

use Kansas\Db\Adapter;
use Kansas\Http\ServerRequest;
use Kansas\Plugin\PluginInterface;
use Kansas\Plugin\RouterPluginInterface;
use Kansas\Provider\AbstractDb;
use Kansas\Router\RouterInterface;
use System\Localization\Resources;

require_once 'Kansas/Db/Adapter.php';
require_once 'Kansas/Http/ServerRequest.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Provider/AbstractDb.php';
require_once 'Kansas/Router/RouterInterface.php';

class App {

    public static function getPlugin(string $pluginName) : PluginInterface {
        global $application;
        return $application->getPlugin($pluginName);
    }

    public static function getProvider(string $providerName) : AbstractDb {
        global $application;
        return $application->getProvider($providerName);
    }

    public static function getRouter(string $pluginName) : ?RouterInterface {
        require_once 'Kansas/Plugin/RouterPluginInterface.php';
        global $application;
        $plugin = $application->hasPlugin($pluginName);
        if (is_a($plugin, 'Kansas\Plugin\RouterPluginInterface')) {
            return $plugin->getRouter();
        }
        return null;
    }

    public static function hasPlugin($pluginName) {
        global $application;
        return $application->hasPlugin($pluginName);
    }

    public static function getRequest() : ServerRequest {
        global $environment;
        return $environment->getRequest();
    }

    public static function getSpecialFolder(int $folderId) {
        global $environment;
        return $environment->getSpecialFolder($folderId);
    }

    public static function getDb() : Adapter {
        global $application;
        return $application->getDb();
    }

    public static function getLocale(array $keys, ...$values) {
        require_once 'System/Localization/Resources.php';
        $value = Resources::getResource($keys);
        if (!$value) {
            return false;
        }
        return count($values) == 0
            ? $value
            : sprintf($value, ...$values);

    }

}
