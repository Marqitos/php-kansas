<?php

namespace Kansas\Controller;

use Kansas\Auth\AuthException;
use Kansas\Controller\Auth;
use Kansas\View\Result\Redirect;
use Kansas\Plugin\Membership as MembershipPlugin;
use Kansas\Plugin\Contacts as ContactsPlugin;
use Psr\Http\Message\RequestMethodInterface;
use System\Guid;

use function Kansas\Email\serverSend as emailSend;
use function Kansas\Http\Request\post as httpRequestPost;
use function System\String\isNullOrEmpty as stringIsNullOrEmpty;
use function curl_init;

require_once 'Kansas/Controller/Auth.php';

class Membership extends Auth {

    const REQUIRE_USERNAME = 1;
    const REQUIRE_NAME     = 2;
    const REQUIRE_SURNAME  = 4;
    const FAILURE_USERNAME = 9;
    const FAILURE_CAPTCHA  = 16;
    const FAILURE_TOS      = 32;
    const FAILURE_SENDMAIL = 64;

    const SIGNUP_ACTION_DEFAULT  = 0;
    const SIGNUP_ACTION_NEW_USER = 1;
    const SIGNUP_ACTION_VALIDATE_USER = 2;

    /**
     * Devuelve la vista de inicio de sesión o
     * autentica al usuario mediante usuario y contraseña y devuelve la redireccion a la página donde se encontraba
     *
     * @param array $vars
     * @return Kansas\View\Result
     */ 
    public function signIn(array $vars) {
        global $application, $environment;
        $ru		    = $this->getParam('ru', '/');
        $identity   = self::getIdentity($vars);
        if ($identity !== false) {
            require_once 'Kansas/View/Result/Redirect.php';
            return Redirect::gotoUrl($ru);
        }
        require_once 'Psr/Http/Message/RequestMethodInterface.php';

        $email      = '';
        $remember   = false;
        $error      = 0;
        $email 		= $this->getParam('email', $email);

        $request = $environment->getRequest();
        if($request->getMethod() == RequestMethodInterface::METHOD_POST) {
            require_once 'System/String/isNullOrEmpty.php';

            $password 	= $this->getParam('password');
            $remember 	= $this->getParam('remember', $remember);
    
            // Validar datos
            if(stringIsNullOrEmpty($email)) {
                $error += AuthException::REQUIRE_USERNAME;
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail = filter_var($email, FILTER_SANITIZE_EMAIL);
                $error += AuthException::FAILURE_USERNAME;
            }
            if(stringIsNullOrEmpty($password)) {
                $error += AuthException::REQUIRE_PASSWORD;
            } elseif(!filter_var($password, FILTER_VALIDATE_REGEXP, [
                "options" => [
                    "regexp" => '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,72}$/'
                ]])) {
                $error += AuthException::FAILURE_PASSWORD;
            }

            if($error == 0) {
                require_once 'Kansas/Plugin/Membership.php';
                try {
                    MembershipPlugin::authenticate($email, $password, $remember);
                    require_once 'Kansas/View/Result/Redirect.php';
                    return Redirect::gotoUrl($ru);
                } catch(AuthException $ex) {
                    $error = $ex->getErrorCode();
                }
            }
            $application->getView()->setCaching(false);
        } else {
            $error 		= $this->getParam('error', $error);
        }

        $router = $application->getPlugin('Auth')->getRouter();
        return $this->createViewResult('page.membership-signin.tpl', [
            'title'				=> 'Iniciar sesión',
            'ru'				=> $ru,
            'authAction'	    => 'signIn',
            'email'             => $email,
            'remember'		    => $remember,
            'error'			    => $error,
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

    /** 
     * Devuelve la vista de registro de usuario,
     * crea un nuevo usuario y envia el mensaje de validación de email,
     * reenvía el mensaje de validación de email 
     * 
     * 
     */
    public function signUp(array $vars) {
        require_once 'System/Guid.php';
        global $application, $environment;
        $ru		    = $this->getParam('ru', '/');
        $identity   = self::getIdentity($vars);
        if ($identity !== false) {
            require_once 'Kansas/View/Result/Redirect.php';
            return Redirect::gotoUrl($ru);
        }
        require_once 'Psr/Http/Message/RequestMethodInterface.php';

        $email = '';
        $name = '';
        $surname = '';
        $error = 0;
        $signUpAction = 0; // solicitar email

        $request = $environment->getRequest();
        if($request->getMethod() == RequestMethodInterface::METHOD_POST) {
            require_once 'System/String/isNullOrEmpty.php';
            $application->getView()->setCaching(false);

            $email 		    = $this->getParam('email', $email);
            $signUpAction   = $this->getParam('signUpAction', $signUpAction);
            if(stringIsNullOrEmpty($email)) {
                $error += self::REQUIRE_USERNAME;
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                $error += self::FAILURE_USERNAME;
            }
            if($error == 0) { // Comprobar si existe el usuario
                $membershipProvider = $application->getProvider('auth\membership');
                $user = $membershipProvider->getUserByEmail($email);
                if($user === false) {
                    if($signUpAction == 0) { // Paso 1 - Obtener email
                        $signUpAction   = 1;
                    } else if($signUpAction == 1) { // Paso 2 - Obtener datos de usuario
                        $name 		        = $this->getParam('name', $name);
                        $surname	        = $this->getParam('surname', $surname);
                        $grecaptchatoken	= $this->getParam('grecaptchatoken');
                        $agree              = $this->getParam('agree');
                        if(stringIsNullOrEmpty($name)) {
                            $error += self::REQUIRE_NAME;
                        }
                        if(stringIsNullOrEmpty($surname)) {
                            $error += self::REQUIRE_SURNAME;
                        }
                        if($agree != 'on') {
                            $error += self::FAILURE_TOS;
                        }
                        if($error == 0) {
                            try {
                                require_once 'Kansas/Http/Request/post.php';
                                $jsonResult = httpRequestPost([
                                    'secret' => '6LfSYJAUAAAAAFxurjiYDsc7M0DJBp2ogOxk4n2M',
                                    'response' => $grecaptchatoken //,
                                    // 'remoteip'
                                ]);
                                $grecaptcha = json_decode($jsonResult, 1);
                                if(!$grecaptcha["success"])
                                    $error += self::FAILURE_CAPTCHA;
                            } catch(Exception $ex) {
                                $error += self::FAILURE_CAPTCHA;
                            }
                        }
                        if($error == 0) {
                            // crear usuario
                            $contactsProvider = $application->getProvider('contacts');
                            $user = $contactsProvider->createUser($email, $name, $surname);
                        }

                    }

                }
                if($user !== false) {
                    if(!isset($user['isLockedOut']) ||
                        $user['isLockedOut'] == null ||
                        $user['isLockedOut'] == false) {
                        require_once 'Kansas/Email/serverSend.php';
                        // crear token
                        $userId = ($user['id'] instanceof Guid)
                            ? $user['id']->getHex()
                            : $user['id'];
                        $token = $application->getPlugin('token');
                        $validationLink = $token->createLink([
                            'user'          => $userId,
                            'controller'    => 'membership',
                            'action'        => 'createPassword',
                            'ru'            => $ru
                        ]);
                        // enviar mensaje de validación de cuenta de email
                        if(stringIsNullOrEmpty($name)) { // Obtener el nombre del contacto
                            require_once 'Kansas/Plugin/Contacts.php';
                            $contactsProvider = $application->getProvider('contacts');
                            $user = array_merge($user, $contactsProvider->getUser($user['id']));
                            $formatedName = ContactsPlugin::getFormatedName($user);
                            $name = ContactsPlugin::getGivenName($user);
                        } else {
                            $formatedName = $name . ' ' . $surname;
                        }
                        $sended = emailSend(
                            mb_encode_mimeheader('Marmolería caybe', 'UTF-8') . ' <webapp@caybemarmoleria.es>',
                            mb_encode_mimeheader($formatedName, 'UTF-8') . ' <' . $email .'>',
                            'Validación de correo electronico',
                            'mail.validate-email.tpl',
                            null, [
                                'title' => $title,
                                'link'  => $validationLink,
                                'name'  => $name,
                                'exp'   => time() + 10 * 24 * 60 * 60
                        ]);
                        if(!$sended)
                            $error += self::FAILURE_SENDMAIL;
                        $signUpAction   = 2; 
                    } else {
                        require_once 'Kansas/View/Result/Redirect.php';
                        $router = $auth->getRouter();
                        return Redirect::gotoUrl(
                            $router->assemble([
                                'action'    => 'signin',
                                'ru'	    => $ru,
                                'email'     => $email,
                                'error'     => 1024]));
                    }
                }

            }

            // Si tiene datos de contacto
            // Si está comprobada la cuenta de email



        }

        $router = $application->getPlugin('Auth')->getRouter();
        $data = [
            'title'				=> 'Registro de usuario',
            'ru'				=> $ru,
            'authAction'	    => 'signUp',
            'signUpAction'      => $signUpAction,
            'email'             => $email,
            'name'              => $name,
            'surname'           => $surname,
            'error'				=> $error,
            'formAction'        => $router->assemble([
                'action'            => 'signup']),
        ];
        if($signUpAction == 1) {
            $data['name']       = $name;
        }
        return $this->createViewResult('page.membership-signup.tpl', $data);	

    }
    
}