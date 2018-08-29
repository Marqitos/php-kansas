<?php

namespace Kansas\Module;

use System\Configurable;
use Kansas\Module\ModuleInterface;
use System\ArgumentOutOfRangeException;
use System\NotSuportedException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;

require_once 'System/Configurable.php';
require_once 'Kansas/Module/ModuleInterface.php';


class Token extends Configurable implements ModuleInterface {

    /// Constructor
    public function __construct(array $options) {
        parent::__construct($options);
        
        if(!isset($this->options['secret'])) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('secret');
        }
        global $application;
        $application->registerCallback('preinit', [$this, 'appPreInit']);
    }

//		$usersModule->getRouter()->setRoute('token', [
//			'controller'	=> 'Token',
//			'action'		=> 'index'
//		]);
        
    /// Miembros de Kansas_Module_Interface
    public function getDefaultOptions($environment) {
        switch ($environment) {
            case 'production':
            case 'development':
            case 'test':
                return [
                    'secret'  => FALSE
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

    

    public function parse($tokenString) {
        $parser = new Parser();
        $signer = new Sha256();
        $token = $parser->parse($tokenString);
        $token->verify($signer, $this->options['secret']);
        return $token;
    }
            
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
    
}