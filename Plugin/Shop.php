<?php

class Kansas_Module_Shop {
		
	private $_shop;
  protected $options;
	
	public function __construct(array $options) {
    $this->options = $options;
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
			$this->_shop = new $this->options['shopClass']($this->options);
		return $this->_shop;
	}
		
}

