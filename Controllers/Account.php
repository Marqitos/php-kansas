<?php

class Kansas_Controllers_Account
	extends Kansas_Controller_Abstract {
		
	private $_auth;
	
	public function init(Zend_Controller_Request_Http $request) {
		parent::init($request);
		$this->_auth = Zend_Auth::getInstance();
	}
	
	protected function getModule() {
		global $application;
		return $application->getModule('Users');
	}
	
	public function index() {
		if(!$this->_auth->hasIdentity()) {
			$router = $this->getModule()->getRouter();
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/' . $router->getBasePath() . '/signin' . Kansas_Response::buildQueryString(array('ru' => '/' . $router->getBasePath())));
			return $result;
		} else {
			$view = $this->createPage('Perfil', 'Perfil de usuario');
			return $this->createResult($view, 'page.account.tpl');
		}
	}
	
	public function signIn() {
		$ru				= $this->getParam('ru', '/');
		$router 	= $this->getModule()->getRouter();
		if($this->_auth->hasIdentity()) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($ru);
			return $result;
		}
		
		$view = $this->createPage('Iniciar sesión');
		$view->setCaching(false);
		$view->assign('signin', 	true);
		$view->assign('ru', 			$ru);
		if($this->getModule()->getAuthService('membership') != false) {
			$email 		= $this->getParam('email');
			$password = $this->getParam('password');
	
			// Validar datos
			if(!empty($email) && !empty($password)) {
				$authAdapter	= $this->getModule()->createAuthMembership('membership', array($email, $password));
				$result				= Zend_Auth::getInstance()->authenticate($authAdapter);
				if($result->isValid()) {
					$redirect = new Kansas_View_Result_Redirect();
					$redirect->setGotoUrl($ru);
					return $redirect;
				}
			}
			
			$view->assign('email',		$email);
			$view->assign('password',	$password);
		}
		
		$externalSignin = [];
		foreach($this->getModule()->getAuthServices('external') as $name => $externalService) {
			$externalSignin[$name] = $externalService->getLoginUrl(['ru' => $ru]);
			
			
			$facebook = $this->getModule()->getAuthService('facebook')->getCore();
			$view->assign('fb_signin',	$facebook->getLoginUrl(array(
				'ru'	=> $ru,
				'redirect_uri'	=> 'http://zoltham.com/account/fb/signin' . Kansas_Response::buildQueryString(array('ru' => $ru))
			)));
		
		}
		$view->assign('external_signin', $externalSignin);
		return $this->createResult($view, 'page.account-signin.tpl');
	}
	
	public function signOut() {
		$ru	= $this->getParam('ru', '/');
		$this->_auth->clearIdentity();
		$redirect = new Kansas_View_Result_Redirect();
		$redirect->setGotoUrl($ru);
		return $redirect;
	}
	
	public function fbSignIn() {
		$facebook = $this->getModule()->getAuthService('facebook')->getCore();
		$ru				= $this->getParam('ru', '/');
		if(intval($facebook->getClass()->getUser()) == 0) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
		} elseif($facebook->isRegistered()) {
			$result = new Kansas_View_Result_Redirect();
			$authResult				= Zend_Auth::getInstance()->authenticate($facebook);
			if($authResult->isValid())
				$result->setGotoUrl($ru);
			else
				$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
				
		} else {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/account/fb/register' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
		}
		return $result;
	}
	
	public function fbRegister() {
		global $application;
		$ru				= $this->getParam('ru', '/');
		$facebook = $application->createAuthMembership('facebook');
		if(isset($_REQUEST['signed_request'])) {
			$facebook->register();
			$result	= Zend_Auth::getInstance()->authenticate($facebook);
			$redirect = new Kansas_View_Result_Redirect();
			if($result->isValid())
				$redirect->setGotoUrl($ru);
			else
				$redirect->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
			return $redirect;
		} else {
			$user = $facebook->getClass()->getUser();
			
			$view = $this->createView();
			$view->setCaching(false);
			$view->assign('user',		$user);
			$view->assign('signin', 	true);
			$view->assign('ru', 		$ru);
			$view->assign('fb_id', 	$facebook->getClass()->getAppId());
			return $this->createResult($view, 'page.fb-register.tpl');
		}
	}
	
}