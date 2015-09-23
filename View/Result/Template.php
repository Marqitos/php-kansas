<?php
require_once('Kansas/View/Result/String/Abstract.php');

/// Representa una respuesta a una solicitud basada en una plantilla
class Kansas_View_Result_Template
	extends Kansas_View_Result_String_Abstract {
		
	private $_template;
	private $_view;
	
	public function __construct(Zend_View_Interface $view, $template, $mimeType) {
    parent::__construct($mimeType);
		$this->_view			= $view;
		$this->_template	= $template;
	}
	
	public function getView() {
		return $this->_view;
	}
	
	public function getResult(&$noCache) {
    $noCache = true;
		return $this->_view->render($this->_template);
	}
}