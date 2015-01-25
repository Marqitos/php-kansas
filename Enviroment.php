<?php

class Kansas_Enviroment {
	
	private $_status;
	private $_public_folder;
	private $_home_folder;
	protected static $instance;
	
	const CONSTRUCTION	= 'construction';
	const DEVELOPMENT 	= 'development';
	const PRODUCTION		= 'production';
	const TEST					= 'test';

	const SF_HOME			= 0x0001;
	const SF_PUBLIC 	= 0x0002;
	const SF_LIBS			= 0x0003;
	const SF_LAYOUTS 	= 0x0004;
	const SF_CACHE		= 0x0005;
	const SF_COMPILE	= 0x0006;
	const SF_FILES		= 0x0007;

	protected function __construct($status) {
		$this->_status = $status;
	}
	
	public static function getInstance($status = null) {
		if(self::$instance == null) {
			if(empty($status))
				$status = getenv('APPLICATION_ENV');
			if(empty($status) && defined('APP_ENVIROMENT'))
				$status = APP_ENVIROMENT;
			if(empty($status))
				$status = self::PRODUCTION;
			self::$instance = new self($status);
		}
		return self::$instance;
	}
	
	public function getStatus() {
		return $this->_status;
	}
	
//	public function getSpecialFolder($specialFolder) {
//		
//	}
	
}
