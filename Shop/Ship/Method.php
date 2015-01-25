<?php

class Kansas_Shop_Ship_Method
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Shop_Ship_Method_Interface {
		
	public function __construct($row) {
		if(!isset($row['Id']))
			$row['Id'] = new System_Guid(
				hex2bin(md5($row['Company'])) ^
				hex2bin(md5($row['Name']))
			);
		parent::__construct($row);			
	}
	/* Miembros de Kansas_Shop_Ship_Method_Interface */
	public function getCompany() {
		return $this->row['Company'];
	}
	public function getName() {
		return $this->row['Name'];
	}
	public function getDescription() {
		return $this->row['Description'];
	}
	public function getPrice() {
		return floatval($this->row['Price']);
	}
	public function getHtmlPrice() {
		return number_format($this->getPrice(), 2) . ' &euro;';
	}
}