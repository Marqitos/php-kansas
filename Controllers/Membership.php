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
    $application->getView()->setCaching(false);
    $membershipData = [];
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
			
      $membershipData = [
        'email'             => $email,
        'form-action'       => '/iniciar-sesion',
        'remember-account'  => '/remember-account'
      ];
		}
		
		return $this->createResult('page.account-signin.tpl', array_merge([
        'title'           => 'Iniciar sesión',
        'ru'              => $ru,
        'signin'          => true,
        'external_signin' => parent::getExternalSignin($params),
        'register'        => '/registro'
      ], $membershipData));		
	}
	
}