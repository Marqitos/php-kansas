<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/View/Interface.php';

class Kansas_View_Smarty
	extends System_Configurable_Abstract
  implements Kansas_View_Interface {
		
  private $_smarty;
  private $_compileDir;
	private $_templateDir;
	private $_configDir;
	private $_cacheDir;
  
	private $_cacheId = null;
  /*			
		foreach(['configDir', 'pluginDir', 'cacheDir'] as $config_dir) {
			if(isset($config[$config_dir]) && is_string($config[$config_dir]))
				$config[$config_dir] = realpath($config[$config_dir]);
		}
    if(isset($config['pluginDir']) && is_array($config['pluginDir'])) {
      $pluginDir = [];
      foreach($config['pluginDir'] as $dir)
				$pluginDir[] = realpath($dir);
      $config['pluginDir'] = $pluginDir;
    }
  }
  */
  /// Miembros de System_Configurable_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
        return [
          'compile_dir' => realpath(BASE_PATH . 'tmp/view-compile/'),
          'config_dir' => [
            realpath(HOME_PATH . 'view-config')
          ],
          'cache_dir' => realpath(BASE_PATH . 'tmp/cache/'),
          'plugin_dir' => [
            realpath(LIBRARY_PATH . 'Kansas/View/Smarty/')],
          'template_dir' => realpath(HOME_PATH . 'layout'),
          'caching' => true,
          'debugging' => false
        ];
      case 'development':
        return [
          'compile_dir' => realpath(BASE_PATH . 'tmp/view-compile/'),
          'config_dir' => [
            realpath(HOME_PATH . 'view-config')
          ],
          'cache_dir' => realpath(BASE_PATH . 'tmp/cache/'),
          'plugin_dir' => [
            realpath(LIBRARY_PATH . 'Kansas/View/Smarty/')],
          'template_dir' => realpath(HOME_PATH . 'layout'),
          'caching' => false,
          'debugging' => true
        ];
      case 'test':
        return [
          'compile_dir' => realpath(BASE_PATH . 'tmp/view-compile/'),
          'config_dir' => [
            realpath(HOME_PATH . 'view-config')
          ],
          'cache_dir' => realpath(BASE_PATH . 'tmp/cache/'),
          'plugin_dir' => [],
          'template_dir' => realpath(HOME_PATH . 'layout'),
          'caching' => false,
          'debugging' => false
        ];
      default:
        require_once 'System/NotSupportedException.php';
        throw new System_NotSupportedException("Entorno no soportado [$environment]");
    }
  }

  public function getCompileDir() {
    if($this->_compileDir === null) {
      $this->_compileDir = realpath($this->options['compile_dir']);
    }
    return $this->_compileDir;
  }

  public function getEngine() {
		if($this->_smarty == null) {
      require_once 'Smarty/Autoloader.php';
      Smarty_Autoloader::register();
      require_once 'Smarty/Smarty.class.php';
      
			$this->_smarty = new Smarty();
			$this->_smarty->setCompileDir($this->getCompileDir());
      $this->_smarty->config_dir = $this->options['config_dir'];
      $this->_smarty->cache_dir = $this->options['cache_dir'];
      $this->_smarty->caching = (bool)$this->options['caching'];
      $this->_smarty->debugging = (bool)$this->options['debugging'];
      foreach((array)$this->options['plugin_dir'] as $dir)
        $this->_smarty->addPluginsDir($dir);
		}
		
    return $this->_smarty;
  }
	
	public function getScriptPaths() {
    if($this->_templateDir == null) {
      $this->_templateDir = [];
      if(is_array($this->options['template_dir'])) {
        foreach($this->options['template_dir'] as $key => $value)
          $this->_templateDir[$key] = realpath($value);
      }	else
        $this->_templateDir[] = realpath($this->options['template_dir']);
    }
		return $this->_templateDir;
	}
  public function setScriptPath($path) {
    $this->options['template_dir'] = $path;
    $this->_templateDir = null;
  }
  
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
  
  public function assign($spec, $value = null) {
    if($value === null)
      $this->getEngine()->assign($spec);
    else
      $this->getEngine()->assign($spec, $value);
  }
  
  public function clearVars() {
    $this->getEngine()->clear_all_assign();
  }
  
  public function render($file) {
    $file = substr($file, strrpos($file, '/'));
    $this->getEngine()->setTemplateDir($this->getScriptPaths());
    return $this->getEngine()->fetch($file, $this->_cacheId);
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
  
  public function createData() {
    return $this->getEngine()->createData();
  }
  
  public function createTemplate($file, array $data = []) {
    $file = substr($file, strrpos($file, '/'));
    $this->getEngine()->setTemplateDir($this->getScriptPaths());
    return $this->getEngine()->createTemplate($file, $data);
  }
}
