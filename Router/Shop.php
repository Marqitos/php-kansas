<?php

class Kansas_Router_Shop
	extends Kansas_Router_Abstract {
		
	const SHOW_FAMILY 	= 0x01;
	const SHOW_CATEGORY	= 0x02;
	const SHOW_NEW			= 0x04;
	const SHOW_POPULAR	= 0x10;
	const SHOW_OFFERS		= 0x11;
	
	const SHOW_ALL			= 0x77;
	
	private $_families;
		
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Zend_Controller_Request_Abstract $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false)
			return false;
		
		$params = false;
			
		switch($path) {
			case '':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'index'
					));
				break;
			case 'order/add':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'buyProduct'
					));
				break;
			case 'order/set':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'setProduct'
					));
				break;
			case 'order/express-checkout':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'expressCheckout'
					));
				break;
			case 'order/payment/paypal':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'paymentPaypal'
					));
				break;
			case 'order/payment/void':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'paymentVoid'
					));
				break;
			case 'order/payment/confirm':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'paymentConfirm'
					));
				break;
			case 'order/checkout':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'checkout'
					));
				break;
			case 'order/checkout/ship-address':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'shipAddress',
					'generic'			=> false
					));
				break;
			case 'order/checkout/review':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'reviewCheckout'
					));
				break;
			case 'order/update':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'orderUpdate'
					));
				break;
			case 'order/ship-address':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'shipAddress'
					));
				break;
			case 'order/set-ship-address':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'setShipAddress'
					));
				break;
		}
		
		if($params == false) {
			$currentPath = $path;
			$current = false;
			do {
				$families = $current instanceof Kansas_Shop_Family_Interface?
					$current->getChildren():
					$this->getFamilies();
				$products = $current instanceof Kansas_Shop_Family_Interface?
					$current->getProducts():
					array();
				$current	= false;
				foreach($families as $family) {
					if($family->getSlug() == $currentPath) {
						$params = array_merge($this->getFamilyParams(), array(
							'controller'	=> 'Shop',
							'action'			=> 'family',
							'family'			=> $family
							));
						break;
					} elseif(Kansas_String::startWith($path, $family->getSlug())) {
						$currentPath = substr($currentPath, strlen($family->getSlug()) + 1);
						$current = $family;
						break;
					}
				}
				foreach($products as $product) {
					if($product->getSlug() == $currentPath) {
						$params = array_merge($this->getProductParams(), array(
							'controller'	=> 'Shop',
							'action'			=> 'product',
							'family'			=> $product->getFamily(),
							'product'			=> $product
							));
						break;
					}
				}
			} while($current != false);
		}
		
		if($params != false) {
			$params['router'] = $this;
			return $params;
		}
		
		// routers
		$routers = array(
			'categorias'	=> new Kansas_Router_Shop_Category(new Zend_Config(array_merge(array(
				'basePath'	=> $this->getBasePath() . '/categorias'
				), $this->getDefaultParams()))),
			'pedidos'			=> new Kansas_Router_Shop_Order(new Zend_Config(array_merge(array(
				'basePath'	=> $this->getBasePath() . '/order'
				), $this->getDefaultParams())))
		);
		foreach($routers as $router)
			if($params = $router->match($request))
				break;
		
		return $params;
	}
	
	
  public function assemble($data = array(), $reset = false, $encode = false) {
		$path = parent::assemble($data, $reset, $encode);
		$queryData = array();
		if(isset($data['show']))
			$queryData['show'] = $data['show'];
			
	 	if(isset($data['action'])) {
			switch($data['action']) {
				case 'viewCategories':
					$result = array();
					foreach($data['categories'] as $category)
						$result[$category->getId()->__toString()] = $path . '/categorias/' . $category->getSlug();
					return $result;
				case 'viewCategory':
					$path .= '/categorias/' . $data['category']->getSlug();
					break;
				case 'viewFamilies':
					$result = array();
					foreach($data['families'] as $family)
						$result[$family->getId()->__toString()] = $path . '/' . $family->getSlug();
					return $result;
				case 'viewFamily':
					$path .= '/' . $data['family']->getSlug();
					break;
				case 'productThumbnails':
					$result = array();
					foreach($data['products'] as $product)
						$result[$product->getId()->__toString()] = '/img/' . $product->getFullSlug() . '/th_128.jpg';
					return $result;
				case 'viewProduct':
					$path .= '/' . $data['product']->getFullSlug();
					break;
				case 'viewProducts':
					$result = array();
					foreach($data['products'] as $product)
						$result[$product->getId()->__toString()] = $path . '/' . $product->getFullSlug();
					return $result;
			}
		}
			
		return $path . Kansas_Response::buildQueryString($queryData);
	}
	
	protected function getFamilies() {
		if($this->_families == null)
			$this->_families = Kansas_Application::getInstance()->getProvider('shop')->getFamilies();
		return $this->_families;
	}
	
	protected function getFamilyParams() {
		return array_merge(parent::getDefaultParams(), $this->options->family->toArray());
	}

	protected function getProductParams() {
		return array_merge(parent::getDefaultParams(), $this->options->product->toArray());
	}
	
	public function getDefaultOptions() {
		return parent::getDefaultOptions()->merge(new Zend_Config(array(
			'family'		=> array(
				'body_class'	=> 'family'),
			'product'			=> array(
				'template'		=> 'page.shop-product.tpl')
		)));
	}
	
}
