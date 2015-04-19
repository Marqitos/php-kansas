<?php

class Kansas_Application_Module_Shop
	extends Kansas_Application_Module_Abstract {
		
	private $_shop;
	
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function providerCreated($provider, $providerName) {
		global $application;
		if($providerName = 'Photos')
			$provider->tagProviders[] = $application->getProvider('Shop_TagProvider');
	}
		
	public function route(Kansas_Request $request, $params) {
		$count = 0;
		if($cart = Kansas_Shop_Order::getCurrent($count, false)) {
			$params['cart'] = $cart;
			$params['order_count'] = $count;
		}
		return $params;
	}
	
	public function getShop() {
		if($this->_shop == null)
			$this->_shop = new $this->options->shopClass($this->options);
		return $this->_shop;
	}
		
}

