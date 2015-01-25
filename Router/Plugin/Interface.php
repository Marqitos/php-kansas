<?php

interface Kansas_Router_Plugin_Interface {
	public function beforeRoute(Zend_Controller_Request_Abstract $request, $params, &$basepath, &$cancel);
	public function afterRoute(Zend_Controller_Request_Abstract $request, $params);
}