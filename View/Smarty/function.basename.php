<?php

function smarty_function_basename($params, $template) {
  if(count($params) == 1 && isset($params['params']))
		$params = $params['params'];
	$filename = $params['filename'];
  return basename($filename);
}