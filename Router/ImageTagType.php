<?php

class Kansas_Router_ImageTagType
	extends Kansas_Router_ImageGallery_Abstract {
	
	private $_gallery;
	
	protected function getDefaultOptions() {
		return parent::getDefaultOptions()->merge(new Zend_Config(array(
			'gallery'		=> array(
				'template'		=> 'page.image-gallery.tpl',
				'body_class'	=>	'gallery-empty'),
			'album'			=> array(
				'template'	=> 'page.image-album.tpl',
				'body_class'	=>	'album-full'),
			'photo'			=> array(
				'template'	=> 'page.image-image.tpl',
				'body_class'	=>	'album-full'),
			'tagType'		=> ''
			)));
	}

	public function __construct(Zend_Config $options) {
		parent::__construct($options);
	}
		
	public function getGallery() {
		global $application;
		if($this->_gallery == null)
			$this->_gallery = $application->getProvider('Image')->getByTagType($this->options->tagType);
		return $this->_gallery;
	}
	
	public function getAlbumRouter(Kansas_Media_Group_Image_Album_Interface $album) {
		return new Kansas_Router_ImageTag($album, $this->options, $this);
	}
	
	public function getDefaultParams() {
		return array_merge(parent::getDefaultParams(), $this->options->gallery->toArray());
	}
		
}