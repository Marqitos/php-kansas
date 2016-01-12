<?php
// Implementa las funcionalidades básicas de Kansas_Module_Interface y la carga de la configuración por defecto desde un archivo .ini
abstract class Kansas_Module_Abstract 
  implements Kansas_Module_Interface {
  
  /// Campos
  private $_options;
  private $_config;
  private $_default;

  /// Constructor
  protected function __construct($options, $default) {
    $this->_config = $options;
    $this->_default = $default;
  }

  /// Miembros de Kansas_Module_Interface
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

  // Obtiene los valores de configuración
  public function getOptions($key = null, $default = null){
    if($this->_options == null) {
      $this->_options = array_replace_recursive(
        $this->getDefaultOptions(),
        $this->_config
      );
    }
    if($key == null)
      return $this->_options;
    elseif(is_string($key) && isset($this->_options[$key]))
      return $this->_options[$key];
    elseif(is_array($key)) {
      $value = $this->_options;
      foreach($key as $search)
        if(isset($value[$search]))
          $value = $value[$search];
        else
          return $default;
      return $value;
    } else
      return $default;
  }
  
  // Establece los valores de configuración
  public function setOptions(array $options) {
    $this->_config = $options;
    $this->_options = null;
  }
}