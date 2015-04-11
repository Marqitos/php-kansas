<?php

use Zend\Http\Request;

class Kansas_Router_ImageAlbum
	extends Kansas_Router_Abstract {

	private $_galleryRouter;
	private $_album;
	private $_gallery;

	public function __construct(Kansas_Media_Group_Image_Album_Interface $album, Zend_Config $options, $galleryRouter = null) {
		parent::__construct($options);
		$this->_galleryRouter = $galleryRouter;
		$this->_album	= $album;
	}
		
	public function getDefaultOptions() {
		return parent::getDefaultOptions()->merge(new Zend_Config(array(
			'album'			=> array(
				'controller'	=> 'image',
				'action'			=> 'album',
				'body_class'	=> 'album-full'),
			'photo'			=> array(
				'controller'	=> 'image',
				'action'			=> 'image')
			)));
	}
	
	public function getGallery() {
		return $this->_gallery;
	}
	public function setGallery($gallery) {
		$this->_gallery = $gallery;
	}
	
	public function getAlbum() {
		return $this->_album;
	}
	
	public function getGalleryRouter() {
		return $this->_galleryRouter;
	}
		
	public function match(Request $request) {
		$path = Kansas_Router_GetPartialPath($this, $request);

		if($path === false)
			return false;
			
		if($path == '') {
			return array_merge($this->getDefaultAlbumParams(),
				array(
					'gallery'				=> $this->getGallery(),
					'album'					=> $this->getAlbum(),
					'router'				=> $this,
					'galleryRouter'	=> $this->getGalleryRouter()
				));
		}
			
		foreach($this->getAlbum() as $image) {
			if($image->getSlug() == $path) {
				return array_merge($this->getDefaultPhotoParams(),
					array(
						'gallery'				=> $this->getGallery(),
						'album'					=> $this->getAlbum(),
						'image'					=> $image,
						'router'				=> $this,
						'galleryRouter'	=> $this->getGalleryRouter()
					));
				
			}
		}
		
		return false;

	}
	
  public function assemble($data = array(), $reset = false, $encode = false) {
		$path = parent::assemble($data, $reset, $encode);
	 	if(isset($data['action'])) {
			switch($data['action']) {
				case 'viewImages':
					$result = array();
					foreach($data['images'] as $image)
						$result[$image->getId()->__toString()] = $path . '/' . $image->getSlug();
					return $result;
				case 'viewImage':
					return $path . '/' . $data['image']->getSlug();
				case 'pathImages':
					$result = array();
					foreach($data['images'] as $image)
						$result[$image->getId()->__toString()] = $image->getPath($data['format'], $data['params']);
					return $result;
			}
		}
	}
	
	public function getDefaultAlbumParams() {
		return array_merge(parent::getDefaultParams(), $this->options->album->toArray());
	}
	
	public function getDefaultPhotoParams() {
		return array_merge(parent::getDefaultParams(), $this->options->photo->toArray());
	}
	
}