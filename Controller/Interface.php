<?php
use Zend\Http\Request;

interface Kansas_Controller_Interface {
	public function init(Request $request, array $params);
}