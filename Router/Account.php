<?php

class Kansas_Router_Account
	extends Kansas_Router_Abstract {
		
	public function __construct(array $options) {
    parent::__construct();
    $this->setOptions($options);
	}
		
	public function match() {
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
		foreach($this->options['path'] as $key => $value) {
			if($path == ((substr($value, 0, 1) == '/') ? substr($value, 1) : $this->options['basePath'] . '/' . $value) &&
				isset($this->options['action'][$key])) {
				$params = array_merge($this->options['params'], $this->options['action'][$key], ['router' => $this]);
				break;
			}
		}
		return $params;
	}
	
  public function assemble($data = [], $reset = false, $encode = false) {
		$basePath = parent::assemble($data, $reset, $encode);
	 	if(isset($data['action']) && isset($this->options['path'][$data['action']])) {
			$path = (substr($this->options['path'][$data['action']], 0, 1) == '/') ?
							 $this->options['path'][$data['action']] :
							 $basePath . '/' . $this->options['path'][$data['action']];
			 unset($data['action'], $data['basepath']);
			return $path . '?' . http_build_query($data);
		}		
		return false;
	}
	
	protected function getDefaultOptions() {
		return [
			'basePath'	=> 'account',
			'params'		=> [],
			'path'		  => [
				'signout'   => 'signout',
				'signin'    => 'signin',
				'account'		=> ''],
			'action'  => [
				'account' => [
					'controller'	=> 'Account',
					'action'			=> 'index'],
				'signout' => [
					'controller'	=> 'Account',
					'action'			=> 'signOut'],
				'signin'	=> [
					'controller'	=> 'Account',
					'action'			=> 'signIn']
			]
		];
	}
  
  public function addActions(array $actions = null) {
    
  }
}