<?php

class Kansas_Controllers_Error
	extends Kansas_Controller_Abstract {
	
	public function Index(array $params) {
		global $application;
		$error;
		$code			= $this->getErrorCode($error);
		$message	= $error instanceof System_Net_WebException ?
									$error->getMessage():
									'Error en la aplicación';

		$view = $this->createPage('Error');
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
		$view->assign('errorCode',	$code);
		$view->assign('exception',	$error);
		$view->assign('env', 				$application->getEnviroment());
		$view->assign('pageTitle',	$message);
		$view->assign('requestUri', $this->getRequest()->getPathInfo());
		
		return $this->createResult($view, 'page.error.tpl');
	}
	
	protected function getErrorCode(&$error) {
		$error		= $this->getParam('error');
		return $error instanceof System_Net_WebException ? $error->getStatus():
																											 500;
	}
}
	