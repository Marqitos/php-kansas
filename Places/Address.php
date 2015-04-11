<?php

class Kansas_Places_Address
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Places_Address_Interface {
		
	private $_country;
	private $_user;
	private $_userId;

	protected function init() {
		parent::init();
		if($this->row['Country'] instanceof Kansas_Places_Country)
			$this->setCountry($this->row['Country']);
			
		if($this->row['User'] instanceof Kansas_User_Interface)
			$this->setUser($this->row['User']);
		else
			$this->_userId = new System_Guid($this->row['User']);
	}
	
	/* Miembros de Kansas_Places_Address_Interface */
	public function getUser() {
		global $application;
		if($this->_user == null)
			$this->_user = $application->getProvider('users')->getById($this->_userId);
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
	
	public function getAlias() {
		return $this->row['Alias'];
	}
	
	public function getName() {
		return $this->row['Name'];
	}
	public function getStreet() {
		return $this->row['Street'];
	}
	public function getStreet2() {
		return $this->row['Street2'];
	}
	public function getCity() {
		return $this->row['City'];
	}
	public function getPostalCode() {
		return $this->row['PostalCode'];
	}
	public function getState() {
		return $this->row['State'];
	}
	public function getCountry() {
		global $application;
		if($this->_country == null)
			$this->_country = $application->getProvider('Places')->getCountryByCode($this->row['Country']);
		return $this->_country;
	}
	public function getCountryCode() {
		return $this->row['Country'];
	}
	public function setCountry(Kansas_Places_Country $country) {
		$this->_country				= $country;
		$this->row['Country']	= $country->getKey();
	}
	
	public function getLabel() {
		return isset($this->row['Label'])?
			$this->row['Label']:
			$this->buildLabel();
	}
	
	public function getHtmlLabel() {
		return nl2br(htmlentities($this->getLabel()));
	}
	
	public function buildLabel() {
		$result  = $this->row['Name'] . 	"\r\n";
		$result	.= $this->row['Street'] .	"\r\n";
		if(!empty($this->row['Street2']))
			$result	.= $this->row['Street2'] . "\r\n";
		$result	.= $this->row['PostalCode'] .	' - ' . $this->row['City'] . "\r\n";
		$result	.= $this->row['State'] .	' - ' . $this->getCountry()->getName();
		return $result;
	}
	
	public function save() {
		global $application;
		$this->row = $application->getProvider('places')->saveAddress($this->row);
		$this->init();
	}
	
}

