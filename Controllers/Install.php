<?php

class Kansas_Controllers_Install
	extends Kansas_Controller_Abstract {

	public function Index($vars = []) {
		$view = $this->createView();
		$view->assign($vars);
		return $this->createResult($view, 'install.default.tpl');
	}
  
}