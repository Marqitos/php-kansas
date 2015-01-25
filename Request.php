<?php

class Kansas_Request {
	
	public static function getParam($var, $defaultValue = null) {
		return isset($_REQUEST[$var])?
			$_REQUEST[$var]:
			$defaultValue;
	}
	
	public static function getReturnUrl($var = 'ru', $defaultValue = null) {
		return isset($_REQUEST[$var]) 					?	$_REQUEST[$var]:
			     !empty($defaultValue)						?	$defaultValue:
				   isset($_SERVER['HTTP_REFERER'])	?	$_SERVER['HTTP_REFERER']:
																							'/';
	}
	
	public static function fillModel(array &$model) {
		$result = array();
		foreach($model as $key => $value)
			$model[$key] = self::getParam($key, $value);
	}

}