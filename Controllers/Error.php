<?php

class Kansas_Controllers_Error
	extends Kansas_Controller_Abstract {
	
	public function Index(array $params) {
		global $application;
		global $environment;
		$code	= $this->getParam('code');
		$view = $application->getView();
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
		
		return $this->createViewResult('page.error.tpl', [
      'title'       => 'Error',
      'env'         => $application->getEnvironment(),
      'pageTitle'   => $this->getParam('message'),
      'requestUri'  => $environment->getRequest()->getUriString(),
      'sugestions'  => []
    ]);
	}
	
}