<?php

require_once('Phpsass/SassParser.php');

class Kansas_Application_Module_Sass
	extends Kansas_Application_Module_Abstract {
		
	private $_parser;
	private $_cache = false;
	private $_router;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
		if(isset($options->cache))
			$this->_cache = $options->cache;
		global $application;
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
	
	public function appPreInit() { // añadir router
		global $application;
		$application->getRouter()->addRouter($this->getRouter());
	}

	public function getRouter() {
		if($this->_router == null)
			$this->_router = new Kansas_Router_Theme(new Zend_Config(['basePath' => 'theme']));
		return $this->_router;
	}

	public function getParser() {
		if($this->_parser == null) {
			$options = $this->options->toArray();
			
			if(isset($options['load_paths'])) {
				if(is_string($options['load_paths']))
					$options['load_paths'] = realpath($options['load_paths']);
				else if(is_array($options['load_paths']))
					foreach($options['load_paths'] as $key => $path)
						$options['load_paths'][$key] = realpath($path);
			} else {
				$options['load_paths'] = [];
				if($themePath = Kansas_Router_Theme::getThemePath())
					$options['load_paths'][] = $themePath;
				$options['load_paths'][] = realpath(BASE_PATH . './themes/shared/');
			}

			$this->_parser = new SassParser($options); 
		}
		return $this->_parser;
	}
	
	public function toCss($file) {
		global $application;
		if($application->hasModule('BackendCache') && $this->_cache) {
			$cache = $application->getModule('BackendCache')->getCache();
			$parser = $this->getParser();
			$file = SassFile::get_file($file, $parser);
			$md5 = md5_file($file[0]);
			if($cache->test('sass-' . $md5))
				return $cache->load('sass-' . $md5);
			else {
				$css = $this->getParser()->toCss($file);
				$cache->save($css, 'sass-' . $md5);
				return $css;
			}
		} else
			return $this->getParser()->toCss($file);
	}
	
}