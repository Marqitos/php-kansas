<?php

abstract class Kansas_Router_Abstract
	implements Kansas_Router_Interface, Serializable {
	
	protected $options;

	protected function __construct() {
    $this->options = $this->getDefaultOptions();
  }
	
	public function setOptions(array $options) {
		$this->options = array_replace_recursive($this->options, $options);
	}
	
	protected function getDefaultParams() {
		return $this->options['params'];
	}
  
  protected function getDefaultOptions() {
    return [
  		'basePath'	=> '',
  		'params'		=> []
  	];
  }
	
	/* Miembros de Kansas_Router_Interface */
	public function getBasePath() {
		return $this->options['basePath'];
	}
	public function setBasePath($basePath) {
		$this->options['basePath'] = trim((string) $basePath, '/');
	}
	
  public function assemble($data = [], $reset = false, $encode = false) {
		return isset($data['basepath']) ?
			$data['basepath']:
			'/' . $this->getBasePath();
	}
	
	/* Miembros de Serializable */
	public function serialize() {
		return serialize($this->options);
	}
	
	public function unserialize($serialized) {
		$this->options = unserialize($serialized);
	}
	
}