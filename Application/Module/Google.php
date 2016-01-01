<?php

class Kansas_Application_Module_Google
  extends Kansas_Application_Module_Abstract {
		
	private $_client;

	public function __construct(array $options) {
    parent::__construct($options, __FILE__);
		global $application;
		$application->registerRenderCallbacks([$this, 'appRender']);
	}
  
  public function getDefaultOptions() {
    return [];
  }

	public function appRender(Kansas_View_Result_Interface $result)	{
		if($result instanceof Kansas_View_Result_Template) {
			// analitics
			if($this->getOptions('GaTracker')) {
				$javascript = Kansas_Helpers::getHelper('javascript');
				$javascript->addScript("(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga'); ga('create', '" . $this->getOptions('GaTracker') . "', 'auto'); ga('send', 'pageview');");
			}
			
			// authentication
			
		}
			
	}
	
	public function customSearch($query) {
		if(!$this->getOptions('CseCx'))
			throw new System_ArgumentException('Google::CseCx');
			
		require_once 'Google/Google_Client.php';
		require_once 'Google/contrib/Google_CustomsearchService.php';

		$search = new Google_CustomsearchService($this->getClient());
		return $search->cse->listCse($query, ['cx' => $this->getOptions('CseCx')]);
	}
	
	protected function getClient() {
		if($this->_client == null) {
			if(!$this->getOptions('AppName'))
				throw new System_ArgumentException('Google::AppName');
			if(!$this->getOptions('ApiKey'))
				throw new System_ArgumentException('Google::ApiKey');
	
			require_once 'Google/Google_Client.php';
	
			$this->_client = new Google_Client();
			$this->_client->setApplicationName($this->getOptions('AppName'));
			$this->_client->setDeveloperKey($this->getOptions('ApiKey'));
		}
		return $this->_client;
	}
  
  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	
}