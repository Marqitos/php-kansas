<?php

class Kansas_Media_Group_Image_Tag
	extends Kansas_Media_Group_Image_Album_Abstract {

	private $_collection;
	
	public function __construct($row) {
		parent::__construct($row);
	}
	
	public function getTagType() {
		$this->row['Type'];
	}

	public function getSlugCollection() {
		global $application;
		if($this->_collection == null)
			$this->_collection = $application->getProvider('Image')->getTagPhotos($this->getId());
		return $this->_collection;	
	}
		
}