<?php

class Kansas_Controllers_Error
	extends Kansas_Controller_Abstract {
	
	public function Index(array $params) {
		global $application;
		global $environment;
		$code	= $this->getParam('code');
		$view = $this->createView();
    $view->assign('title', 'Error');
		$view->setCaching(false);
		
		switch($code) {
			case 403:
				header('HTTP/1.0 403 Forbidden');
				break;
			case 404:
				header("HTTP/1.0 404 Not Found");
				break;
			default:
			
		}
		
		$view->assign($params);
		$view->assign('env', 				$application->getEnvironment());
		$view->assign('pageTitle',  $this->getParam('message'));
		$view->assign('requestUri', $environment->getRequest()->getUriString());
		
		return $this->createResult($view, 'page.error.tpl');
	}
	
}