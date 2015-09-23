<?php

class Kansas_Media_Group_Image_Gallery
	extends Kansas_Media_Group_Image_Gallery_Abstract {

	private $_collection;
	
	public function __construct($name) {
		parent::__construct(array('Name' =>  $name));
	}
	
	protected function init() {}
	
	public function getSlugCollection() {
		global $application;
		if($this->_collection == null)
			$this->_collection = $application->getProvider('Image')->getAlbums();
		return $this->_collection;	
	}
		
}