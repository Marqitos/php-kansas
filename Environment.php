<?php

class Kansas_Environment {
	
	private $_status;
	private $t_inicio;
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

	protected function __construct($status, float $t_inicio = null) {
		$this->_status = $status;
		$this->t_inicio = isset($t_inicio) ? (float)$t_inicio : microtime(true);
	}
	
	public static function getInstance($status = null, $t_inicio = null) {
		if(self::$instance == null) {
			global $environment;
			if(empty($status))
				$status = getenv('APPLICATION_ENV');
			if(empty($status) && defined('APP_ENVIRONMENT'))
				$status = APP_ENVIRONMENT;
			if(empty($status))
				$status = self::PRODUCTION;
			$environment = self::$instance = new self($status, $t_inicio);
			
			if($status == self::DEVELOPMENT) {
				error_reporting(E_ALL);
				ini_set("display_errors", 1);
			} else
				error_reporting(E_ERROR);
		}
		return self::$instance;
	}
	
	public function getStatus() {
		return $this->_status;
	}
	
	public function getExecutionTime() {
		return microtime(true) - $this->t_inicio;
	}
	
}