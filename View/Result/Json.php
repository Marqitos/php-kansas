<?php
namespace Kansas\View\Result;

<<<<<<< HEAD
use Kansas\View\Result\StringAbstract;
use function json_encode;

require_once('Kansas/View/Result/StringAbstract.php');
=======
use Kansas\Environment;
use Kansas\View\Result\StringAbstract;
use function json_encode;
use function md5;

require_once 'Kansas/View/Result/StringAbstract.php';
>>>>>>> origin/master

class Json extends StringAbstract {
		
	private $data;
	
	public function __construct($data) {
<<<<<<< HEAD
    parent::__construct('application/json; charset: UTF-8');    
		$this->data = $data;
	}
		
	public function getResult(&$noCache) {
    $noCache = true;
		return json_encode($this->data);
=======
    parent::__construct('application/json');    
		$this->data = $data;
	}
		
	public function getResult(&$cache) {
		global $environment;
		require_once 'Kansas/Environment.php';
		$result = $environment->getStatus() == Environment::ENV_DEVELOPMENT
			? json_encode($this->data, JSON_PRETTY_PRINT)
			: json_encode($this->data);
    	$cache = md5($result);
		return $result;
>>>>>>> origin/master
	}
      
}