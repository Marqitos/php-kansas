<?php

abstract class Kansas_Application_Module_Abstract 
  implements Kansas_Application_Module_Interface {
    
  private $_options;
  private $_config;

  protected function __construct($options) {
    $this->_config = $options;
  }

  public function getOptions($key = NULL){
    if($this->_options == null) {
      $this->_options = array_replace_recursive(
        $this->getDefaultOptions(),
        $this->_config
      );
    }
    if($key == null)
      return $this->_options;
    elseif(is_string($key))
      return $this->_options[$key];
    elseif(is_array($key)) {
      $value = $this->_options;
      foreach($key as $search)
        $value = $value[$search];
      return $value;
    } else 
      throw new System_ArgumentOutOfRangeException();
  }

  public function setOptions($options) {
    $this->_config = $options;
    $this->_options = null;
  }
}