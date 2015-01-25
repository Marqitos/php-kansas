<?php

abstract class Kansas_Media_Group_Abstract
	extends Kansas_Core_Model
	implements Kansas_Media_Group_Interface {
		
	/* Miembros de ArrayAccess */
	public function offsetExists($offset) { // boolean
		return $this->getSlugCollection()->offsetExists($offset);
	}

	public function offsetGet($offset) { //mixed
		return $this->getSlugCollection()->offsetGet($offset);
	}
	
	public function add($value) {
		$this->getSlugCollection()->add($value);
	}
	public function offsetSet($offset, $value) {
		throw new System_NotSupportedException();
	}
	
	public function offsetUnset($offset) {
		$this->getSlugCollection()->offsetUnset($offset);
	}
	
	/* Miembros de IteratorAggregate */
	public function getIterator() {
		return $this->getSlugCollection()->getIterator();
	}
	
	/* Miembros de Countable */
	public function count() {
		return $this->getSlugCollection()->count();
	}
}