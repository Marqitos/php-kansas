<?php

abstract class Kansas_Media_Group_Image_Gallery_Abstract
	extends Kansas_Media_Group_Abstract
	implements Kansas_Media_Group_Image_Gallery_Interface {

	protected function init() {}
	
	public function getId() {
		return System_Guid::getEmpty();
	}
	
	public function getName() {
		return $this->row['Name'];
	}
	
	public function getType() {
		return 'image-gallery';
	}

}