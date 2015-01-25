<?php

class Kansas_Router_Account
	extends Kansas_Router_Abstract {
	use Router_PartialPath;
		
	private $_pages;
	private $_defaultPage;
		
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		$this->_defaultPage = [
			'controller'	=> 'Account',
			'action'			=> 'index'
		];
		$this->_pages = [
			'signout' => [
				'controller'	=> 'Account',
				'action'			=> 'signOut'],
			'signin'	=> [
				'controller'	=> 'Account',
				'action'			=> 'signIn']
		];
	}
		
	public function match(Zend_Controller_Request_Abstract $request) {
		$path = $this->getPartialPath($this, $request);
		if($path === false)
			return false;

		if($path == '')
			$params = array_merge($this->getDefaultParams(), $this->_defaultPage);
		elseif(isset($this->_pages[$path]))
			$params = array_merge($this->getDefaultParams(), $this->_pages[$path]);
		else
			$params = false;
			
		if($params)
			$params['router'] = $this;
		
		return $params;
	}
	
	public function setRoute($page, $params) {
		if(empty($page))
			$this->_defaultPage = $params;
		else
			$this->_pages[$page] = $params;
	}


}