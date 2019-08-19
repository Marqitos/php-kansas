<?php
use function Kansas\View\Smarty\getTemplateValue;

require_once 'Smarty/sysplugins/smarty_internal_template.php';

function smarty_function_signin(array $params, Smarty_Internal_Template $template) {
	global $application;
  $templateVars = $template->getTemplateVars();
  $action = isset($params['action'])
    ? $params['action']
    : 'signin';
  $ru = isset($params['ru']) 
    ? $params['ru']
    : '/' . $templateVars['url'];
  return $application->getPlugin('Auth')->getRouter()->assemble([
    'action' => $action,
    'ru' => $ru
  ]);
}