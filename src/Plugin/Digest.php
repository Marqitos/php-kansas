<?php
/**
  * Plugin para la autentificación mediante HTTP Digest
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Exception;
use Kansas\Application;
use Kansas\Auth\ServiceInterface as AuthService;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;
use Kansas\Auth\AuthException;
use Kansas\View\Result\StringInterface;
use Kansas\Configurable;
use System\EnvStatus;
use System\Version;

use function header;
use function preg_match_all;
use function strtolower;
use function strpos;
use function substr;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Auth/ServiceInterface.php';
require_once 'Kansas/Plugin/PluginInterface.php';

//CREATE TABLE  `Digest` (
// `Id` BINARY( 16 ) NOT NULL ,
// `Realm` BINARY( 16 ) NOT NULL ,
// `A1` BINARY( 16 ) NOT NULL ,
//PRIMARY KEY (  `Id` ),
//  UNIQUE INDEX `U_REALM` (`Id` ASC, `Realm` ASC)
//) COMMENT =  'Autenticación HttpDigest';

class Digest extends Configurable implements PluginInterface, AuthService {

    private $nonce;

    /// Constructor
    public function __construct(array $options) {
        global $application;
        $this->nonce = uniqid();
        $application->registerCallback(Application::EVENT_PREINIT, [$this, "appPreInit"]);
        parent::__construct($options);
    }

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
            return [
                'actions' => []];
    }

    public function getVersion() : Version {
        return Environment::getVersion();
    }


    // Obtiene las acciones de autenticación del servicio
    public function getActions() {
        return $this->options['actions'];
    }
    // Obtiene el tipo de autenticación
    public function getAuthType() {
        return 'http';
    }
    // Obtiene el nombre del servicio de autenticación
    public function getName() {
        return 'digest';
    }

      /// Eventos de la aplicación
    public function appPreInit() {
        global $application;
        $authPlugin = $application->getPlugin('Auth');
        $authPlugin->addAuthService($this);
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate($realm) {
        $digest = self::getDigest();
        // If there was no digest, show login
        require_once 'Kansas/Auth/AuthException.php';
        if (!$digest) {
            throw new AuthException(AuthException::FAILURE_UNCATEGORIZED);
        } else {
            $digestParts = self::digestParse($digest);
            global $application;
            $provider = $application->getProvider('Auth_Digest');
            try {
                $user = $provider->validate($digestParts, $realm);

                // Registrar eventos de inicio de sesión
                $authPlugin = $application->getPlugin('Auth');

                return $user;
            } catch(AuthException $ex) {
                if($ex->getErrorCode() != AuthException::FAILURE_UNCATEGORIZED) {
                // Registrar evento de intento de inicio de sesión
                }
                throw $ex;
            }

            // Based on all the info we gathered we can figure out what the response should be
            // $A1 = md5("{$validUser}:{$realm}:{$validPass}");
            $a1 = $digestParts['username'] == $this->_adminUsername
                ? $this->_adminA1
                : $this->_digest->getA1($this->_realm, $digestParts['username']);
            if(!$a1) {
                throw new AuthException(AuthException::FAILURE_CREDENTIAL_INVALID);
            } else {

                $a2 = md5("{$_SERVER['REQUEST_METHOD']}:{$digestParts['uri']}");

                $validResponse = md5("{$a1}:{$digestParts['nonce']}:{$digestParts['nc']}:{$digestParts['cnonce']}:{$digestParts['qop']}:{$a2}");

                if ($digestParts['response']!=$validResponse) {
                    throw new AuthException(AuthException::FAILURE_CREDENTIAL_INVALID);
                } else {
                    try {
                        $user = $this->_users->getByEmail($digestParts['username']);
                    } catch(Exception $exception) {
                        // Todo: Save exception
                    }
                    $result = Kansas_Auth_Result::Success($user);
                }
            }
        }
        return $result;
    }

    // This function forces a login prompt
    public function requireLogin(StringInterface $cancelResult) {
        header('WWW-Authenticate: Digest realm="' . $this->_realm . '",qop="auth",nonce="' . $this->nonce . '",opaque="' . md5($this->_realm) . '"');
        header('HTTP/1.0 401 Unauthorized');
        $cache = false;
        echo $cancelResult->getResult($cache);
        die();
    }

    // This function returns the digest string
    public static function getDigest() {
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) { // mod_php
            return $_SERVER['PHP_AUTH_DIGEST'];
        } elseif(isset($_SERVER['HTTP_AUTHENTICATION']) &&
                 strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'digest') === 0) { // most other servers
            return substr($_SERVER['HTTP_AUTHORIZATION'], 7);
        }
        return false;
    }

    // This function extracts the separate values from the digest string
    public static function digestParse($digest) {
        // protect against missing data
        $needed_parts = [
            'nonce'     => 1,
            'nc'        => 1,
            'cnonce'    => 1,
            'qop'       => 1,
            'username'  => 1,
            'uri'       => 1,
            'response'  => 1
        ];
        $data = [];

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2]
                ? $m[2]
                : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
  }

}
