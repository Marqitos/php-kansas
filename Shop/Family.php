<?php

class Kansas_Shop_Family
	extends Kansas_Core_Guiditem_Model
	implements Kansas_Shop_Family_Interface {
		
	private $_parentId;
	private $_parent;
	private $_products;
	private $_children;
	
	protected function init() {
		parent::init();
		
		if(isset($this->row['Parent']) && $this->row['Parent'] != null)
			$this->_parentId = new System_Guid($this->row['Parent']);
	}
		
	public function getName() {
		return $this->row['Name'];
	}
	
	public function getDescription() {
		return $this->row['Description'];
	}
	
	public function getSlug() {
		return $this->row['slug'];
	}
	
	public function getParentId() {
		return $this->_parentId;
	}
	
	public function getParent() {
		global $application;
		if($this->_parent == null && $this->_parentId != null)
			$this->_parent = $application->getProvider('shop')->getFamilyById($this->getId());
		return $this->_parent;
	}
	
	public function getParentIterator() {
		return new Kansas_Core_Hierarchy_ParentIterator($this);
	}
	
	public function getChildren() {
		return array();
	}

	
	public function getProducts() {
		global $application;
		if($this->_products == null)
			$this->_products = $application->getProvider('shop')->getProductsByFamily($this);
		return $this->_products;
	}
	
	public function getProductCount() {
		return $this->row['ProductCount'];
	}
		
}
