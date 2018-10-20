<?php 

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use function basename;
use function header;
use function is_string;

require_once 'Kansas/View/Result/ViewResultAbstract.php';

abstract class FileAbstract extends ViewResultAbstract {
		
  protected $download = false;
	
  protected function sendHeaders($noCache = false) {
    parent::sendHeaders($noCache);
    header('Content-Disposition: ' . ($this->download? ('attachment' . (is_string($this->download)? '; filename="' . basename($this->download) . '"': '')): 'inline'));
	}

}