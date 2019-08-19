<?php


use Kansas\View\Result\StringAbstract;

require_once 'Kansas/View/Result/StringAbstract.php';

function smarty_function_dispatch($params, $template) {
  if(count($params) == 1 && isset($params['params']))
    $params = $params['params'];
	global $application;
	$params['requestType']	= 'smarty';
	$result = $application->dispatch($params);
  $noCache;
	if($result instanceof StringAbstract)
		return $result->getResult($noCache);
  return $result->executeResult();
}