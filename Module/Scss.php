<?php
require_once 'System/Configurable/Abstract.php';
require_once 'Kansas/Module/Interface.php';
require_once 'Kansas/Controller/Index.php';
use Leafo\ScssPhp\Compiler;

class Kansas_Module_Scss
	extends System_Configurable_Abstract
	implements Kansas_Module_Interface {
		
	private $_parser;
	private $_router;

	public function __construct(array $options = []) {
		parent::__construct($options);

    Kansas_Controller_Index::addAction('scss', [$this, 'controllerAction']);
	}

  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
        return [
          'formater' => 'Leafo\ScssPhp\Formatter\Compressed',
          'cache' => true,
          'environment' => $environment
				];
      case 'development':
      case 'test':
        return [
          'formater' => 'Leafo\ScssPhp\Formatter\Expanded',
          'cache' => false,
          'environment' => $environment
				];
      default:
        require_once 'System/NotSupportedException.php';
        throw new System_NotSupportedException("Entorno no soportado [$environment]");
    }
  }
		
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}  

	public function getParser() {
		if($this->_parser == null) {
			$this->_parser = new Compiler();
			$this->_parser->addImportPath([$this, 'getFile']);
			$this->_parser->setFormatter($this->options['formater']);
		} 
		return $this->_parser;
	}
	
	public function getFile($fileName, $first = false) {
    global $environment;
    $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    // if the last char isn't *, and it's not (.scss|.css)
    if( substr($fileName, -1) != '*' &&
        $ext !== 'scss' &&
        $ext !== 'css' &&
			  $result = $this->getFile($fileName . '.scss', $first))
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
		if($this->options['cache'] &&
			$cache = $application->hasModule('BackendCache')) {
      $test = false;
      if($cache->test('scss-list-' . md5($file))) {
        $data = $cache->load('scss-list-' . md5($file));
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
      if($test && $cache->test('scss-' . $md5 . '.css'))
        return $cache->load('scss-' . $md5 . '.css');
      else {
        $css = $this->getParser()->compile(file_get_contents($file));
        $fileList = [$file => hash_file("crc32b", $file)];
        foreach($this->getParser()->getParsedFiles() as $path => $time)
          $fileList[$path] = hash_file("crc32b", $path);
        $data = serialize($fileList);
        $md5 = md5($data);
        $cache->save($data, 'scss-list-' . md5($file));        
        $cache->save($css, 'scss-' . $md5 . '.css', ['scss']);
        return $css;
      }
		} else
			return $this->getParser()->compile(file_get_contents($file));
	}

	public function controllerAction($controller, array $vars = []) {
    require_once 'Kansas/View/Result/Scss.php';
		return new Kansas_View_Result_Scss($vars['file']);
	}
	
}