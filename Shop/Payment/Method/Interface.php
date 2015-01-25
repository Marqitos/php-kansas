<?php

interface Kansas_Shop_Payment_Method_Interface
	extends Kansas_Core_GuidItem_Interface {

	public function getName();		
		
	public function getType(); // online, online-express, shipping
	
	public function getExtraCost(); //float
	
}