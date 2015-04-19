<?php

class Kansas_Router_Shop_Order
	extends Kansas_Router_Abstract {
		
	private $_orders;
		
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Kansas_Request $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false)
			return false;
			
		$params = false;
			
		switch($path) {
			case '':
				$count = 0;
				$order = Kansas_Shop_Order::getCurrent($count, true);
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'order',
					'order'				=>  $order
					));
				break;
			case 'list':
				$params = array_merge($this->getDefaultParams(),
					array(
					'controller'	=> 'Shop',
					'action'			=> 'orderList',
					'orders'			=> $this->getOrders()
					));
				break;
		}
		
		foreach($this->getOrders() as $id => $order) {
			$pathId = new System_Guid($path);
			if($pathId->getHex() == $id)
				$params = array_merge($this->getDefaultParams(),
				array(
					'controller'	=> 'Shop',
					'action'			=> 'order',
					'order'				=> $order
				));
		}
		
		
		if($params != false)
			$params['router'] = $this;
		
		return $params;
	}
	
	
	/**
	 * Obtiene las compras realizadas por el usuario actual
	 *
	 */
	public function getOrders() {
		if($this->_orders == null) {
			global $application;
			$auth = Zend_Auth::getInstance();
			$this->_orders = $auth->hasIdentity()?
				$application->getProvider('shop')->getOrdersByUser($auth->getIdentity()):
				new Kansas_Core_GuidItem_Collection();
		}
		return $this->_orders;
	}
	
	
}
