<?php

function smarty_function_title(array $params, Smarty_Internal_Template $template) {
  global $application;
  $title = $params['title'] ?: ($template->getTemplateVars('title')? $template->getTemplateVars('title')->value: false);
  if(!$title) {
    @$page = $params['page'] ?: ($template->getTemplateVars('page')? $template->getTemplateVars('title')->value: false);
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