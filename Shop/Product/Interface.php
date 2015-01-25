<?php

interface Kansas_Shop_Product_Interface
	extends Kansas_Core_GuidItem_Interface, Kansas_Core_Slug_Interface {
		
	public function getName();
	public function getDescription();
	
	public function getFamilyId();
	public function getFamily();
	
	public function getPrice();
	public function getFullSlug();
}