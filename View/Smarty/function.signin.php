<?php

function smarty_function_signin($params, $template) {
  global $application, $environment;
  @$ru = $params['ru'] ?: $environment->getRequest()->getUriString();
  return $application->getModule('Auth')->getRouter()->assemble(['action' => 'signin', 'ru' => $ru]);
}