<?php

class Kansas_Helpers {
	
	private static $_helpersName = [];
	private static $_helpersType = [];
	
	public static function getHelper($name) {
		return 	isset(self::$_helpersName[$name]) ? self::$_helpersName[$name]:
						isset(self::$_helpersType[$name])	?	self::$_helpersType[$name]:
																								self::createHelper($name);
	}
	
	public static function createHelper($name) {
		global $application;
		$helperClass = $application->getLoader('helper')->load($name, false);
		$helper = $helperClass != false ?	new $helperClass
																		:	false;
		if($helper != null) {
			self::$_helpersName[$helper->getName()] = $helper;
			self::$_helpersType[$helper->getType()] = $helper;
		}
		return $helper;
	}

}