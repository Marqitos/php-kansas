<?php

class Kansas_FyleSystem_File_Stream {
	
	private $_handle;
	
	public function __construct($filename, $mode) {
		$this->_handle = fopen($filename, $mode);
	}
	
	public function __destruct() {
		fclose($this->_handle);
	}
	
	public function read($length) {
		return fread($this->_handle, $length);
	}
	
	public function write($string) {
		return fwrite($this->_handle, $string);
	}
	
}