<?php
	
use Facebook\GraphUser;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

class Kansas_Controllers_Facebook
	extends Kansas_Controllers_Account {
		
	public function signIn() { //Trata de iniciar sesión a traves de una redirección desde facebook
		global $application;
		$ru = $this->getParam('ru', '/');
		$router = $this->getParam('router');
		try {
			$fbSession = Kansas_Auth_Facebook::getSessionFromRedirect($ru);
			if($fbSession == null)
				return Kansas_View_Result_Redirect::gotoUrl('/' .
					$router->assemble(['action' => 'signin']) . http_build_query([
						'fb-error' => $this->getParam('error'),
						'ru' => $ru]));
			$auth = $application->getModule('Auth');
			$authAdapter	= $auth->createAuthMembership('facebook', [$fbSession]);
			$result = $auth->authenticate($authAdapter);
			if($result->isValid())
				return Kansas_View_Result_Redirect::gotoUrl($ru);
			else {
				var_dump($result);
				
				$register = $router->assemble(['action' => 'fb-register']) . http_build_query(['ru' => $ru]);
				$cancel = $router->assemble(['action' => 'signin']) . http_build_query(['ru' => $ru]);
				$request = new FacebookRequest($fbSession, 'GET', '/me');
			  $user = $request->execute()->getGraphObject()->cast('Facebook\GraphUser');
        $application->getView()->setCaching(false);
        
				return $this->createViewResult('page.fb-register.tpl', [
          'title'     => 'Conectar con facebook',
          'ru'        => $ru,
          'register'  => $register,
          'cancel'    => $cancel,
          'email'     => $user->getEmail(),
          'name'      => $user->getName()
        ]);		
			}
			
		} catch(FacebookRequestException $ex) { 		  // When Facebook returns an error
      return Kansas_View_Result_Redirect::gotoUrl('/' . $router->assemble(['action' => 'signin']) . http_build_query(['fb-error' => $ex->getCode(), 'ru' => $ru]));
		}
		
		if ($fbSession) {


		  // Logged in.

		} else {

		}
	}



}