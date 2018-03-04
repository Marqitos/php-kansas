<?php

function smarty_function_signin($params, $template) {
  global $application;
  $templateVars = $template->getTemplateVars();
  $action = isset($params['action'])
    ? $params['action']
    : 'signin';
  $ru = isset($params['ru']) 
    ? $params['ru']
    : '/' . $templateVars['url'];
  return $application->getModule('Auth')->getRouter()->assemble([
    'action' => $action,
    'ru' => $ru
  ]);
}