<?php
require_once('Kansas/Core/GuidItem/Collection.php');

/**
 * Devuelve la clave con la que se almacenará un objeto System_Guid
 * en una colección Kansas_Core_GuidItem_GuidCollection
 * @param System_Guid $item
 * @return string
 */
trait GuidCollection {
	function getKey(System_Guid $item) {
		return $item->getHex();
	}
}

/**
 * Una colección de elementos System_Guid
 * organizados por su Id
 * @author Marcos
 *
 */
class Kansas_Core_GuidItem_GuidCollection 
	extends Kansas_Core_Collection_Keyed {
	use GuidCollection { getKey as getHex; }
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