<?php
require_once 'Kansas/Router/Abstract.php';

class Caybe_Router_API
	extends Kansas_Router_Abstract {

    /// Miembros de Kansas_Router_Interface
	public function match() {
		$params = false;
		$path = static::getPath($this);
        if($path === FALSE)
            return false;
			
		switch($path) {
			case '':
				$params = $this->getParams([
					'controller'	=> 'API',
					'action'		=> 'index'
				]);
				break;
			case 'modules':
				$params = $this->getParams([
					'controller'	=> 'API',
					'action'		=> 'modules'
				]);
				break;
			case 'config':
				$params = $this->getParams([
					'controller'	=> 'API',
					'action'		=> 'config'
				]);
				break;
		}
		if(System_String::startWith($path, 'files')) {
			$params = $this->getParams([
				'controller'	=> 'API',
				'action'		=> 'files'
			]);
			if(strlen($path) > 5)
				$params['path'] = trim(substr($path, 6), './ ');
		}
		if($params)
			$params['router'] = get_class($this);
		
		return $params;
	}
}