<?php

abstract class Kansas_Application_Module_Abstract 
  implements Kansas_Application_Module_Interface {
    
  private $_options;
  private $_config;
  private $_default;

  protected function __construct($options, $default) {
    $this->_config = $options;
    $this->_default = $default;
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
  
  // Obtiene la configuración por defecto, a partir de archivos .ini
  public function getDefaultOptions() {
    if(is_string($this->_default)) {
      global $environment;
      $pathInfo = pathinfo($this->_default);
      return Kansas_Config::ParseIni(
        $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.ini',
        ['nestSeparator' => ':'],
        $environment->getStatus()
      );
    } elseif(is_array($this->_default))
      return $this->_default;
    else
      return [];
  }

  // Establece los valores de configuración
  public function setOptions(array $options) {
    $this->_config = $options;
    $this->_options = null;
  }
}