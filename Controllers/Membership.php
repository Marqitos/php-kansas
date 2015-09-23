<?php

class Kansas_Controllers_Membership
	extends Kansas_Controllers_Account {
		
	public function signIn($params) {
		global $application;
		$ru				= $this->getParam('ru', '/');
		$auth = $application->getModule('Auth');
		if($auth->hasIdentity()) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($ru);
			return $result;
		}
		$router 	= $this->getParam('router');		

		$view = $this->createView();
    $view->assign('title', 'Iniciar sesión');
		$view->setCaching(false);

		$view->assign('ru', 			$ru);
		$view->assign('signin', 	true);
		if($auth->getAuthService('membership')) {
			$email 		= $this->getParam('email', '');
			$password = $this->getParam('password');

			// Validar datos
			if(!empty($email) && !empty($password)) {
				
				$authAdapter	= $auth->createAuthMembership('membership', [
					$application->getDb('Membership'),
					$email,
					$password]);
				$result				= $auth->authenticate($authAdapter);
				if($result->isValid()) {
					$redirect = new Kansas_View_Result_Redirect();
					$redirect->setGotoUrl($ru);
					return $redirect;
				}
			}
			
			$view->assign('email',		$email);
			$view->assign('form-action', '/iniciar-sesion');
			$view->assign('remember-account', '/remember-account');
		}
		
		$view->assign('external_signin', parent::getExternalSignin($params));
		$view->assign('register', '/registro');
		return $this->createResult($view, 'page.account-signin.tpl');		
	}
	
}