<?php

use Zend\Http\Request;

abstract class Kansas_Router_Basic
	extends Kansas_Router_Abstract {
		
	public function __construct() {
		parent::__construct(new Zend_Config(array()));
	}
	
	public function match(Request $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);
		
		if(($params = $this->getByPartialUrl($path)) !== false)
			$params['router'] = $this;
		else
			foreach($this->getRouters() as $router)
				if($params = $router->match($request))
					break;
	
		return $params;
	}
		
	public abstract function getByPartialUrl($url);
	public abstract function getRouters();
		
}