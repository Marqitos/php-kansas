<?php

namespace Kansas\Plugin;

use Kansas\Plugin\PluginInterface;
use Kansas\Router\Token as router;
use System\ArgumentOutOfRangeException;
use System\Configurable;
use System\Guid;
use System\NotSuportedException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;

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

    /// Miembros de Kansas\Configurable
    public function getDefaultOptions($environment) {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'device'    => true,
                    'exp'       => 10 * 24 * 60 * 60, // 10 días
                    'secret'    => false
                ];
            default:
                require_once 'System/NotSuportedException.php';
                throw new NotSuportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() {
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
            if(!$token->verify($signer, $this->options['secret']))
                return false;
        }
        if($this->options['device'] === true) { // Comprobar dispositivo
            if(!$token->hasClaim('dev'))
                return false;
            require_once 'Kansas/Request/getTrailData.php';
			$request = $environment->getRequest();
            $userAgent = getTrailData($request)['userAgent'];
            if(md5($userAgent, true) != hex2bin($token->getClaim('dev')))
                return false;
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

/*
        global $application;
        require_once 'System/Guid.php';
        require_once 'Lcobucci/JWT/Builder.php';
        $id = Guid::newGuid();
        $builder = new Builder();
        $builder->setIssuer('https://caybemarmoleria.es')   // Configures the issuer (iss claim)
            ->setId($id->getHex())                          // Configures the id (jti claim)
            ->setIssuedAt(time())                           // Configures the time that the token was issued (iat claim)
            ->setExpiration(time() + $this->options['exp']);    // Configures the expiration time of the token (exp claim)
        if(isset($data['user'])) {
            if($data['user'] instanceof Guid)
                $builder->setSubject($data['user']->getHex());
            else
                $builder->setSubject($data['user']);
            unset($data['user']);
        }
        foreach($data as $key => $value)
            $builder->set($key, $value);
        if($this->options['secret']) { // Firma el token mediante Hmac/Sha256
            require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
            $signer = new Sha256();
            $builder->sign($signer, $this->options['secret']); // creates a signature
        }
        $token = $builder->getToken();
        $provider = $application->getProvider('token');
        $provider->saveToken($id, $token);
*/

        if($this->options['secret']) { // Devuelve un enlace firmado
            $signature = explode('.', (string) $token)[2];
            return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex() . '/' . $signature;
        }
        return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex();
    }

    public static function authenticate(Guid $userId) {
        global $application;
        $authPlugin = $application->getPlugin('Auth');
        $usersProvider = $application->getProvider('users');
        $user = $usersProvider->getById($userId);
        $authPlugin->setIdentity($user);
        return $user;
    }

    public function createToken(array $data, $device = null) {
        global $application, $environment;
        require_once 'System/Guid.php';
        require_once 'Lcobucci/JWT/Builder.php';
<<<<<<< HEAD
=======
        $data = array_merge([
            'iss'   => $_SERVER['SERVER_NAME'],
            'iat'   => time(),
            'exp'   => time() + $this->options['exp']
        ], $data);
>>>>>>> origin/master
        $builder = new Builder();
        foreach($data as $claim => $value)
            $builder->set($claim, $value);
        if(isset($data['jti'])) // Establecemos Id
            $id = new Guid($data['jti']);
        else {
            $id = Guid::newGuid();
            $builder->setId($id->getHex());
        }
        if($device === null)
            $device = $this->options['device'];
        if($device === true) {
            require_once 'Kansas/Request/getTrailData.php';
			$request = $environment->getRequest();
            $userAgent = getTrailData($request)['userAgent'];
            $builder->set('dev', md5($userAgent)); // guardar información del dispositivo
        }
        if($this->options['secret']) { // Firma el token mediante Hmac/Sha256
            require_once 'Lcobucci/JWT/Signer/Hmac/Sha256.php';
            $signer = new Sha256();
            $builder->sign($signer, $this->options['secret']); // creates a signature
        }
        $token = $builder->getToken();
        $provider = $application->getProvider('token');
        $provider->saveToken($id, $token);
        return $token;
    }
/*                
    public function getToken($device = true) {
        global $application;
        $auth = $application->getModule('auth');
        if(!$auth->hasIdentity())
            return false;
        
        global $application;
        $userId		= $auth->getIdentity()->getId();
        $deviceId	= $device && $application->hasModule('track') 
            ? $application->getModule('track')->getDevice()->getId()
            : null;
        $token = $application->getProvider('Token')->getToken($userId, $deviceId);
        if($token == null)
            $token = $application->getProvider('Token')->createToken($userId, $deviceId);
        return $token;
    }
    
    public function getAuthTokenUrl($url, $device = true) {
        global $application;
        return $_SERVER['SERVER_NAME'] . '/' . $application->getModule('Users')->getBasePath() . 'token?' . http_build_query([
            'token' => $this->getToken($device),
            'ru'	=> $url
        ]);
    }
    
    */
    public function getRouter() {
        if(!isset($this->router)) {
            require_once 'Kansas/Router/Token.php';
            $this->router = new router($this, $this->options);
        }
        return $this->router;
    }

}