<?php

class Kansas_Media_Image_Source
	extends Kansas_Core_GuidItem_Model
	implements Kansas_Media_Image_Source_Interface {
		
	const VALIDATION_IMAGE	= 0x01;
	const VALIDATION_PATH 	= 0x02;
	const VALIDATION_FORMAT	= 0X04;
		
	private $_imageId;
	private $_image;
		
	protected function init() {
		parent::init();
		if($this->row['Image'] instanceof Kansas_Media_Image_Interface) {
			$this->_image				= $this->row['Image'];
			$this->_imageId			= $this->_image->getId();			
			$this->row['Image']	= $this->_imageId->getHex();
		} elseif($this->row['Image'] instanceof System_Guid) {
			$this->_imageId			= $this->row['Image'];			
			$this->row['Image']	= $this->_imageId->getHex();
		} else
			$this->_imageId			= new System_Guid($this->row['Image']);			
	}
	public function getImageId() {
		return $this->_image == null?
			$this->_imageId:
			$this->_image->getId();
	}
	public function getImage() {
		global $application;
		if($this->_image == null)
			$this->_image = $application->getProvider('Image')->getImage($this->_imageId);
		return $this->_image;
	}
	
	public function getPath() {
		return $this->row['Path'];
	}
	public function setPath($path) {
		$this->row['Path'] = $path;
	}
	
	public function getFormat() {
		return $this->row['Format'];
	}
	public function setFormat($format) {
		$this->row['Format'] = $format;
	}
	
	public function validate() {
		$result = self::VALIDATION_SUCCESS;
		
//		if( $this->_imageId = System_Guid::getEmpty() &&
//				$this->_image == null)
//			$result |= self::VALIDATION_IMAGE;
			
		$this->row['Path'] = trim($this->row['Path']);
		$this->row['Format'] = trim($this->row['Format']);
		
		if(empty($this->row['Path']))
			$result |= self::VALIDATION_PATH;
		
		if(empty($this->row['Format']))
			$result |= self::VALIDATION_FORMAT;
		
		return $result;
	}
		
}