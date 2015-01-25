<?php

interface Kansas_Shop_Ship_Method_Interface
	extends Kansas_Core_GuidItem_Interface {
		
	public function getCompany();
	public function getName();
	public function getDescription();
	public function getPrice();
	
}