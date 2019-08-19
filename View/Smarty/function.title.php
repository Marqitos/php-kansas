<?php

use function Kansas\View\Smarty\getTemplateValue;

require_once 'Smarty/sysplugins/smarty_internal_template.php';

function smarty_function_title(array $params, Smarty_Internal_Template $template) {
	require_once 'Kansas/View/Smarty/getTemplateValue.php';
	global $application;
	if((!$title = getTemplateValue($params, $template, 'title')) &&
		$page = getTemplateValue($params, $template, 'page')) {
		$title = $page->getTitle();
	}
	$titleBuilder = $application->createTitle();
	if($title) {
		foreach ((array)$title as $titlePart)
			$titleBuilder->attach($titlePart);
	}
	return $titleBuilder->__toString();
}