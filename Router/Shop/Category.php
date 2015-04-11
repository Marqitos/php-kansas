<?php

use Zend\Http\Request;

class Kansas_Router_Shop_Category
	extends Kansas_Router_Abstract {
		
	private $_categories;
		
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Request $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false)
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
