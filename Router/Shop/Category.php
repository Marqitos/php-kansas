<?php

class Kansas_Router_Shop_Category
	extends Kansas_Router_Abstract {
		
	private $_categories;
		
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
				return array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'categories',
					'router'			=> $this
					));
		}
		
		foreach($this->getCategories() as $slug => $category) {
			if($path == $slug)
				return array_merge($this->getDefaultParams(),
				array(
					'controller'	=> 'Shop',
					'action'			=> 'category',
					'category'		=> $category,
					'router'			=> $this
				));
		}
		
		return false;
	}
	
	public function getCategories() {
		global $application;
		if($this->_categories == null)
			$this->_categories = $application->getProvider('shop')->getCategories();
		return $this->_categories;
	}
	
	
}
