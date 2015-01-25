<?php

/**
 * Devuelve la clave con la que se almacenará un objeto Kansas_Media_Group_Image_TagType
 * en una colección Kansas_Media_Group_Image_TagType_Collection
 * @param Kansas_Media_Group_Image_TagType $item
 * @return string
 */
function Kansas_Media_Group_Image_TagType_Collection_GetKey(Kansas_Media_Group_Image_TagType $item) {
	return $item->getTagType();
}
/**
 * Devuelve la clave para buscar un elemento en una colección Kansas_Media_Group_Image_TagType_Collection
 * @param mixed $offset
 */
function Kansas_Media_Group_Image_TagType_Collection_ParseKey($offset) {
	if($offset instanceof Kansas_Media_Group_Image_TagType)
		return $offset->getTagType();
	if(is_string($offset))
		return $offset;
	return false;
}

/**
 * Una colección de elementos Kansas_Core_Slug_Interface
 * organizados por su Slug
 * @author Marcos
 *
 */
class Kansas_Media_Group_Image_TagType_Collection 
	extends Kansas_Core_Collection_Keyed {
		
	public function __construct(Traversable $array = null) {
		parent::__construct($array);
	}
		
	protected function getKey($item) {			//key
		return Kansas_Media_Group_Image_TagType_Collection_GetKey($item);
	}
	protected function parseKey($offset) { 	//key
		return Kansas_Media_Group_Image_TagType_Collection_ParseKey($offset);
	}
}
