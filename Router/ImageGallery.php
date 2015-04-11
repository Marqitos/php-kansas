<?php

use Zend\Http\Request;

class Kansas_Router_ImageGallery
	extends Kansas_Router_ImageGallery_Abstract {
	
	private $_gallery;

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function match(Request $request) {
		if($params = parent::match($request))
			return $params;
			
		$path = Kansas_Router_GetPartialPath($this, $request);
		if($path === false)
			return false;

		switch($path) {
			case 'album/create':
				return array(
					'controller'	=> 'image',
					'action'			=> 'createAlbum',
					'router'			=> $this
				);
			case 'album/edit':
				return array(
					'controller'	=> 'image',
					'action'			=> 'editAlbum',
					'router'			=> $this
				);
			case 'album/delete':
				return array(
					'controller'	=> 'image',
					'action'			=> 'deleteAlbum',
					'router'			=> $this
				);
			case 'create':
				return array(
					'controller'	=> 'image',
					'action'			=> 'createImage',
					'router'			=> $this
				);
			case 'edit':
				return array(
					'controller'	=> 'image',
					'action'			=> 'editImage',
					'router'			=> $this
				);
			case 'delete':
				return array(
					'controller'	=> 'image',
					'action'			=> 'deleteImage',
					'router'			=> $this
				);
			case 'save':
				return array(
					'controller'	=> 'image',
					'action'			=> 'saveImage',
					'router'			=> $this
				);
			case 'source/add':
				return array(
					'controller'	=> 'image',
					'action'			=> 'addSource',
					'router'			=> $this
				);
			case 'source/remove':
				return array(
					'controller'	=> 'image',
					'action'			=> 'removeSource',
					'router'			=> $this
				);
			case 'tag/add':
				return array(
					'controller'	=> 'image',
					'action'			=> 'addTag',
					'router'			=> $this
				);
			case 'tag/remove':
				return array(
					'controller'	=> 'image',
					'action'			=> 'removeTag',
					'router'			=> $this
				);
		}
		return false;
	}

	public function getGallery() {
		if($this->_gallery == null)
			$this->_gallery = new Kansas_Media_Group_Image_Gallery('Fotografías');
		return $this->_gallery;
	}
	
	public function getAlbumRouter(Kansas_Media_Group_Image_Album_Interface $album) {
		return new Kansas_Router_ImageAlbum($album, $this->_options);
	}
	
	public function getDefaultOptions() {
		return parent::getDefaultOptions()->merge(new Zend_Config(array(
			'gallery'		=> array(
				'body_class'	=> 'gallery-empty'),
			'album'			=> array(),
			'photo'			=> array(),
			'photos'		=> 'empty'
		)));
	}
	
	public function getDefaultParams() {
		return array_merge(parent::getDefaultParams(), $this->options->gallery->toArray());
	}
	
}