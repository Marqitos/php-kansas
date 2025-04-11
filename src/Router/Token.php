<?php
/**
  * Router que interpreta rutas basadas en tokens
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Router;

use Kansas\Environment;
use Kansas\Router;
use Kansas\Plugin\Token as TokenPlugin;
use System\Guid;
use System\EnvStatus;
use System\NotSupportedException;

use function explode;
use function trim;

require_once 'Kansas/Router.php';
require_once 'Kansas/Plugin/Token.php';

class Token extends Router {

    protected $plugin;

    public function __construct(TokenPlugin $plugin, array $options) {
        parent::__construct($options);
        $this->plugin = $plugin;
    }

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'secret'    => false,
            'base_path' => 'token'
        ];
    }

    /// Miembros de Kansas\Router
    public function match() {
        $path = self::getPath($this);
        if ($path === false) {
            return false;
        }
        require_once 'System/Guid.php';
        global $application;
        $parts = explode('/', trim($path, '/'));
        if (Guid::tryParse($parts[0], $id) && count($parts) == 2) {
            $provider = $application->getProvider('token');
            $token = $provider->getToken($id, $parts[1]);
            if($token) {
                $params = [
                    'token' => $token
                ];
                foreach ($token->getClaims() as $claim) {
                    if ($claim->getName() == 'sub') {
                        $authPlugin = $application->getPlugin('Auth');
                        $identity   = $authPlugin->getIdentity();
                        if ($identity !== false ||
                            $identity['id'] != $claim->getValue()) { // Iniciamos sesión solo si es necesario
                            $this->plugin->authenticate($claim->getValue());
                            // TODO: Registrar inicio de sesión
                        }
                    } elseif(array_search($claim->getName(), ['jti', 'iss', 'exp', 'iat']) === false) {
                        $params[$claim->getName()] = $claim->getValue();
                    }
                }
                return $params;
            }
        }
        return false;
    }

}
