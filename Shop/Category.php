<?php

class Kansas_Shop_Category
	extends Kansas_Core_Guiditem_Model
	implements Kansas_Shop_Category_Interface {
		
		
	public function getName() {
		return $this->row['Name'];
	}
	
	public function getDescription() {
		return $this->row['Description'];
	}
	
	public function getSlug() {
		return $this->row['slug'];
	}
		
}
