<?php

function smarty_function_signin($params, $template) {
  global $application, $environment;
  $request = $environment->getRequest();
  @$ru = $params['ru'] ?: $request->getUriString();
  return $application->getModule('Auth')->getRouter()->assemble(['action' => 'signin', 'ru' => $ru]);
}