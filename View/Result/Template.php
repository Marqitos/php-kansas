<?php
require_once('Kansas/View/Result/String/Abstract.php');

/// Representa una respuesta a una solicitud basada en una plantilla
class Kansas_View_Result_Template
	extends Kansas_View_Result_String_Abstract {
		
	private $_template;
	
	public function __construct($template, $mimeType) {
    parent::__construct($mimeType);
		$this->_template	= $template;
	}
	
	public function getResult(&$noCache) {
    $noCache = true;
		return $this->_template->fetch();
	}
}