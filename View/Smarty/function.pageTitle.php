<?php

function smarty_function_pageTitle(array $params, Smarty_Internal_Template $template) {
  $templateVars = $template->getTemplateVars();
  if(isset($templateVars['pageTitle'])) {
    return $templateVars['pageTitle'];
  }
  if(isset($templateVars['title'])) {
    if(is_array($templateVars['title']) &&
       isset($templateVars['title'][0])) {
      return $templateVars['title'][0];
    } else
      return $templateVars['title'];
  }
  if(isset($templateVars['page']))
    return $templateVars['page']->getTitle();
  return false;
}