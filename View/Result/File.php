<?php 

class Kansas_View_Result_File
  extends Kansas_View_Result_File_Abstract {
		
	private $_filename;
	private $_chunksize; // how many bytes per chunk 
	private $_retbytes;
	private $_mimeType;
		
	public function __construct($filename, $retbytes=true) {
		$this->_filename	= $filename;
		$this->_retbytes	= $retbytes;
		$this->_chunksize	= 1*(1024*1024);
	}
	
	public function getMimeType() {
		if(!$this->hasMimeType())
			$this->setMimeType(mime_content_type($this->_filename));
		return parent::getMimeType();
	}
	
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
		parent::executeResult();
		
		if($this->getUseXSendFile()) {
			if(strtolower(substr(php_uname('s'), 0, 3)) == 'win')
				$filename = str_replace('\\', '/', $this->_filename);
			else
				$filename = $this->_filename;
			header("X-SENDFILE: " . $filename);
		} else {
			$buffer = ''; 
			$cnt =0; 
			// $handle = fopen($filename, 'rb'); 
			$handle = fopen($this->_filename, 'rb'); 
			if ($handle === false) 
				return false; 
			while (!feof($handle)) { 
				$buffer = fread($handle, $this->_chunksize); 
				echo $buffer; 
				ob_flush(); 
				flush(); 
				if ($this->_retbytes)
					$cnt += strlen($buffer); 
			} 
			$status = fclose($handle); 
			return ($this->_retbytes && $status)?
				$cnt: // return num. bytes delivered like readfile() does. 
				$status; 
		}
	}
 
	public function getUseXSendFile() {
		return true;
//		if(array_search('mod_xsendfile', apache_get_modules())) {
	}

}