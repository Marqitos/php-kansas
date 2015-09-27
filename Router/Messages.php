<?php

class Kansas_Router_Messages
	extends Kansas_Router_Abstract {
	use Router_PartialPath;

	public function __construct(array $options) {
		parent::__construct();
    $this->setOptions($options);
	}
		
	public function match() {
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
    if(Kansas_String::startWith($this->getBasePath(), $path))
      $path = substr($this->getBasePath(), strlen($this->getBasePath()));
    else
			return false;
      
		switch($path) {
			case '':
				$params = [
					'controller'	=> 'messages',
					'action'			=> 'index'
        ];
				break;
			case 'enviar':
				$params = [
					'controller'	=> 'messages',
					'action'			=> 'send'
        ];
				break;
			case 'captcha':
				$params = [
					'controller'	=> 'messages',
					'action'			=> 'captcha'
        ];
				break;
			case 'send-approve':
				$params = [
					'controller'	=> 'messages',
					'action'			=> 'approve'
        ];
				break;
			case 'reply':
				$params = [
					'controller'	=> 'messages',
					'action'			=> 'reply'
        ];
				break;
			default:
				global $application;
				if($application->hasModule('users') && $user = $application->getModule('users')->getIdentity() && $id = System_Guid::tryParse($path)) {
					$thread = $application->getProvider('messages')->getThreadById($id, $user->getId());
					if($thread != null) {
						$params = [
							'controller'	=> 'messages',
							'action'			=> 'show',
							'thread'			=> $thread
						];
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
	
}