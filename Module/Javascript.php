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
    //var_dump($cache = $application->hasModule('BackendCache'), $cache->test('js-' . $md5));
		$md5 = md5(serialize($components));    
    if($this->getOptions('cache') &&
       $cache = $application->hasModule('BackendCache')) {
      // TODO: Comprobar crc de todos archivos que componen el resultado
      if($cache->test('js-' . $md5))
  			return $cache->load('js-' . $md5);
    }
    $jsCode = $this->getPackager()->build_from_components($components);
    if($this->getOptions('minifier')) {
      require_once 'JShrink/Minifier.php';
      $jsCode = \JShrink\Minifier::minify($jsCode, $this->getOptions('minifier')); 
    }
    if($this->getOptions('cache') && $cache)
			$cache->save($jsCode, 'js-' . $md5);
    return $jsCode;
  }
  
  public function getVersion() {
    global $environment;
    return $environment->getVersion();    
  }
  
}