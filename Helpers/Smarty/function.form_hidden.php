<?php

require_once 'Kansas/Helpers/Form.php';

function smarty_function_form_hidden($params, $template) {
	return Kansas_Helpers_Form::hidden(
		$params['id'],
		$params['value']
	);
}