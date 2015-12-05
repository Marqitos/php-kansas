<?php

class Kansas_Controllers_Account
	extends Kansas_Controller_Abstract {
		
	
	public function init(array $params) {
		parent::init($params);
	}
	
	
	static function getSignInRedirection($ru = '/') {
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl(self::getRouter()->assemble([
			'action' => 'signin',
			'ru'     => $ru
		]));
		return $result;
	}
	
	public function index() {
		global $application;
		if(!$application->getModule('Auth')->hasIdentity())
			return self::getSignInRedirection('/' . trim($this->getRequest()->getUri()->getPath(), '/'));
		else {
			$view = $this->createView();
      $view->assign('title', 'Perfil de usuario');
			return $this->createResult($view, 'page.account.tpl');
		}
	}
	
	protected static function getExternalSignIn($params) {
		global $application;
		$externalSignin = [];
		$params = array_merge($_REQUEST, $params);
		foreach($application->getModule('Auth')->getAuthServices('external') as $name => $externalService)
			$externalSignin[] = array_merge($externalService->getLoginUrl($params), ['name' => $name]);
		return $externalSignin;		
	}
	
	public function signOut() {
		global $application;
		$ru	= $this->getParam('ru', '/');
		$application->getModule('Auth')->clearIdentity();
		$redirect = new Kansas_View_Result_Redirect();
		$redirect->setGotoUrl($ru);
		return $redirect;
	}
	
	public function fbSignIn() {
		global $application;
		$facebook = $this->getModule()->getAuthService('facebook')->getCore();
		$ru				= $this->getParam('ru', '/');
		if(intval($facebook->getClass()->getUser()) == 0) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
		} elseif($facebook->isRegistered()) {
			$result = new Kansas_View_Result_Redirect();
			$authResult				= $application->getModule('Auth')->authenticate($facebook);
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
			$result	= $application->getModule('Auth')->authenticate($facebook);
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