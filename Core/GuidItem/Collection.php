<?php
/**
 * Devuelve la clave con la que se almacenará un objeto Kansas_Core_GuidItem_Interface
 * en una colección Kansas_Core_GuidItem_Collection
 * @param Kansas_Core_GuidItem_Interface $item
 * @return string
 */
trait GuidItem_GetKey {
	function getKey(Kansas_Core_GuidItem_Interface $item) {
		return $item->getId()->getHex();
	}
}

/**
 * Devuelve la clave para buscar un elemento en una colección Kansas_Core_GuidItem_Collection
 * @param mixed $offset
 */
trait GuidItem_ParseKey {
	function parseKey($offset) {
		if($offset instanceof System_Guid)
			return $offset->getHex();
		if($offset instanceof Kansas_Core_GuidItem_Interface)
			return $offset->getId()->getHex();
		if(is_string($offset))
			return $offset;
		return false;
	}
}
/**
 * Una colección de elementos Kansas_Core_GuidItem_Interface
 * organizados por su Id
 * @author Marcos
 *
 */
class Kansas_Core_GuidItem_Collection 
	extends Kansas_Core_Collection_Keyed {
	use GuidItem_GetKey { getKey as getHex; }
	use GuidItem_ParseKey { parseKey as parseGuid; }
		
	public function __construct(Traversable $array = null) {
		parent::__construct($array);
	}
		
	protected function getKey($item) {			//key
		return $this->getHex($item);
	}
	protected function parseKey($offset) { 	//key
		return $this->parseGuid($offset);
	}
}