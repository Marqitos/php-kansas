<?php

class Kansas_Router_Image
	extends Kansas_Router_Abstract {
		
	public function match(Zend_Controller_Request_Abstract $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false || $path == '')
			return false;
		
		
		$imageProvider = Kansas_Application::getInstance()->getProvider('image');
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
