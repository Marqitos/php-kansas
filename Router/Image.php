<?php

use Zend\Http\Request;

class Kansas_Router_Image
	extends Kansas_Router_Abstract {
		
	public function match(Request $request) {
		global $application;
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false || $path == '')
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
