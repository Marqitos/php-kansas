<?php

interface Kansas_Shop_Category_Interface
	extends Kansas_Core_GuidItem_Interface, Kansas_Core_Slug_Interface {
	public function getName();
	public function getDescription();
}