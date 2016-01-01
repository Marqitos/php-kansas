<?php

class Kansas_Controllers_Account
	extends Kansas_Controller_Abstract {
		
	
	public function init(array $params) {
		parent::init($params);
	}
	
	static function getSignInRedirection($ru = '/') {
		return Kansas_View_Result_Redirect::gotoUrl(self::getRouter()->assemble([
			'action' => 'signin',
			'ru'     => $ru
		]));
	}
	
	public function index() {
		global $application;
		if(!$application->getModule('Auth')->hasIdentity())
			return self::getSignInRedirection('/' . trim($this->getRequest()->getUri()->getPath(), '/'));
		else
			return $this->createViewResult('page.account.tpl', [
        'title' => 'Perfil de usuario'
      ]);
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
		if(intval($facebook->getClass()->getUser()) == 0)
      return Kansas_View_Result_Redirect::gotoUrl('/account/signin' . http_build_query([
        'ru'  => $ru
      ]));
		elseif($facebook->isRegistered()) {
			$authResult				= $application->getModule('Auth')->authenticate($facebook);
			if($authResult->isValid())
				return Kansas_View_Result_Redirect::gotoUrl($ru);
			else
				return Kansas_View_Result_Redirect::gotoUrl('/account/signin' . http_build_query([
          'ru'	=> $ru
        ]));
				
		} else
      return Kansas_View_Result_Redirect::gotoUrl('/account/fb/register' . http_build_query([
        'ru'	=> $ru
      ]));
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
      $application->getView()->setCaching(false);
			return $this->createResult('page.fb-register.tpl',[
        'user'    => $facebook->getClass()->getUser(),
        'signin'  => true,
        'ru'      => $ru,
        'fb_id'   => $facebook->getClass()->getAppId()
      ]);
		}
	}
	
}