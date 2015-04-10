<?php

require_once 'Smarty/Smarty.php';
require_once 'Zend/View/Interface.php';

class Kansas_View_Smarty
  implements Zend_View_Interface {
		
  protected $_smarty;
	private $_config;
	private $_cacheId = null;
	private $_scriptPath;
  
  public function __construct(array $config) {
		if(!isset($config['compileDir']))
			throw new Exception('compileDir is not set for '.get_class($this));
		else
			$config['compileDir'] = realpath($config['compileDir']);
			
		foreach(['configDir', 'pluginDir', 'cacheDir'] as $config_dir) {
			if(isset($config[$config_dir]))
				$config[$config_dir] = realpath($config[$config_dir]);
		}
			
		if(isset($config['scriptPath']))
			$this->setScriptPath($config['scriptPath']);
			
		$this->_config = $config;
  }
  
  public function getEngine() {
		if($this->_smarty == null) {
			$this->_smarty = new Smarty();
		
			$this->_smarty->setCompileDir($this->_config['compileDir']);
		
			if(isset($this->_config['configDir']))
				$this->_smarty->config_dir = $this->_config['configDir'];
	
			if(isset($this->_config['pluginDir']))
				$this->_smarty->plugins_dir[] = $this->_config['pluginDir'];
				
			if(isset($this->_config['cacheDir']))
				$this->_smarty->cache_dir = $this->_config['cacheDir'];
	
			if(isset($this->_config['caching']))
				$this->_smarty->caching = (bool)$this->_config['caching'];
	
			if(isset($this->_config['debugging']))
				$this->_smarty->debugging = (bool)$this->_config['debugging'];
			
		}
		
    return $this->_smarty;
  }
	
	public function setScriptPath($path) {
		if(is_array($path)) {
			$this->_scriptPath = [];
			foreach($path as $key => $value)
				$this->_scriptPath[$key] = realpath($value);
		}	elseif(is_string($path))
			$this->_scriptPath = realpath($path);
		else
			throw new System_ArgumentOutOfRangeException();
	}
	
	public function getScriptPaths() {
		return $this->_scriptPath;
	}
  
	public function setBasePath($path, $classPrefix = 'Zend_View') {}

  public function addBasePath($path, $classPrefix = 'Zend_View') {}


  public function __set($key,$val) {
    $this->getEngine()->assign($key,$val);
  }
  
  public function __isset($key) {
    $var = $this->getEngine()->get_template_vars($key);
    if($var)
      return true;
    
    return false;
  }
  
  public function __unset($key) {
    $this->getEngine()->clear_assign($key);
  }
  
  /**
   * (non-PHPdoc)
   * @see Zend_View_Abstract::assign()
   */
  public function assign($spec, $value = null) {
    if($value === null)
      $this->getEngine()->assign($spec);
    else
      $this->getEngine()->assign($spec, $value);
  }
  
  /**
   * (non-PHPdoc)
   * @see Zend_View_Abstract::clearVars()
   */
  public function clearVars() {
    $this->getEngine()->clear_all_assign();
  }
  
  public function render($file) {
    //$this->getEngine()->assignByRef('this', $this);
    
    $file = substr($file, strrpos($file, '/'));
    $this->getEngine()->setTemplateDir($this->getScriptPaths());
    echo $this->getEngine()->fetch($file, $this->_cacheId);
  }

  public function isCached($template, $cacheId = null) {
    return $this->getEngine()->isCached($template, $cacheId == null? $this->_cacheId: null);
  }

	public function getCaching() {
		return $this->getEngine()->caching;
	}
  public function setCaching($caching) {
    $this->getEngine()->caching = $caching;
  }
	public function setCacheId($cacheId) {
		$this->_cacheId = $cacheId;
	}
}
