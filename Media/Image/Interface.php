<?php

interface Kansas_Media_Image_Interface
	extends Kansas_Core_GuidItem_Interface,
					Kansas_Core_Slug_Interface {

	public function getName();
	public function getDescription();
	
	public function getDefaultSourceId();
	public function getDefaultSource();

	public function getAlbumId();
	public function getAlbum();		
		
	public function getSources();
	public function getSource($format);
	
	public function getTags();
	
}