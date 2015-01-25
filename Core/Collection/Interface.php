<?php

interface Kansas_Core_Collection_Interface 
	extends ArrayAccess, IteratorAggregate, Countable {
	/**
	 * Agrega un nuevo elemento.
	 * @param mixed $item Elemento a añadir
	 */
	public function add($item);
}

function Kansas_Core_Collection_first($array, $default = null) {
	$iterator = Kansas_Core_Collection_getIterator($array);
	$iterator->rewind();
	return $iterator->valid()?
		$iterator->current():
		$default;
}
function Kansas_Core_Collection_getIterator($array) {
	if(is_array($array))
		return new ArrayIterator($array);
	elseif($array instanceof IteratorAggregate)
		return $array->getIterator();
	elseif($array instanceof Iterator)
		return $array;
	else
		throw new System_ArgumentOutOfRangeException('array', 'Se esperaba un iterador', $array);
}