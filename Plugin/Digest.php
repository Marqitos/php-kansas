<?php
/**
 * Plugin para la autentificación mediante HTTP Digest
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

 namespace Kansas\Plugin;

use System\Configurable;
use System\NotSupportedException;
use Kansas\Auth\ServiceInterface as AuthService;
use Kansas\Plugin\PluginInterface;
use Kansas\Auth\AuthException;

use function header;
use function preg_match_all;
use function strtolower;
use function strpos;
use function substr;

require_once 'System/Configurable.php';
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
		$application->registerCallback('preinit', [$this, "appPreInit"]);
		parent::__construct($options);
	}

	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions($environment) : array {
        switch ($environment) {
        case 'production':
        case 'development':
        case 'test':
            return [
                'actions' => [
            ]];
        default:
            require_once 'System/NotSupportedException.php';
            throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }
    
    public function getVersion() {
        global $environment;
        return $environment->getVersion();
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
                // Registar evento de intento de inicio de sesión
                }
                throw $ex;
            }
                // Based on all the info we gathered we can figure out what the response should be
            // $A1 = md5("{$validUser}:{$realm}:{$validPass}");
            $A1 = $digestParts['username'] == $this->_adminUsername ? $this->_adminA1 :
                                                                                                                                $this->_digest->getA1($this->_realm, $digestParts['username']); 
            if(!$A1)
                throw new AuthException(AuthException::FAILURE_CREDENTIAL_INVALID);
            else {
                
                $A2 = md5("{$_SERVER['REQUEST_METHOD']}:{$digestParts['uri']}");
                
                $validResponse = md5("{$A1}:{$digestParts['nonce']}:{$digestParts['nc']}:{$digestParts['cnonce']}:{$digestParts['qop']}:{$A2}");
                
                if ($digestParts['response']!=$validResponse)
                throw new AuthException(AuthException::FAILURE_CREDENTIAL_INVALID);
                else {
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
    public function requireLogin(Kansas_View_Result_String_Interface $cancelResult) {
        header('WWW-Authenticate: Digest realm="' . $this->_realm . '",qop="auth",nonce="' . $this->nonce . '",opaque="' . md5($this->_realm) . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo $cancelResult->getResult();
        die();
    }

    // This function returns the digest string
    public static function getDigest() {
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) // mod_php
            return $_SERVER['PHP_AUTH_DIGEST'];
        elseif (isset($_SERVER['HTTP_AUTHENTICATION']) &&
            strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'digest')===0) // most other servers
            return substr($_SERVER['HTTP_AUTHORIZATION'], 7);
        else
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
            $data[$m[1]] = $m[2] ? $m[2] : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
  }

}