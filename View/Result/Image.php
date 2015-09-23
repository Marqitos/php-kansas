<?php


class Kansas_View_Result_Image
  extends Kansas_View_Result_File_Abstract {
		
	private $_image;
	private $_format;
		
	public function __construct($image, $format) {
		$this->_image = $image;
		$this->_format = $format;
	}
	
	public function getMimeType() {
		switch($this->_format) {
			case 'gif':
				return 'image/gif';
			default:
				return 'image/jpeg';
		}
	}
	
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
  	parent::sendHeaders();
		switch($this->_format) {
			case 'gif':
				return imagegif($this->_image);
			default:
				return imagejpeg($this->_image);
		}
	}
 
}