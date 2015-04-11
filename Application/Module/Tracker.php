<?php

use Zend\Http\Request;

class Kansas_Application_Module_Tracker
	extends Kansas_Application_Module_Abstract {

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		Zend_Session::start();
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit(Request $request, $params) {
		// Comprobar sesion, dispositivo y petición
		$track = Zend_Session::namespaceIsset('track')	? $this->verifyDevice()	// verificar dispositivo
																										: $this->createSession();	// crear sesión y dispositivo
		// crear petición
		$hint = new Kansas_Track_Hint($track->id);
		$hint->save();
		return [];
	}
	
	protected function createSession() {
		$track = new Zend_Session_Namespace('track');
		$session = new Kansas_Track_Session();
		$session->save();
		$session->getDevice()->save();
		$track->id = $session->getId();
		$track->device = $session->getDevice();
		return $track;
	}
	
	protected function verifyDevice() {
		$track = new Zend_Session_Namespace('track');
		if(!$track->device->isCurrent()) {
			Zend_Session::regenerateId();
			// logout
			$this->createSession();
		}
		return $track;
	}
	
	
}
