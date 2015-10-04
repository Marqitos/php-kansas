<?php

require_once('Phpsass/SassParser.php');

class Kansas_Application_Module_Sass
  extends Kansas_Application_Module_Abstract {
		
	private $_parser;
	private $_router;

	public function __construct(array $options) {
		global $application;
    parent::__construct($options);
		$application->registerPreInitCallbacks([$this, "appPreInit"]);
	}
  
  public function getDefaultOptions() {
    global $environment;
    return [
      'cache' => ($environment->getStatus() == Kansas_Environment::DEVELOPMENT ? FALSE: TRUE)
    ];
  }
	
	public function appPreInit() { // añadir rutas
		global $application;
		$config = $application->getConfig();
		if(isset($config['theme']))
      $application->setRoute('css', [
        'controller'  => 'index',
        'action'      => 'sass',
        'file'        => 'default.scss'
      ]);
	}

	public function getParser() {
		if($this->_parser == null) {
			$options = $this->getOptions();
			
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
		if($application->hasModule('BackendCache') && $this->getOptions('cache')) {
			$cache = $application->getModule('BackendCache');
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

  public function getVersion() {
		global $environment;
		return $environment->getVersion();
	}	
}