<?php

/// Proporciona la manera de añadir archivos y bloques de script
class Kansas_Helpers_Javascript
	implements Kansas_Helpers_Interface {
	private $files		= [];
	private $scripts	= [];
	
	const SCRIPT_START 	= '<script type="text/javascript">';
	const SCRIPT_END 		= '</script>';
	const SCRIPT_FILE 	= '<script type="text/javascript" src="%s"></script>';
   
	public function getName() {
		return 'javascript';
	}
	
	public function getType() {
		return 'javascript';
	}
	
	public function addFile($file) {
		if(!array_search($file, $this->files)) {
			$this->files[] = $file;
			$this->scripts[] = array(
				'type' => 'file',
				'data' => $file
			);
			return true;
		}
		return false;
	}
	
	public function addScript($script) {
		$this->scripts[] = array (
			'type'	=>	'script',
			'data'	=>	$script
		);
	}
	
	public function addDelegate($method) {
		if(is_callable($method)) {
			$this->scripts[] = array (
				'type'	=>	'delegate',
				'data'	=>	$method
			);
		}
	}
	
	public function containsFile($file) {
		return array_search($file, $this->files);
	}
	
	public function render() {
		$result = '';
		$script = false;
		foreach($this->scripts as $item) {
			switch($item['type']) {
				case 'file':
					if($script) {
						$result .= self::SCRIPT_END;
						$script = false;
					}
					$result .= sprintf(self::SCRIPT_FILE, $item['data']);
					break;
				case 'script':
					if(!$script) {
						$result .= self::SCRIPT_START;
						$script = true;
					}
					$result .= $item['data'];
					break;
				case 'delegate':
					if(!$script) {
						$result .= self::SCRIPT_START;
						$script = true;
					}
					$result .= call_user_func($item['data']);
					break;
			}
		}
		if($script) $result .= self::SCRIPT_END;
		return $result;
	}
	
}