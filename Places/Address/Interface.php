<?php

interface Kansas_Places_Address_Interface {
	
	public function getUser();
	
	public function getAlias();
	
	public function getName();
	public function getStreet();
	public function getStreet2();
	public function getCity();
	public function getPostalCode();
	public function getState();
	public function getCountry();
	
	public function getLabel();
}