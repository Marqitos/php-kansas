<?php

class Kansas_Application_Module_Token
	extends Kansas_Application_Module_Abstract {

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
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
		$auth = Zend_Auth::getInstance();
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
		return $_SERVER['SERVER_NAME'] . '/' . $application->getModule('Users')->getBasePath() . 'token' . Kansas_Response::buildQueryString([
			'token' => $this->getToken($device),
			'ru'		=> $url
		]);
	}
	
}