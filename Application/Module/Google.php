<?php

class Kansas_Application_Module_Google
	extends Kansas_Application_Module_Abstract {
		
	private $_client;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		global $application;
		$application->registerRenderCallbacks([$this, 'appRender']);
	}

	public function appRender(Kansas_View_Result_Interface $result)	{
		if($result instanceof Kansas_View_Result_Page) {
			// analitics
			if($this->options->GaTracker) {
				$javascript = Kansas_Helpers::getHelper('javascript');
				$javascript->addScript("(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga'); ga('create', '" . $this->options->GaTracker . "', 'auto'); ga('send', 'pageview');");
			}
			
			// authentication
			
		}
			
	}
	
	public function customSearch($query) {
		if(!$this->options->CseCx)
			throw new System_ArgumentException('Google::CseCx');
			
		require_once 'Google/Google_Client.php';
		require_once 'Google/contrib/Google_CustomsearchService.php';

		$search = new Google_CustomsearchService($this->getClient());
		return $search->cse->listCse($query, ['cx' => $this->options->CseCx]);
	}
	
	protected function getClient() {
		if($this->_client == null) {
			if(!$this->options->AppName)
				throw new System_ArgumentException('Google::AppName');
			if(!$this->options->ApiKey)
				throw new System_ArgumentException('Google::ApiKey');
	
			require_once 'Google/Google_Client.php';
	
			$this->_client = new Google_Client();
			$this->_client->setApplicationName($this->options->AppName);
			$this->_client->setDeveloperKey($this->options->ApiKey);
		}
		return $this->_client;
	}
}