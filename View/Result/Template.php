<?php

namespace Kansas\View\Result;

use Kansas\View\Result\StringAbstract;

require_once('Kansas/View/Result/StringAbstract.php');

/// Representa una respuesta a una solicitud basada en una plantilla
class Template extends StringAbstract {
		
	private $template;
	
	public function __construct($template, $mimeType) {
    parent::__construct($mimeType);
		$this->template	= $template;
	}
	
	public function getResult(&$noCache) {
    $noCache = true;
		return $this->template->fetch();
	}
}