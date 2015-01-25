<?php

interface Kansas_Application_Module_Interface {
	public function setOptions(Zend_Config $options);
	public function getOptions();
	public function getVersion();
}