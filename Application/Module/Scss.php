<?php

use Leafo\ScssPhp\Compiler;

class Kansas_Application_Module_Scss {
		
	private $_parser;
	private $_router;
	private $_cache = true;
	private $_formater = 'Leafo\ScssPhp\Formatter\Compressed';

	public function __construct(array $options = []) {
		global $application;
		global $environment;
		$this->_cache = isset($options['cache'])
			? (bool) $options['cache']
			: ($environment->getStatus() == Kansas_Environment::PRODUCTION);
		if(isset($options['formater']))
			$this->_formater = $options['formater'];			
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir rutas
		global $application;
		$config = $application->getConfig();
		if(isset($config['theme']))
      $application->setRoute('css', [
        'controller'  => 'index',
        'action'      => 'scss',
        'file'        => 'default.scss'
      ]);
	}

	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_Theme(['basePath' => 'theme']);
		return $this->_router;
	}

	public function getParser() {
		if($this->_parser == null) {
			global $environment;
			$this->_parser = new Compiler();
			$this->_parser->addImportPath([$this, 'getFile']);
			switch($environment->getStatus()) {
				case Kansas_Environment::DEVELOPMENT:
					$this->_parser->setFormatter('Leafo\ScssPhp\Formatter\Expanded');
					break;
				default:
					$this->_parser->setFormatter($this->_formater);
			}
			 	
		} 
		return $this->_parser;
	}
	
	public function getFile($fileName, $parser, $first = false) {
    $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    // if the last char isn't *, and it's not (.scss|.css)
    if(substr($fileName, -1) != '*' && $ext !== 'scss' && $ext !== 'css' &&
			  $result = $this->getFile($fileName . '.scss', $parser, $first))
			return $result;
		if(file_exists($fileName))
	      return $fileName;
		$partialname = $first ? false : dirname($fileName).DIRECTORY_SEPARATOR.'_'.basename($fileName);
		foreach([Kansas_Router_Theme::getThemePath(), realpath(BASE_PATH . './themes/shared/')] as $dir) {
	    foreach ([$fileName, $partialname] as $file) {
	      if (file_exists($dir . DIRECTORY_SEPARATOR . $file) && !is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
  	      return realpath($dir . DIRECTORY_SEPARATOR . $file);
				}
	    }
		}
		return false;
	}
	
	public function toCss($file) {
		global $application;
		$file = $this->getFile($file, null, true);
		if($this->_cache && $application->hasModule('BackendCache')) {
			$cache = $application->getModule('BackendCache');
			$md5 = md5_file($file);
			if($cache->test('scss-' . $md5))
				return $cache->load('scss-' . $md5);
			else {
				$css = $this->getParser()->compile(file_get_contents($file));
				$cache->save($css, 'scss-' . $md5);
				return $css;
			}
		} else
			return $this->getParser()->compile(file_get_contents($file));
	}
	
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}  
}