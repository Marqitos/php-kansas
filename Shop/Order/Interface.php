<?php

interface Kansas_Shop_Order_Interface 
	extends Kansas_Core_GuidItem_Interface {
		
	public function getUser();
	public function getUserId();
	
	public function getItems();
	public function getBillingAddress();
	public function getBillingAddressId();
	public function getShippingAddress();
	public function getShippingAddressId();
	
	public function getPayment();
	public function getPaymentId();
	
	public function getStatus();
	
	public function getStatusText();
}
