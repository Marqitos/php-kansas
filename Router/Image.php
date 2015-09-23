<?php

class Kansas_Router_Image
	extends Kansas_Router_Abstract {
		
	public function match() {
		global $application;
    global $environment;
		$params = false;
		$path = trim($environment->getRequest()->getUri()->getPath(), '/');
    
    if(Kansas_String::startWith($this->getBasePath(), $path))
      $path = substr($this->getBasePath(), strlen($this->getBasePath()));
    else
			return false;
		
		$imageProvider = $application->getProvider('image');
		$images = $imageProvider->getAll();
		
		foreach($images as $image) {
			if(Kansas_String::startWith($path, $image->getSlug() . '.')) {
				return array(
					'controller'	=> 'image',
					'action'			=> 'resize',
					'image'				=> $image,
					'format' 			=> pathinfo($path, PATHINFO_EXTENSION)
				);
			}
			if(Kansas_String::startWith($path, $image->getSlug() . '_files')) {
				var_dump($path);
				
				return array(
					'controller'	=> 'image',
					'action'			=> 'resize',
					'image'				=> $image,
					'format' 			=> pathinfo($path, PATHINFO_EXTENSION)
				);
			}
		}
		
		// TODO: Image-ErrorPlugin
		return array(
			'controller'	=> 'image',
			'action'			=> 'resize',
			'header'			=> 404,
			'image'				=> Kansas_Media_Image::getErrorInstace()
		);
	}
		
}
