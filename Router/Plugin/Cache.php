<?php

class Kansas_Router_Plugin_Cache
	implements Kansas_Router_Plugin_Interface {
	
	private $_cache;
	
	public function __construct(Zend_Cache_Backend_Interface $cache) {
		$this->_cache = $cache;
	}
	
	public function getCache() {
		return $this->_cache;
	}
	
	public function getCacheId(Zend_Controller_Request_Abstract $request) {
		return urlencode(
			'router|'.
			Kansas_User::getCurrentRole().
			'|'.
			$request->getRequestUri()
		);
	}
	
	public function beforeRoute(Zend_Controller_Request_Abstract $request, $params, &$basepath, &$cancel) {
		$cacheId = $this->getCacheId($request);
		if($this->_cache->test($cacheId)) {
			$params = unserialize($this->_cache->load($cacheId));
			$cancel = true;
			$params['fromCache'] = urldecode($cacheId);
		}
		return $params;
	}
	public function afterRoute(Zend_Controller_Request_Abstract $request, $params) {
		if(!isset($params['fromCache']))
			$this->_cache->save(serialize($params), $this->getCacheId($request));
		return $params;
	}
	
}
