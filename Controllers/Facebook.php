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
				$view = $this->createView();
        $view->assign('title', 'Conectar con facebook');
				$view->setCaching(false);
			
				$view->assign('ru', 			$ru);
				$view->assign('register',	$register);			
				$view->assign('cancel',	$cancel);
				$view->assign('email',	$user->getEmail());
				$view->assign('name',	$user->getName());
				return $this->createResult($view, 'page.fb-register.tpl');		
			}
			
		} catch(FacebookRequestException $ex) { 		  // When Facebook returns an error
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/' . $router->assemble(['action' => 'signin']) . http_build_query(['fb-error' => $ex->getCode(), 'ru' => $ru]));
			return $result;
		}

		
		if ($fbSession) {


		  // Logged in.

		} else {

		}
	}



}