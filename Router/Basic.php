<?php

abstract class Kansas_Router_Basic
	extends Kansas_Router_Abstract {
		
	public function match() {
		global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
		
		if(($params = $this->getByPartialUrl($path)) !== false)
			$params['router'] = get_class($this);
		else
			foreach($this->getRouters() as $router)
				if($params = $router->match($request))
					break;
	
		return $params;
	}
		
	public abstract function getByPartialUrl($url);
	public abstract function getRouters();
		
}