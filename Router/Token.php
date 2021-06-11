<?php
/**
 * Router que interpreta rutas basadas en tokens
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Router;

use Kansas\Router;
use Kansas\Plugin\Token as TokenPlugin;
use System\Guid;
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
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'secret'  => FALSE,
                    'base_path' => 'token'
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    /// Miembros de Kansas\Router
	public function match() {
		$path = self::getPath($this);
        if($path === false) {
            return false;
        }
        require_once 'System/Guid.php';
        global $application, $environment;
        $parts = explode('/', trim($path, '/'));
        if(Guid::tryParse($parts[0], $id) && count($parts) == 2) {
            $provider = $application->getProvider('token');
            $token = $provider->getToken($id, $parts[1]);
            if($token) {
                $params = [
                    'token' => $token
                ];
                foreach ($token->getClaims() as $claim) {
                    if($claim->getName() == 'sub') {
                        $authPlugin = $application->getPlugin('Auth');
                        $identity   = $authPlugin->getIdentity();
                        if($identity !== false || $identity['id'] != $claim->getValue()) { // Iniciamos sesión solo si es necesario
                            $this->plugin->authenticate($claim->getValue());
                            // TODO: Registrar inicio de sesión
                        }
                    } else if(array_search($claim->getName(), ['jti', 'iss', 'exp', 'iat']) === false) {
                        $params[$claim->getName()] = $claim->getValue();
                    }
                }
                return $params;
            }
        }
        return false;
    }

}