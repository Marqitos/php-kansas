<?php
      
class Kansas_Application_Module_Javascript
  implements Kansas_Application_Module_Interface {
  
  protected $options;
  private $_packager;
  
  public function __construct(array $options) {
    global $environment;
    $this->options = array_replace_recursive([
      'cache'     => ($environment->getStatus() == Kansas_Environment::PRODUCTION),
      'packages'   => [],
      'minifier'  => ($environment->getStatus() == Kansas_Environment::PRODUCTION ? ['flaggedComments' => false] : false) 
    ], $options);
  }
  
  public function getPackager() {
    require_once 'packager/packager.php';
    if($this->_packager == null)
      $this->_packager = new Packager($this->options['packages']);
    return $this->_packager;
  }
  
  public function build($components) {
    global $application;
		$md5 = md5(serialize($components));
    if($this->options['cache'] && $application->hasModule('BackendCache')) {
			$cache = $application->getModule('BackendCache');
			if($cache->test('js-' . $md5))
				return $cache->load('js-' . $md5);
    }
    $jsCode = $this->getPackager()->build_from_components($components);
    if($this->options['minifier']) {
      require_once 'JShrink/Minifier.php';
      $jsCode = \JShrink\Minifier::minify($jsCode, $this->options['minifier']); 
    }
    if($this->options['cache'] && $application->hasModule('BackendCache'))
			$application->getModule('BackendCache')->save($jsCode, 'js-' . $md5);
    return $jsCode;
  }
  
  public function getVersion() {
    global $environment;
    return $environment->getVersion();    
  }
  
}