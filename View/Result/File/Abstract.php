<?php 

abstract class Kansas_View_Result_File_Abstract
  extends Kansas_View_Result_Abstract {
		
  protected $filename = false;
  protected $download = false;
	
  
  // Obtiene o establece el tipo de contenido de archivo	
	public function getMimeType() {
    if(empty($this->_mimeType)) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			return $finfo->file($this->_filename);
    }
		return $this->_mimeType;
	}

	protected function sendHeaders($noCache = false) {
    parent::sendHeaders($noCache);
    header('Content-Disposition: ' . ($this->download? ('attachment' . ($this->filename? '; filename="' . basename($this->filename) . '"': '')): 'inline'));
	}

}