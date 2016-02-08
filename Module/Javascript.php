<?php
      
class Kansas_Module_Javascript
  extends Kansas_Module_Abstract {
  
  private $_packager;
  
  public function __construct(array $options) {
    parent::__construct($options, pathinfo(__FILE__));
  }

  public function getPackager() {
    require_once 'packager/packager.php';
    if($this->_packager == null)
      $this->_packager = new Packager($this->getOptions('packages'));
    return $this->_packager;
  }
  
  public function build($components, &$md5 = false) {
    global $application;
    $cache = false;
    $test = FALSE;
    if($cache = $application->hasModule('BackendCache')) {
      // TODO: Comprobar crc de todos archivos que componen el resultado
      if($cache->test('js-' . md5(serialize($components)))) {
        $data = $cache->load('js-' . md5(serialize($components)));
        $md5 = md5($data);
        $dataList = unserialize($data);
        $test = $dataList['packages'] == $this->getOptions('packages');
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
        $dataList = [
          'packages'  => $this->getOptions('packages'),
          'files'     => []
        ];
        foreach($fileList as $file)
          $dataList['files'][$this->getPackager()->get_file_path($file)] = hash_file("crc32b", $this->getPackager()->get_file_path($file));          
        $data = serialize($dataList);
        $md5 = md5($data);
        $cache->save($data, 'js-' . md5(serialize($components)), ['javascript', 'js-index']);
        $jsCode = $this->javascriptFromComponents($components, $this->getOptions('minifier'));        
        $cache->save($jsCode, 'js-' . $md5, ['javascript', 'js-code']);
        return $jsCode;
      }
    } else
      return $this->javascriptFromComponents($components, $this->getOptions('minifier'));
  }
  
  public function javascriptFromComponents($components, $minifier = false) {
    $jsCode = $this->getPackager()->build_from_components($components);
    if($minifier) {
      require_once 'JShrink/Minifier.php';
      return \JShrink\Minifier::minify($jsCode, $minifier); 
    } else 
      return $jsCode;
  }
  
  public function getVersion() {
    global $environment;
    return $environment->getVersion();    
  }
  
}