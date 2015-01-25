<?php

require_once 'Kansas/Helpers.php';
require_once 'Kansas/Helpers/Javascript.php';

function smarty_function_javascript($params, $template) {
	$javascript = Kansas_Helpers::getHelper('javascript');
	return $javascript->render();
}