<?php

class Kansas_Core_Hierarchy_ParentIterator
	implements Iterator {
	
	private $_first;
	private $_current;
	private $_level;

	public function __construct($first) {
		$this->_first = $first;
		$this->rewind();
	}

	public function current () {
		return $this->_current;
	}
	public function key () {
		return $this->_level;
	}
	public function next () {
		$this->_current = $this->_current->getParent();
		$this->_level++;
	}
	public function rewind () {
		$this->_current = $this->_first;
		$this->_level = 0;
	}
	public function valid () {
		return $this->_current != null;
	}
}