<?php
require_once 'System/Configurable/Abstract.php';

class Kansas_Module_Javascript
  extends System_Configurable_Abstract {
  
  private $_packager;
  
	public function __construct(array $options = []) {
		parent::__construct($options);

    Kansas_Controller_Index::addAction('javascript', [$this, 'controllerAction']);
	}
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
        return [
          'packages' => [],
          'minifier' => [
            'flaggedComments' => false
          ]
        ];
      case 'development':
      case 'test':
        return [
          'packages' => [],
          'minifier' => false
        ];
      default:
        require_once 'System/NotSuportedException.php';
        throw new System_NotSuportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}

  public function getPackager() {
    require_once 'packager/packager.php';
    if($this->_packager == null)
      $this->_packager = new Packager($this->options['packages']);
    return $this->_packager;
  }
  
  public function build($components, &$md5 = false) {
    global $application;
    $cache = FALSE;
    $test = FALSE;
    if($cache = $application->hasModule('BackendCache')) {
      if($cache->test('js-' . md5(serialize($components)))) {
        $data = $cache->load('js-' . md5(serialize($components)));
        $md5 = md5($data);
        $dataList = unserialize($data);
        $test = $dataList['packages'] == $this->options['packages'];
        foreach($dataList['files'] as $path => $crc) {
          if(!is_readable($path) || $crc != hash_file("crc32b", $path)) {
            $test = false;
            break;
          }
        }
      }
      if($test && $cache->test('js-' . $md5))
  			return $cache->load('js-' . $md5);
      else {
        $fileList = $this->getPackager()->components_to_files($components);
        $fileList = $this->getPackager()->complete_files($fileList);
        $dataList = [
          'packages'  => $this->options['packages'],
          'files'     => []
        ];
        foreach($fileList as $file)
          $dataList['files'][$this->getPackager()->get_file_path($file)] = hash_file("crc32b", $this->getPackager()->get_file_path($file));          
        $data = serialize($dataList);
        $md5 = md5($data);
        $cache->save($data, 'js-' . md5(serialize($components)), ['javascript', 'js-index']);
        $jsCode = $this->javascriptFromComponents($components, $this->options['minifier']);        
        $cache->save($jsCode, 'js-' . $md5, ['javascript', 'js-code']);
        return $jsCode;
      }
    } else
      return $this->javascriptFromComponents($components, $this->options['minifier']);
  }
  
  public function javascriptFromComponents($components, $minifier = false) {
    $jsCode = $this->getPackager()->build_from_components($components);
    if($minifier) {
      require_once 'JShrink/Minifier.php';
      return \JShrink\Minifier::minify($jsCode, $minifier); 
    } else 
      return $jsCode;
  }

	public function controllerAction(Kansas_Controller_Interface $controller, array $vars) {
    $components = $vars['component'];
    require_once 'Kansas/View/Result/Javascript.php';
    return new Kansas_View_Result_Javascript($components);
	}		
   
}