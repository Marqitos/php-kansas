<?php

function smarty_function_title($params, $template) {
  global $application;
  @$title = $params['title'] ?: ($template->getVariable('title')? $template->getVariable('title')->value: false);
  if(!$title) {
    @$page = $params['page'] ?: ($template->getVariable('page')? $template->getVariable('title')->value: false);
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