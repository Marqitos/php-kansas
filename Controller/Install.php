<?php

class Kansas_Controller_Install
	extends Kansas_Controller_Abstract {

	public function Index($vars = []) {
		return $this->createViewResult('install.default.tpl');
	}
  
}