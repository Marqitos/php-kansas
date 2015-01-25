<?php

class Kansas_View_Result_Page
	extends Kansas_View_Result_Template {

	private $_page;
	
	public function __construct(Zend_View_Abstract $view, $template, Kansas_View_Page_Interface $page) {
		parent::__construct($view, $template);
		$this->_page = $page;
	}
	
	/* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
	 */
	public function executeResult() {
		parent::ExecuteResult();
	}
	
	public function getPage() {
		return $this->_page;
	}
	
}