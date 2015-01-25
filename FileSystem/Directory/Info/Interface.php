<?php

interface Kansas_FileSystem_Directory_Info_Interface
	extends Kansas_FileSystem_Info_Interface {
		
	public function getParent();
	public function getIterator();
		
	public function Create();
	public function Delete();
	public function MoveTo();	
		
}