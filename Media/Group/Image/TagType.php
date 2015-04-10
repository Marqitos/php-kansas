<?php

class Kansas_Media_Group_Image_TagType
	extends Kansas_Media_Group_Image_Gallery_Abstract {

	private $_collection;
	
	public function __construct($row) {
		parent::__construct($row);
	}
	
	public function getTagType() {
		return $this->row['Type'];
	}
	
	public function getSlugCollection() {
		global $application;
		if($this->_collection == null)
			$this->_collection = $application->getProvider('Image')->getTagGroupsByType($this->getTagType());
		return $this->_collection;	
	}
		
}