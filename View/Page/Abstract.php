<?php

abstract class Kansas_View_Page_Abstract
	implements Kansas_View_Page_Interface {
		
  private $_helpers;
  private $_javascript;
  
  private $_namespaces = [];
  private $_metadata = [];
  
	private $_router;
	private $_parent;
	
  public function __construct(Kansas_View_Page_Interface $parent = null, Kansas_Router_Interface $router = null) {
		$this->_parent			= $parent;
		$this->_router			= $router;
  }

  // Helpers
  public function getHelper($name) {
  	return Kansas_Helpers::getHelper($name);
  }
  
  // Metadata
  public function getMetadata() {
  	return $this->_metadata;
  }
  
  public function addMetadata($name, $content = '', $type = 'name')	{
  	// Since we allow the data to be passes as a string, a simple array
  	// or a multidimensional one, we need to do a little prepping.
  	if ( ! is_array($name))
  		$name = [[$type => $name, 'content' => $content]];
  	else // Turn single array into multidimensional
  		if (isset($name['content']))
  			$name = [$name];
  
  	foreach ($name as $meta)
  		$this->_metadata[] = $meta;
  }
  
  // Namespaces
  
  public function addNamespace($preffix, $name) {
    $this->_namespaces[$preffix] = $name;
  }
  public function removeNamespace($preffix) {
    unset($this->_namespaces[$preffix]);
  }
  public function getNamespaces() {
    return $this->_namespaces;
  }

	public function getRouter() {
		return $this->_router;
	}
	public function getParent() {
		return $this->_parent;
	}

}	