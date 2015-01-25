<?php

interface Kansas_Media_Group_Image_Album_Interface
	extends Kansas_Media_Group_Interface, Kansas_Core_Slug_Interface {
		
	public function getName();
	public function getDescription();
	public function getThumbnail();

}