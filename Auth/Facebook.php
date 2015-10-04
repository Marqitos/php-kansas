<?php

use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;

class Kansas_Auth_Facebook
	implements Kansas_Auth_Adapter_Interface {
	
	private $_fbSession;
	
	public function __construct(FacebookSession $session = null) {
		$this->_fbSession = $session;
	}
	
	/**
	 * Performs an authentication attempt
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		global $application;
		if(!isset($this->_fbSession)) {
			if(!isset($_SESSION['fb-token']))
				return Kansas_Auth_Result::Failure(['No se ha obtenido la sesión de facebook']);
			$this->_fbSession =  new FacebookSession($_SESSION['fb-token']);
		}
		$userId = $this->_fbSession->getUserId();
		$fbUser = false;
		if($userId == null) {
			$fbUser = (new FacebookRequest($this->_fbSession, 'GET', '/me'))->execute()->getGraphObject(GraphUser::class);
			$userId = $fbUser->getId();
		}
		if($user = $application->getProvider('Auth_Facebook')->getUser($userId))
			return Kansas_Auth_Result::Success($user);
		if(!$fbUser)
			$fbUser = (new FacebookRequest($this->_fbSession, 'GET', '/me'))->execute()->getGraphObject(GraphUser::class);
		if($user = $application->getProvider('Users')->getByEmail($fbUser->getEmail())) // Conectar usuario
			return Kansas_Auth_Result::Failure(['No se ha conectado el usuario con la cuenta de facebook'], Kansas_Auth_Result::FAILURE_CREDENTIAL_INVALID);
		else // Nuevo usuario
			return Kansas_Auth_Result::Failure(['No hay ningun usuario con ese email'], Kansas_Auth_Result::FAILURE_IDENTITY_NOT_FOUND);				
	}
	
	public static function getRedirectLoginHelper($ru) {
		global $application;
		return new FacebookRedirectLoginHelper('http://' . $_SERVER['HTTP_HOST'] . $application->getModule('Auth')->getRouter()->assemble(['action' => 'fb-signin']) . http_build_query(['ru' => $ru]));
	}
		
	public static function getSessionFromRedirect($ru) {
		$loginHelper = self::getRedirectLoginHelper($ru);
		$fbSession = $loginHelper->getSessionFromRedirect();
		$_SESSION['fb-token'] = $fbSession->getToken();
		return $fbSession;	
	}
	
}