<?php

class Kansas_Application_Module_Facebook
	extends Kansas_Application_Module_Abstract
	implements Kansas_Auth_Service_Interface {
		
	private $_core;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		global $application;
		$users = $application->getModule('Users');
		$users->setAuthService('facebook', $this);
		$users->getRouter()->setRoute('fb-sign', [
			'Controller'	=>	'Facebook',
			'Action'			=>	'SignIn']);
		$users->getRouter()->setRoute('fb-register', [
			'Controller'	=>	'Facebook',
			'Action'			=>	'Register']);
		$this->_core = new Facebook_Core($this->options->toArray()); 
	}

	public function factory() {
		global $application;
		return new Kansas_Auth_Facebook(
			$application->getProvider('SignIn'),
			$application->getProvider('Auth_Facebook'),
			$this->_core
		);
	}
	
	public function getCore() {
		return $this->_core;
	}
	
	public function getAuthType() {
		return 'external';
	}
	
	public function getLoginUrl(array $params) {
		global $application;
		$params['redirect_url'] = $application->getRequest()->getScheme() . '://' . $application->getRequest()->getHttpHost() . '/' . $application->getModule('Users')->getRouter()->getBasePath() . '/fb-signin' . Kansas_Response::buildQueryString(['ru' => $params['ru']]);
		return $this->_core->getLoginUrl($params);
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