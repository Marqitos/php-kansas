<?php
require_once 'System/Version.php';

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
		$this->_version = new System_Version('0.3');
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
    if(!is_array($theme)) {
			require_once 'System/ArgumentOutOfRangeException.php';
      throw new System_ArgumentOutOfRangeException();
		}
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
    elseif(is_array($message))
			$message = $message['message'];
      			
    if(self::$instance->_status != self::DEVELOPMENT && $level != E_USER_WARNING)  
      return;
      
    $levelText = ($level == E_USER_ERROR)    ? '<b>ERROR</b> '
               : (($level == E_USER_WARNING) ? '<b>WARNING</b> '
               : (($level == E_USER_NOTICE)  ? '<b>NOTICE</b> '
                                             : ''));
    
    echo $levelText . $time . ' [' . $level . '] ' . $message . "<br />\n";
	}
  
/**
	* Determine system TMP directory and detect if we have read access
	*
	* inspired from Zend_File_Transfer_Adapter_Abstract
	*
	* @return string
	* @throws Zend_Cache_Exception if unable to determine directory
	*/
	public static function getTmpDir() {
		foreach ([$_ENV, $_SERVER] as $tab) {
			foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
				if (isset($tab[$key])) {
					if (($key == 'windir') or ($key == 'SystemRoot'))
						$dir = realpath($tab[$key] . '\\temp');
					else
						$dir = realpath($tab[$key]);
					if (self::_isGoodTmpDir($dir))
						return $dir;
				}
			}
		}
		$upload = ini_get('upload_tmp_dir');
		if ($upload) {
			$dir = realpath($upload);
			if (self::_isGoodTmpDir($dir))
				return $dir;
		}
		if (function_exists('sys_get_temp_dir')) {
			$dir = sys_get_temp_dir();
			if ($this->_isGoodTmpDir($dir))
				return $dir;
		}
		// Attemp to detect by creating a temporary file
		$tempFile = tempnam(md5(uniqid(rand(), TRUE)), '');
		if ($tempFile) {
			$dir = realpath(dirname($tempFile));
			unlink($tempFile);
			if (self::_isGoodTmpDir($dir))
				return $dir;
		}
		if (self::_isGoodTmpDir('/tmp'))
			return '/tmp';
		if (self::_isGoodTmpDir('\\temp'))
			return '\\temp';
		require_once 'System/IO/IOException.php';
		throw new System_IO_Exception('Could not determine temp directory, please specify a temp directory manually');
	}

/**
	* Verify if the given temporary directory is readable and writable
	*
	* @param $dir temporary directory
	* @return boolean true if the directory is ok
	*/
	protected static function _isGoodTmpDir($dir) {
		return is_readable($dir) && is_writable($dir);
	}    

	public function getVersion() {
		return $this->_version;
	}	
	
}