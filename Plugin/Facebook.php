<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;

class Kansas_Module_Facebook
	implements Kansas_Auth_Service_Interface {
		
  protected $options;

	public function __construct(array $options) {
			global $application;
      $this->options = array_replace_recursive([
			'path'		  => [
				'fb-signin'    => 'fb-signin', // iniciar sesión
				'fb-register'	 => 'fb-register'], // registar usuario
			'action'  => [
				'fb-signin'	=> [
					'controller'	=> 'Facebook',
					'action'			=> 'signIn'],
				'fb-register'	=> [
					'controller'	=> 'Facebook',
					'action'			=> 'Register']]
			], $options);
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
		$application->registerCreateViewCallbacks([$this, "appCreateView"]);
		FacebookSession::setDefaultApplication($options['appId'], $options['appSecret']);		
	}

	public function appPreInit() { // añadir proveedor de inicio de sesión - comprobar inicio de sesión
		global $application;
    if($this->options['signIn']) {
      $usersModule = $application->getPlugin('Auth');
      $usersModule->setAuthService('facebook', $this);
      $usersModule->getRouter()->setOptions($this->options);
    }
	}
  
	public function appCreateView(Kansas_View_Interface $view) { // registrar plugins en la vista
		$engine = $view->getEngine();
    $engine->registerPlugin('function', 'facebooklike', [$this, "viewFacebookLike"]);
	}	
  
	public static function factory(FacebookSession $session = null) {
		return new Kansas_Auth_Facebook();
	}
	
	public function getAuthType() {
		return 'external';
	}
	
	public function getLoginUrl(array $params) {
		$result = [
			'link' => Kansas_Auth_Facebook::getRedirectLoginHelper($params['ru'])
			->getLoginUrl(['public_profile', 'email']),
			'text' => 'Conectar con Facebook'
		];
		if(isset($params['fb-error']))
			$result['error'] = $params['fb-error'];
		
		return $result;
	}
	
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}
  
  public function viewFacebookLike($params, $template) {
    global $environment;
    $ru = $environment->getRequest()->getUri()->toString();
    $href = false;
    if(isset($params['href'])) // Utilizamos el parametro href
      $href = $params['href'];
    else { // Utilizamos el parametro o variable og (OpenGraph)
      @$og = $params['og'] | $template->getTemplateVars('og');
      if($og) $href = $og['url'];
    }
    if(!$href) // Utilizamos la url actual
      $href = $ru;
      
    // Obtener likes
    $session = $this->getUserSession();
    $objectId = $this->getObjectId($href, $session);
    if(!$session) $session = FacebookSession::newAppSession();    
    $request = new FacebookRequest($session, 'GET', '/' . $objectId . '/likes?summary=true');
    $likes = $request->execute()->getGraphObject()->getProperty('summary')->asArray();

    var_dump($href, $likes);
    
    $facebookTemplate = $template->createTemplate(isset($params['template']) ? $params['template'] : 'part.fb-like.tpl', $template);
    $facebookTemplate->assign('layout', 'standard'); // standard, box_count, button_count, button
    $facebookTemplate->assign($params); 
    $facebookTemplate->assign($likes);
    $facebookTemplate->assign('href', ((isset($likes['has_liked']) && !$likes['has_liked']) ? '/fb-unlike/': '/fb-like/') . $objectId . '?' . http_build_query(['ru' => $ru]));
    return $facebookTemplate->fetch();
  }
  
  public function getObjectId($url, &$session) {
    global $application;
    $cacheId = 'facebookId-' . $url;
    $cache = false;
    if($cache = $application->hasPlugin('cache') && !$session && $cache->test($cacheId)) {
      return $cache->load($cacheId);
    } else {
      if(!$session)
         $session = FacebookSession::newAppSession();
      $request = new FacebookRequest($session, 'GET', '/' . $url);
      $response = $request->execute();
      try {
        $id = $response->getGraphObject()->getProperty('og_object')->getProperty('id');
        if($cache) $cache->save($id, $cacheId);
        return $id;
			} catch(Exception $e) {
        global $environment;
        $environment->log(E_USER_NOTICE, $e);
        return false;  
      }
    }
  }
  
  public function getUserSession() {
    global $application;
    $auth = $application->getPlugin('Auth');
    if(!$auth->hasIdentity())
      return false;
    // Obtener token desde base de datos, o sesión php
    
    
  }
  
	//case 'fb/signin':
//		$code = isset($_REQUEST['code'])?
//			$_REQUEST['code']:
//			null;
//		$error = isset($_REQUEST['error'])?
//			$_REQUEST['error']:
//			null;
//		if(!empty($code))
//			$params = array_merge($this->getDefaultParams(),
//				array(
//				'controller'	=> 'Account',
//				'action'			=> 'fbSignIn',
//				'code'				=> $code
//				));
//		else
//			$params = array_merge($this->getDefaultParams(),
//				array(
//				'controller'	=> 'Account',
//				'action'			=> 'fbError',
//				'error'				=> $error
//				));
//		break;
//	case 'fb/register':
//		$params = array_merge($this->getDefaultParams(),
//			array(
//			'controller'	=> 'Account',
//			'action'			=> 'fbRegister'
//			));
//		break;
	
}