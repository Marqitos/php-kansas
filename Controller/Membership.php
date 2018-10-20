<?php

namespace Kansas\Controller;

use Kansas\Controller\Auth;
use Kansas\View\Result\Redirect;
use Kansas\Module\Membership as MembershipModule;
use System\Guid;

require_once 'Kansas/Controller/Auth.php';

class Membership extends Auth {
    
    /* Devuelve la vista de inicio de sesión o
     * autentica al usuario mediante usuario y contraseña y devuelve la redireccion a la página donde se encontraba
     */ 
    public function signIn(array $vars) {
        require_once 'System/Guid.php';
        global $application, $environment;
        $ru		= $this->getParam('ru', '/');
        $auth 	= $application->getModule('Auth');
        $identity = $auth->getSession()->getIdentity();
        $error  = 0;
        if ($identity !== false && !Guid::isEmpty($identity)) {
            require_once 'Kansas/View/Result/Redirect.php';
            return Redirect::gotoUrl($ru);
        }

        $email = '';
        $remember = false;
        $errors = 0;

        $request = $environment->getResquest();
        if($request->getMethod() == RequestMethodInterface::METHOD_POST) {
            require_once 'Kansas/Auth/Exception.php';
            $application->getView()->setCaching(false);

            $email 		= $this->getParam('email', $email);
            $password 	= $this->getParam('password');
            $remember 	= $this->getParam('remember', $remember);
    
            // Validar datos
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail = filter_var($email, FILTER_SANITIZE_EMAIL);
                $error = AuthException::FAILURE_USERNAME;
            }
            if(!filter_var($password, FILTER_VALIDATE_REGEXP, [
                "options" => [
                    "regexp" => "^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,72}$"
                ]])) {
                $error = AuthException::FAILURE_PASSWORD;
            }

            if($error = 0) {
                require_once 'Kansas/Module/Membership.php';
                try {
                    $user = MembershipModule::authenticate($email, $password, $remember);
                    require_once 'Kansas/View/Result/Redirect.php';
                    return Redirect::gotoUrl($ru);
                } catch(Kansas_Auth_Exception $ex) {
                    $error = $ex->getErrorCode();
                }
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