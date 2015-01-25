<?php

interface Kansas_FileSystem_File_Interface
	extends Kansas_Core_GuidItem_Interface {
		
	public function getRelativePath();
	public function getAbsolutePath();
	public function getMd5();
	public function getMimeType();
	public function getSize();
	
}
