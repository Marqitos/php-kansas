<?php

class Kansas_Router_Cache
	extends Kansas_Router_Abstract {

  private $_cache;
	public function __construct($cache) {
    parent::__construct([]);
    $this->_cache = $cache;
	}
		
	public function match() {
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
		$cacheId = $this->_cache->getCacheId($request);
        
		if($this->_cache->test($cacheId)) {
			$params = unserialize($this->load($cacheId));
			$params['cache'] = urldecode($cacheId);
		}
		return $params;
	}
	
}