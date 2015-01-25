<?php

interface Kansas_Shop_Family_Interface 
	extends Kansas_Core_GuidItem_Interface, Kansas_Core_Slug_Interface, Kansas_Core_Hierarchy_Interface {
	public function getName();
	public function getDescription();

	public function getProducts();
}