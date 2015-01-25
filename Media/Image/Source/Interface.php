<?php

interface Kansas_Media_Image_Source_Interface
	extends Kansas_Core_GuidItem_Interface {
		
	public function getImageId();
	public function getImage();
	
	public function getPath();
	public function getFormat();
}