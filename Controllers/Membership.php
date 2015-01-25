<?php

class Kansas_Controllers_Membership
	extends Kansas_Controller_Account {
		
	public function signIn() {
		$ru				= $this->getParam('ru', '/');
		$router 	= $this->getParam('router');
		if($this->_auth->hasIdentity()) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($ru);
			return $result;
		}
		
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
		

		$view = $this->createPage('Iniciar sesión');
		$view->setCaching(false);
		$view->assign('email',		$email);
		$view->assign('password',	$password);
		$view->assign('ru', 			$ru);
		$view->assign('signin', 	true);

		$facebook = $this->getModule()->getAuthService('facebook')->getCore();
		$view->assign('fb_signin',	$facebook->getLoginUrl(array(
			'ru'	=> $ru,
			'redirect_uri'	=> 'http://zoltham.com/account/fb/signin' . Kansas_Response::buildQueryString(array('ru' => $ru))
		)));

		return $this->createResult($view, 'page.account-signin.tpl');
	}
	
}