<?php 

abstract class Kansas_View_Result_File_Abstract
  extends Kansas_View_Result_Abstract {
		
  protected $download = false;
	
  protected function sendHeaders($noCache = false) {
    parent::sendHeaders($noCache);
    header('Content-Disposition: ' . ($this->download? ('attachment' . (is_string($this->download)? '; filename="' . basename($this->download) . '"': '')): 'inline'));
	}

}