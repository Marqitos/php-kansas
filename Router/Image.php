<?php

class Kansas_Router_Image
	extends Kansas_Router_Abstract {
		
	public function match() {
		$path = $this->getPath();
		if($path == false)
			return false;

		global $application;
		global $environment;
		$params = false;
		
		$imageProvider = $application->getProvider('image');
		$images = $imageProvider->getAll();
		
		foreach($images as $image) {
			if(System_String::startWith($path, $image->getSlug() . '.')) {
				$params = $this->getParams([
					'controller'	=> 'image',
					'action'		=> 'resize',
					'image'			=> $image,
					'format' 		=> pathinfo($path, PATHINFO_EXTENSION)
				]);
			}
			if(System_String::startWith($path, $image->getSlug() . '_files')) {
				var_dump($path);
				$params = $this->getParams([
					'controller'	=> 'image',
					'action'		=> 'resize',
					'image'			=> $image,
					'format' 		=> pathinfo($path, PATHINFO_EXTENSION)
				]);
			}
		}
		if($params) {
			$params['router'] = get_class($this);
			return $params;
		}

		// TODO: Image-ErrorPlugin
		return array(
			'controller'	=> 'image',
			'action'		=> 'resize',
			'header'		=> 404,
			'image'			=> Kansas_Media_Image::getErrorInstace()
		);
	}
		
}
