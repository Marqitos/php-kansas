<?php

require_once 'Kansas/View/Result/String/Abstract.php';

function smarty_function_dispatch($params, $template) {
  if(count($params) == 1 && isset($params['params']))
    $params = $params['params'];
	global $application;
	$params['requestType']	= 'smarty';
	$result = $application->dispatch($params);
  $noCache;
	if($result instanceof Kansas_View_Result_String_Abstract)
		return $result->getResult($noCache);
  return $result->executeResult();
}