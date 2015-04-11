<?php

require_once 'Kansas/Helpers/Form.php';

function smarty_function_form_input($params, $template) {
	if(isset($params['attribs']))
		return Kansas_Helpers_Form::input(
			$params['type'],
			$params['id'],
			$params['value'],
			$params['attribs']
		);
	else
		return Kansas_Helpers_Form::input(
			$params['type'],
			$params['id'],
			$params['value']
		);
}