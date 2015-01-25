<?php

interface Kansas_FileSystem_File_Info_Interface
	extends Kansas_FileSystem_Info_Interface {
	public function getDirectory();
	public function getDiretoryName();
	public function getExtension();
	public function getSize();
	
	public function CopyTo($target);
	public function MoveTo($target);
	public function Rename($name);
	public function Delete();
	public function Open($mode);
		
}
