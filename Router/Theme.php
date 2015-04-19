<?php

class Kansas_Router_Theme
	extends Kansas_Router_Abstract {
	use Router_PartialPath;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Kansas_Request $request) {
		$path = $this->getPartialPath($this, $request);
		$params = false;

  	if($path === false || strstr($path, '..'))
			return false;

		if($file = realpath(self::getThemePath() . '/img/' . $path))
			$params = array(
				'controller'	=> 'index',
				'action'			=> 'file',
				'file'				=> $file
			);
			
		if($params)
			$params['router']	= $this;
		return $params;
	}
	
  public function assemble($data = [], $reset = false, $encode = false) {
		$basepath = parent::assemble($data, $reset, $encode);
		switch($data['action']) {
			
			
		}
	}
	
	public static function getThemePath() {
		global $application;
		$config = $application->getConfig();
		return isset($config['theme']) ? realpath(BASE_PATH . './themes/' . $config['theme'] . '/')
																	 : false;
	}
	
	

}