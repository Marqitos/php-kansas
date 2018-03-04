<?php
require_once 'Kansas/Router/Abstract.php';
require_once 'Kansas/Cache/Interface.php';

class Kansas_Router_Cache
	extends Kansas_Router_Abstract {

  private $_cache;

	public function __construct(Kansas_Cache_Interface $cache) {
    parent::__construct([]);
    $this->_cache = $cache;
	}
		
	public function match() {
    global $environment;
		$params = false;
		$cacheId = Kansas_Module_BackendCache::getCacheId($environment->getRequest());
        
		if($this->_cache->test($cacheId)) {
			$params = unserialize($this->_cache->load($cacheId));
			$params['cache'] = urldecode($cacheId);
			$params['router'] = get_class($this);
		}
		return $params;
	}
	
}