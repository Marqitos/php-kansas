<?php

class Kansas_Router_Messages
	extends Kansas_Router_Abstract {
	use Router_PartialPath;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Zend_Controller_Request_Abstract $request) {
		$path = $this->getPartialPath($this, $request);
		$params = false;

  	if($path === false)
			return false;
		switch($path) {
			case '':
				$params = array(
					'controller'	=> 'messages',
					'action'			=> 'index'
				);
				break;
			case 'enviar':
				$params = array(
					'controller'	=> 'messages',
					'action'			=> 'send'
				);
				break;
			case 'captcha':
				$params = array(
					'controller'	=> 'messages',
					'action'			=> 'captcha'
				);
				break;
			case 'send-approve':
				$params = array(
					'controller'	=> 'messages',
					'action'			=> 'approve'
				);
				break;
			case 'reply':
				$params = array(
					'controller'	=> 'messages',
					'action'			=> 'reply'
				);
				break;
			default:
				$application = Kansas_Application::getInstance();
				if($application->hasModule('users') && $user = $application->getModule('users')->getIdentity()) {
					try {
						$id = new System_Guid($path);
						$thread = $application->getProvider('messages')->getThreadById($id, $user->getId());
						if($thread != null) {
							$params = [
								'controller'	=> 'messages',
								'action'			=> 'show',
								'thread'			=> $thread
							];
						}
					} catch(Exception $ex) {
						break;
					}
				}
				break;
		}
		
		if($params)
			$params['router']	= $this;
		return $params;
	}
	
  public function assemble($data = [], $reset = false, $encode = false) {
		$basepath = parent::assemble($data, $reset, $encode);
		switch($data['action']) {
			
			
		}
	}
	
	public function getTitle($partial = '') {
		return Kansas_Application::getInstance()->getRouter()->getTitle($partial);
	}
		
}