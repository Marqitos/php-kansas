<?php 

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use function basename;
use function header;
use function is_string;
use function md5;

require_once 'Kansas/View/Result/ViewResultAbstract.php';

class Content extends ViewResultAbstract {
		
  protected $download = false;
	private $_content;

	public function __construct($content, $mimeType) {
    parent::__construct($mimeType);
		$this->_content = $content;
	}

	protected function sendHeaders($noCache = false) {
		$noCache = md5($this->_content);
    	if(parent::sendHeaders($noCache)) {
			header('Content-Disposition: ' . ($this->download? ('attachment' . (is_string($this->download)? '; filename="' . basename($this->download) . '"': '')): 'inline'));
			header('Content-Length: ' . strlen($this->_content));
			return true;
		}
		return false;
	}

	public function executeResult() {
		if($this->sendHeaders())
			echo($this->_content);
		return true;
	}

}