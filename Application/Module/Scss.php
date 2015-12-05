<?php

use Leafo\ScssPhp\Compiler;

class Kansas_Application_Module_Scss {
		
	private $_parser;
	private $_router;
	private $_cache = true;
	private $_formater = 'Leafo\ScssPhp\Formatter\Compressed';

	public function __construct(array $options = []) {
		global $application;
		if(isset($options['formater']))
			$this->_formater = $options['formater'];			
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir rutas
		global $application;
    $application->setRoute('css', [
      'controller'  => 'index',
      'action'      => 'scss',
      'file'        => 'default.scss'
    ]);
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
    global $environment;
    $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    // if the last char isn't *, and it's not (.scss|.css)
    if(substr($fileName, -1) != '*' && $ext !== 'scss' && $ext !== 'css' &&
			  $result = $this->getFile($fileName . '.scss', $parser, $first))
			return $result;
		if(file_exists($fileName))
	      return $fileName;
		$partialname = $first ? false : dirname($fileName).DIRECTORY_SEPARATOR.'_'.basename($fileName);
		foreach($environment->getThemePaths() as $dir) {
	    foreach ([$fileName, $partialname] as $file) {
	      if (file_exists($dir . DIRECTORY_SEPARATOR . $file) && !is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
  	      return realpath($dir . DIRECTORY_SEPARATOR . $file);
				}
	    }
		}
		return false;
	}
	
	public function toCss($file, &$md5 = false) {
		global $application;
		$file = $this->getFile($file, null, true);
		if($cache = $application->hasModule('BackendCache')) {
      $test = false;
      if($cache->test('scss-' . md5($file))) {
        $data = $cache->load('scss-' . md5($file));
        $md5 = md5($data);
        $fileList = unserialize($data);
        $test = true;
        foreach($fileList as $path => $crc) {
          if(!is_readable($path) || $crc != hash_file("crc32b", $path)) {
            $test = false;
            break;
          }
        }
      }
      if($test && $cache->test('scss-' . $md5))
        return $cache->load('scss-' . $md5);
      else {
        $css = $this->getParser()->compile(file_get_contents($file));
        $fileList = [$file => hash_file("crc32b", $file)];
        foreach($this->getParser()->getParsedFiles() as $path)
          $fileList[$path] = hash_file("crc32b", $path);
        $data = serialize($fileList);
        $md5 = md5($data);
        $cache->save($data, 'scss-' . md5($file));        
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