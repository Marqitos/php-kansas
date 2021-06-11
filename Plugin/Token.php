<?php
/**
 * Plugin para el uso de tokesn JWT
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\Guid;
use System\NotSupportedException;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\Token as Router;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token as JWToken;

use function time;
use function Kansas\Request\getTrailData;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Token extends Configurable implements PluginInterface {

    private $router;

    /// Constructor
    public function __construct(array $options) {
        parent::__construct($options);
        global $application;
        $application->registerCallback('preinit', [$this, 'appPreInit']);
    }

	// Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'device'    => false,
                    'exp'       => 10 * 24 * 60 * 60, // 10 días
                    'secret'    => false
                ];
            default:
                require_once 'System/NotSupportedException.php';
                throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }

    public function appPreInit() {
        global $application;
        $application->addRouter($this->getRouter(), 100);
    }
    
    /**
     * Crea un token a partir de una cadena jwt,
     * y comprueba que sea válido
     *
     * @param string $tokenString
     * @return mixed Lcobucci\JWT\Token, o false
     */
    public function parse($tokenString) {
        global $environment;
        require_once 'Lcobucci/JWT/Parser.php';
        $parser = new Parser();
        $token = $parser->parse($tokenString);
        if($this->options['secret']) { // Comprobar firma mediante Hmac/Sha256
            require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
            $signer = new Sha256();
            if(!$token->verify($signer, $this->options['secret'])) {
                return false;
            }
        }
        if($this->options['device'] === true) { // Comprobar dispositivo
            if(!$token->hasClaim('dev')) {
                return false;
            }
            require_once 'Kansas/Request/getTrailData.php';
			$request = $environment->getRequest();
            $userAgent = getTrailData($request)['userAgent'];
            if(md5($userAgent, true) != hex2bin($token->getClaim('dev'))) {
                return false;
            }
        }
        return $token;
    }

    /**
     * Crea un enlace que realizara un dispatch al visitarlo, y devuelve la dirección
     *
     * @param array $data
     * @return string
     */
    public function createLink(array $data) {
        require_once 'System/Guid.php';
        $tokenData = [
            'iss'   => $_SERVER['SERVER_NAME'],
            'iat'   => time(),
            'exp'   => time() + $this->options['exp']
        ];
        if(isset($data['user'])) {
            $tokenData['sub'] = ($data['user'] instanceof Guid)
                ? $data['user']->getHex()
                : $data['user'];
            unset($data['user']);
        }
        $data = array_merge($tokenData, $data);
        $token = $this->createToken($data);
        $id = new Guid($token->getClaim('jti'));

        if($this->options['secret']) { // Devuelve un enlace firmado
            $signature = explode('.', (string) $token)[2];
            return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex() . '/' . $signature;
        }
        return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex();
    }

    public static function authenticate($userId) {
        global $application;
        $authPlugin         = $application->getPlugin('Auth');
        $localizationPlugin	= $application->getPlugin('Localization');
        $usersProvider 		= $application->getProvider('Users');
        $locale             = $localizationPlugin->getLocale();
        $user               = $usersProvider->getById($userId, $locale['lang'], $locale['country']);
        $authPlugin->setIdentity($user);
        return $user;
    }

    public function createToken(array $data, $device = null) {
        global $application, $environment;
        require_once 'System/Guid.php';
        require_once 'Lcobucci/JWT/Builder.php';
        $data = array_merge([
            'iss'   => $_SERVER['SERVER_NAME'],
            'iat'   => time(),
            'exp'   => time() + $this->options['exp']
        ], $data);
        if(isset($data['exp']) && !$data['exp']) {
            unset($data['exp']);
        }
        $builder = new Builder();
        foreach($data as $claim => $value) {
            $builder->withClaim($claim, $value);
        }
        if(!isset($data['jti'])) { // Establecemos Id
            $id = Guid::newGuid();
            $builder->identifiedBy($id->getHex());
        }
        if($device === null) {
            $device = $this->options['device'];
        }
        if($device === true) {
            require_once 'Kansas/Request/getTrailData.php';
			$request = $environment->getRequest();
            $userAgent = getTrailData($request)['userAgent'];
            $builder->withClaim('dev', md5($userAgent)); // guardar información del dispositivo
        }
        if($this->options['secret']) { // Firma el token mediante Hmac/Sha256
            require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
            $signer = new Sha256();
            $key = new Key($this->options['secret']);
            $token = $builder->getToken($signer, $key); // creates a signature
        } else {
            $token = $builder->getToken();
        }
        $provider = $application->getProvider('token');
        $provider->saveToken($token);
        return $token;
    }

    public function updateToken(JWToken $token, array $changes) {
        global $application;
        require_once 'System/Guid.php';
        require_once 'Lcobucci/JWT/Builder.php';
        $claims = $token->getClaims();
        $data = [];
        foreach($claims as $claim) {
            if(isset($changes[$claim->getName()])) {
                $data[$claim->getName()] = $changes[$claim->getName()];
                unset($changes[$claim->getName()]);
            } else {
                $data[$claim->getName()] = $claim->getValue();
            }
        }
        $data = array_merge($data, $changes);
        $builder = new Builder();
        foreach($data as $claim => $value) {
            $builder->withClaim($claim, $value);
        }
        if($this->options['secret']) { // Firma el token mediante Hmac/Sha256
            require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
            $signer = new Sha256();
            $key = new Key($this->options['secret']);
            $token = $builder->getToken($signer, $key); // creates a signature
        } else {
            $token = $builder->getToken();
        }
        if(isset($data['jti'])) { // Establecemos Id
            $provider = $application->getProvider('token');
            $provider->saveToken($token);
        }
        return $token;
    }

    public static function deleteToken(JWToken $token) {
        global $application;
        require_once 'System/Guid.php';
        $id = new Guid($token->getClaim('jti'));
        $provider = $application->getProvider('token');
        $provider->deleteToken($id);
    }

    public function getRouter() {
        if(!isset($this->router)) {
            require_once 'Kansas/Router/Token.php';
            $this->router = new Router($this, $this->options);
        }
        return $this->router;
    }

}