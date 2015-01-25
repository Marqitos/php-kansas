<?php

class Kansas_Media_Group_Image_Album
	extends Kansas_Media_Group_Image_Album_Abstract {

	private $_collection;
	
	public function __construct($row) {
		parent::__construct($row);
	}
	
	public function getUrl() {
		return '/cuadros/' . $this->getSlug();
	}
	
	public function getSlugCollection() {
		if($this->_collection == null)
			$this->_collection = Kansas_Application::getInstance()->getProvider('Image')->getAlbums();
		return $this->_collection;	
	}
		
}