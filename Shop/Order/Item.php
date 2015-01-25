<?php

class Kansas_Shop_Order_Item
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Shop_Order_Item_Interface {
		
	private $_orderId;
	private $_order;

	private $_productId;
	private $_product;
	
	protected function init() {
		parent::init();
		
		if($this->row['Product'] instanceof Kansas_Shop_Product_Interface) {
			$this->_product = $this->row['Product'];
			$this->_productId = $this->_product->getId();
		} else
			$this->_productId = new System_Guid($this->row['Product']);
			
	}

	public function getOrderId() {
		return $this->_orderId;
	}
	public function getOrder() {
		if($this->_order == null && $this->_orderId != System_Guid::getEmpty())
			$this->_order = Kansas_Application::getInstance()->getProvider('Shop')->gerOrderById($this->_orderId);
		return $this->_order;
	}
	
	public function getProductId() {
		return $this->_productId;
	}
	public function getProduct() {
		if($this->_product == null)
			$this->_product = Kansas_Application::getInstance()->getProvider('Shop')->getProductById($this->_productId);
		return $this->_product;
	}
	
	public function getName() {
		return empty($this->row['Name'])?
			$this->getProduct()->getName():
			$this->row['Name'];
	}

	public function getDescription() {
		return empty($this->row['Description'])?
			$this->getProduct()->getDescription():
			$this->row['Description'];
	}
	public function getUnitPrice() {
		return isset($this->row['UnitPrice'])?
			floatval($this->row['UnitPrice']):
			$this->getProduct()->getPrice();
	}
	public function getHtmlUnitPrice() {
		return number_format($this->getUnitPrice(), 2) . ' &euro;';
	}
	
	public function getQuantity() {
		return intval($this->row['Quantity']);
	}
	public function setQuantity($value) {
		$this->row['Quantity'] = $value;
	}
		
	public function getPrice() {
		return $this->getQuantity() * $this->getUnitPrice();
	}
	
	public function getHtmlPrice() {
		return number_format($this->getPrice(), 2) . ' &euro;';
	}
	
	public function fixPrice() {
		if(!isset($this->row['UnitPrice']))
			$this->row['UnitPrice'] = $this->getProduct()->getPrice();
	}
	public function unfixPrice() {
		unset($this->row['UnitPrice']);
	}
	
	public function fixProduct() {
		$this->fixPrice();
		// Nombre
		if(empty($this->row['Name']))
			$this->row['Name'] = $this->getProduct()->getName();
		// Descripción
		if(empty($this->row['Description']))
			$this->row['Description'] = $this->getProduct()->getDescription();
	}
}