<?php

function smarty_function_title(array $params, Smarty_Internal_Template $template) {
  global $application;
  $templateVars = $template->getTemplateVars();
  $title = isset($params['title'])
    ? $params['title']
    : (isset($templateVars['title'])
      ? $templateVars['title']
      : false
    );
  if(!$title) {
    $page = isset($params['page'])
      ? $params['page']
      : (isset($templateVars['page'])
        ? $templateVars['page']
        : false
      );
    if($page)
      $title = $page->getTitle();
  }
  $titleBuilder = $application->createTitle();
  if($title) {
    foreach ((array)$title as $titlePart)
      $titleBuilder->attach($titlePart);
  }
  return $titleBuilder->__toString();
}