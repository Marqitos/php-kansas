<?php

class Kansas_Module_Token {

  protected $options;
  
	public function __construct(array $options) {
    $this->options = $options;
		global $application;
		$usersModule = $application->getModule('Users');
		$usersModule->setAuthService('token', $this);
		$usersModule->getRouter()->setRoute('token', [
			'controller'	=> 'Token',
			'action'			=> 'index'
		]);
		
	}

	public function factory($token) {
		global $application;
		return new Kansas_Auth_Membership(
			$application->getProvider('SignIn'),
			$application->getProvider('Token'),
			$token
		);
	}
	
	public function getToken($device = true) {
		global $application;
		$auth = $application->getModule('auth');
		if(!$auth->hasIdentity())
			return false;
		
		global $application;
		$userId		= $auth->getIdentity()->getId();
		$deviceId	= $device && $application->hasModule('track') ?	$application->getModule('track')->getDevice()->getId():
																															null;
		$token = $application->getProvider('Token')->getToken($userId, $deviceId);
		if($token == null)
			$token = $application->getProvider('Token')->createToken($userId, $deviceId);
		return $token;
	}
	
	public function getAuthTokenUrl($url, $device = true) {
		global $application;
		return $_SERVER['SERVER_NAME'] . '/' . $application->getModule('Users')->getBasePath() . 'token?' . http_build_query([
			'token' => $this->getToken($device),
			'ru'		=> $url
		]);
	}
	
}