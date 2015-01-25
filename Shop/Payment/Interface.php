<?php

interface Kansas_Shop_Payment_Interface
	extends Kansas_Core_GuidItem_Interface {
	
	public function getUser();
	public function getOrder();
	
	public function getMethodId();
	public function getMethod();
	
	public function getAmount();
	public function getDate();
	
	public function getExternalID();
	
	public function getStatus();
}