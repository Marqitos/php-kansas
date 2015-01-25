<?php

class Kansas_Shop_Product
	extends Kansas_Core_Guiditem_Model
	implements Kansas_Shop_Product_Interface {
		
	private $_familyId;
	private $_family;
	
	protected function init() {
		parent::init();
		
		if($this->row['Family'] instanceof Kansas_Shop_Family_Interface) {
			$this->_familyId = $this->row['Family']->getId();
			$this->_family = $this->row['Family'];
		} else
			$this->_familyId = new System_Guid($this->row['Family']);
		
	}
		
	public function getName() {
		return $this->row['Name'];
	}
	
	public function getDescription() {
		return $this->row['Description'];
	}
	public function getHtmlDescription() {
		return htmlentities($this->row['Description']);
	}
	
	public function getSlug() {
		return $this->row['slug'];
	}
	
	public function getPrice() {
		return floatval($this->row['Price']);
	}
	
	public function getHtmlPrice() {
		return number_format($this->getPrice(), 2) . ' &euro;';
	}

	public function getFullSlug() {
		$slug = $this->getSlug();
		foreach($this->getFamily()->getParentIterator() as $parent)
			$slug = $parent->getSlug() . '/' . $slug;
		return $slug;
	}

	public function getFamilyId() {
		return $this->_familyId;
	}
	
	public function getFamily() {
		if($this->_family == null)
			$this->_family = Kansas_Application::getInstance()->getProvider('shop')->getFamilyById($this->_familyId);
		return $this->_family;
	}
	
}
