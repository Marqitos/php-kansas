<?php

class Kansas_Environment {
	
	private $_status;
	private $_request;
	private $t_inicio;
	private $_version;
  private $_theme = ['shared'];
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
		$this->_version = new System_Version('0.2');
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
			
		}
		return self::$instance;
	}
	
	public function getStatus() {
		return $this->_status;
	}
	
	public function getExecutionTime() {
		return microtime(true) - $this->t_inicio;
	}
	
	public function getRequest() {
		if($this->_request == null)
			$this->_request = new Kansas_Request();
		return $this->_request;
	}
  
  public function setTheme($theme) {
    if(is_string($theme))
      $theme = explode(':', $theme);
    if(!is_array($theme))
      throw new System_ArgumentOutOfRange();
    if($theme[0] == '') {
      unset($theme[0]);
      $this->_theme = array_merge($this->_theme, $theme);        
    } else
      $this->_theme = $theme;
  }
  
  public function getViewPaths() {
    $func = function($theme) {
      return realpath(LAYOUTS_PATH . $theme . '/');
    };
    return array_reverse(array_map($func, $this->_theme));
  }
  
  public function getThemePaths() {
    $func = function($theme) {
      return realpath(THEMES_PATH . $theme . '/');
    };
    return array_reverse(array_map($func, $this->_theme));
  }
  
	public static function log($level, $message) {
		$time = self::$instance->getExecutionTime();
		
		if($message instanceof Exception)
			$message = $message->getMessage();
			
    if(self::$instance->_status != self::DEVELOPMENT && $level != E_USER_WARNING)  
      return;
      
    $levelText = ($level == E_USER_ERROR)    ? '<b>ERROR</b> '
               : (($level == E_USER_WARNING) ? '<b>WARNING</b> '
               : (($level == E_USER_NOTICE)  ? '<b>NOTICE</b> '
                                             : ''));
    
    echo $levelText . $time . ' [' . $level . '] ' . $message . "<br />\n";
	}
	
	public function getVersion() {
		return $this->_version;
	}	
	
}