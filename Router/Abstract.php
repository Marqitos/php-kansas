<?php

abstract class Kansas_Router_Abstract
	implements Kansas_Router_Interface, Serializable {
	
	protected $options;

	protected function __construct(Zend_Config $options) {
		$this->options = $this->getDefaultOptions();
		$this->options->merge($options);
	}
	
	public function setOptions(Zend_Config $options) {
		$this->options->merge($options);
	}
	
	protected function getDefaultOptions() {
		return new Zend_Config([
			'basePath'	=> '',
			'params'		=> []
		], true);
	}

	protected function getDefaultParams() {
		return $this->options->params->toArray();
	}
	
	/* Miembros de Kansas_Router_Interface */
	public function getBasePath() {
		return $this->options->basePath;
	}
	public function setBasePath($basePath) {
		$this->options->basePath = trim((string) $basePath, '/');
	}
	
  public function assemble($data = [], $reset = false, $encode = false) {
		return isset($data['basepath']) ?
			$data['basepath']:
			'/' . $this->getBasePath();
	}
	
	/* Miembros de Serializable */
	public function serialize() {
		return serialize($this->options->toArray());
	}
	
	public function unserialize($serialized) {
		$this->options = new Zend_Config(unserialize($serialized), true);
	}
	
}