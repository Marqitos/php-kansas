<?php

abstract class Kansas_Media_Group_Image_Album_Abstract
	extends Kansas_Media_Group_GuidItem
	implements Kansas_Media_Group_Image_Album_Interface {

	public function getName() {
		return $this->row['Name'];
	}

	public function getDescription() {
		return $this->row['Description'];
	}
	
	public function getThumbnail() {
		return $this->row['Thumbnail'];
	}
	
	public function getSlug() {
		return $this->row['slug'];
	}
	
	public function getType() {
		return 'image-album';
	}

}