<?php

class Kansas_Router_ImageTagType
	extends Kansas_Router_ImageGallery_Abstract {
	
	private $_gallery;
	
	protected function getDefaultOptions() {
		return array_replace_recursive(parent::getDefaultOptions(), [
			'gallery'		=> [
				'template'		=> 'page.image-gallery.tpl',
				'body_class'	=>	'gallery-empty'],
			'album'			=> [
				'template'	=> 'page.image-album.tpl',
				'body_class'	=>	'album-full'],
			'photo'			=> [
				'template'	=> 'page.image-image.tpl',
				'body_class'	=>	'album-full'],
			'tagType'		=> ''
    ]);
	}

	public function __construct(array $options) {
		parent::__construct();
    $this->setOptions();
	}
		
	public function getGallery() {
		global $application;
		if($this->_gallery == null)
			$this->_gallery = $application->getProvider('Image')->getByTagType($this->options['tagType']);
		return $this->_gallery;
	}
	
	public function getAlbumRouter(Kansas_Media_Group_Image_Album_Interface $album) {
		return new Kansas_Router_ImageTag($album, $this->options, $this);
	}
	
	public function getDefaultParams() {
		return array_merge($this->getDefaultParams(), $this->options['gallery']);
	}
		
}