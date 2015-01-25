<?php

require_once('Kansas/Core/GuidItem/Collection.php');
/**
 * Una colección de elementos Kansas_Places_Region_Interface
 * organizados por su Id
 * @author Marcos
 *
 */
class Kansas_Places_Region_Collection
	extends Kansas_Core_Collection_Keyed {
		
	private $_default;
	
	public function __construct(Traversable $array = null) {
		parent::__construct($array);
	}
		
	protected function getKey($item) {			//key
		return Kansas_Core_GuidItem_Collection_GetKey($item);
	}
	protected function parseKey($offset) { 	//key
		return Kansas_Core_GuidItem_Collection_ParseKey($offset);
	}
	
	public function getDefault() {
		return $this[$this->_default];
	}
	
	public function getDefaultId() {
		return $this->_default;
	}
	
	public function setDefault($item) {
		$key = $this->getKey($item);
		if(!isset($this[$key]))
			$this->add($item);
		$this->_default = $key;
	}
	
	public function setDefaultId($key) {
		$this->_default = $this->parseKey($key);
	}
		
}
