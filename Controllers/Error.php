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
				header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
				break;
			case 404:
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
				break;
			default:
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		}
		
		$view->assign($params);
		$view->assign('env', 				$application->getEnvironment());
		$view->assign('pageTitle',  $this->getParam('message'));
		$view->assign('requestUri', $environment->getRequest()->getUriString());
    $view->assign('sugestions', []);
		
		return $this->createResult($view, 'page.error.tpl');
	}
	
}