<?php

abstract class Kansas_Shop_Payment_Abstract 
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Shop_Payment_Interface {
		
	private $_order;
	private $_orderId;
	private $_user;
	private $_userId;

	protected function init() {
		parent::init();
		if($this->row['Order'] instanceof Kansas_Shop_Order_Interface)
			$this->setOrder($this->row['Order']);
		else
			$this->_orderId = new System_Guid($this->row['Order']);
			
		if($this->row['User'] instanceof Kansas_User_Interface)
			$this->setUser($this->row['User']);
		else
			$this->_userId = new System_Guid($this->row['User']);
	}

	public function getOrder() {
		if($this->_order == null)
			$this->_order = Kansas_Application::getInstance()->getProvider('shop')->getOrderById($this->_orderId);
		return $this->_order;
	}
	public function getOrderId() {
		return $this->_orderId;
	}
	protected function setOrder(Kansas_Shop_Order_Interface $order) {
		$this->_order				= $order;
		$this->_orderId			= $order->getId();
		$this->row['Order']	= $order->getId()->getHex();
	}
	
	
	public function getUser() {
		if($this->_user == null)
			$this->_user = Kansas_Application::getInstance()->getProvider('users')->getById($this->_userId);
		return $this->_user;
	}
	
	public function getUserId() {
		return $this->_userId;
	}
	
	protected function setUser(Kansas_User_Interface $user) {
		$this->_user 				= $user;
		$this->_userId			= $user->getId();
		$this->row['User']	= $user->getId()->getHex();
	}
	
	public function getAmount() {
		return floatval($this->row['Amount']);	
	}
	public function getDate() {
		return $this->row['Date'];	
	}
	
	public function getExternalID() {
		return $this->row['ExternalId'];	
	}
	
	public function getStatus() {
		return $this->row['Status'];	
	}
	
	protected function setStatus($status, $time = null) {
		$this->row['Status']	= $status;
		$this->getOrder()->addStep(
			'payment-' . $status,
			$time,
			$this->getId()->getHex()
		);
	}

	public function getToken() {
		return $this->row['Token'];	
	}
	public function save() {
		return Kansas_Application::getInstance()->getProvider('Shop')->savePayment($this->row);
	}
	
	public function void() {
		if($this->getStatus() == 'confirmed')
			throw new System_NotSupportedException('No se puede cancelar un pago ya confirmado');
			
		$this->setStatus('void');
	}
	
	public function renew() {
		return $this->getOrder()->createPayment($this->getMethod());
	}
		
}