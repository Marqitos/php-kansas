<?php

class Kansas_Application_Module_RouterCache
	extends Kansas_Application_Module_Abstract {
	
	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
	
	public function getCache() {
		global $application;
		return $application->getModule('BackendCache')->getCache();
	}
	
	public function getCacheId(Zend_Controller_Request_Abstract $request) {
		return urlencode(
			'router|'.
			Kansas_User::getCurrentRole().
			'|'.
			$request->getRequestUri()
		);
	}
	
	public function routing(Zend_Controller_Request_Abstract $request, $params, &$basepath, &$cancel) {
		$cacheId = $this->getCacheId($request);
		if($this->getCache()->test($cacheId)) {
			$params = unserialize($this->getCache()->load($cacheId));
			$cancel = true;
			$params['cache'] = urldecode($cacheId);
		}
		return $params;
	}
	public function route(Zend_Controller_Request_Abstract $request, $params) {
		if(!isset($params['cache']) && !isset($params['error']))
			$this->getCache()->save(serialize($params), $this->getCacheId($request));
		return $params;
	}
	
}
