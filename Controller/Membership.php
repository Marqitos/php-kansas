<?php
require_once 'Kansas/Controller/Account.php';

class Kansas_Controller_Membership
    extends Kansas_Controller_Auth {
    
    /* Devuelve la vista de inicio de sesión o
     * autentica al usuario mediante usuario y contraseña y devuelve la redireccion a la página donde se encontraba
     */ 
    public function signIn(array $vars) {
        global $application;
        $ru		= $this->getParam('ru', '/');
        $auth 	= $application->getModule('Auth');
        $identity = $auth->getSession()->getIdentity();
        $error  = 0;
        if ($identity != FALSE &&
            !System_Guid::isEmpty($identity)) {
            require_once 'Kansas/View/Result/Redirect.php';
            return Kansas_View_Result_Redirect::gotoUrl($ru);
        }
        $application->getView()->setCaching(false);

        $email 		= $this->getParam('email');
        $password 	= $this->getParam('password');
        $remember 	= $this->getParam('remember', false);

        // Validar datos
        if(!empty($email) && !empty($password)) {
            require_once 'Kansas/Module/Membership.php';
            require_once 'Kansas/Auth/Exception.php';
            try {
                $user = Kansas_Module_Membership::authenticate($email, $password, $remember);
                require_once 'Kansas/View/Result/Redirect.php';
                return Kansas_View_Result_Redirect::gotoUrl($ru);
            } catch(Kansas_Auth_Exception $ex) {
                $error = $ex->getErrorCode();
            }
        }

        $router = $auth->getRouter();
        return $this->createViewResult('page.membership-signin.tpl', [
            'title'				=> 'Iniciar sesión',
            'ru'				=> $ru,
            'authAction'	    => 'signIn',
            'email'             => $email,
            'remember'		    => $remember,
            'error'			    => $error,
//			'externalSignin'	=> parent::getExternalSignin($vars),
            'signUp'			=> $router->assemble([
                'action'            => 'signup',
                'ru'	            => $ru]),
            'formAction'        => $router->assemble([
                'action'            => 'signin',
                'ru'	            => $ru]),
            'rememberAccount'   => $router->assemble([
                'action' => 'remember',
                'ru'     => $ru])
        ]);		
    }

  public function signUp(array $vars) {
    global $application;
    $ru     = $this->getParam('ru', '/');
    $email 	= $this->getParam('email');

    return $this->createViewResult('page.membership-signup.tpl', [
        'title'				    => 'Registro de usuario',
        'ru'				    => $ru,
        'authAction'	        => 'signUp',
        'email'                 => $email,
        'error'				    => $error,
    ]);	

  }
    
}