<?php

function smarty_modifier_dump($value) {
  if(is_string($value))
    return $value;
  if(is_array($value)) {
    $result = '[';
    foreach ($value as $key => $value)
      $result .= strval($key) . ' => ' . smarty_modifier_dump($value) . ', ';
    return substr($result, 0, -2) . ']';
  }
  if(is_object($value))
    return gettype($value);
    
  return var_export($value, TRUE);
}