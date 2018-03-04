<?php
require_once 'Kansas/Router/Abstract.php';

class Caybe_Router_Pages
	extends Kansas_Router_Abstract {

	/// Miembros de System_Configurable_Interface
    public function getDefaultOptions($environment) {
        return [
            'pages' => [],
            'params' => [],
            'base_path' => ''
        ];
    }

    public function match() {
		global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
        $pages = $this->options['pages'];
        if($path == '' && isset($pages['.']))
            $params = $this->getParams($pages['.']);
        elseif(isset($pages[$path]))
            $params = $this->getParams($pages[$path]);
            
        if($params)
            $params['router']	= get_class($this);
        
        return $params;
    }

	public function setRoute($page, $params) {
		 $this->options['pages'][$page] = $params;
	}
}