<?php

interface Kansas_Places_Region_Interface
	extends Kansas_Core_GuidItem_Interface {
	
	public function getKey();
	public function getName();
	public function getDescription();
	
	// subregions
	public function getRegionType();
	// type
	
	public function matchAddress(Kansas_Places_Address_Interface $address);
	
}