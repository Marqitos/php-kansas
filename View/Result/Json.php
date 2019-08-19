<?php
namespace Kansas\View\Result;

use Kansas\View\Result\StringAbstract;
use function json_encode;

require_once('Kansas/View/Result/StringAbstract.php');

class Json extends StringAbstract {
		
	private $data;
	
	public function __construct($data) {
    parent::__construct('application/json; charset: UTF-8');    
		$this->data = $data;
	}
		
	public function getResult(&$noCache) {
    $noCache = true;
		return json_encode($this->data);
	}
      
}