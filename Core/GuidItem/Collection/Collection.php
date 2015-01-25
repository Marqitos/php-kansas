<?php
require_once('Kansas/Core/Collection/Keyed.php');
require_once('Kansas/Core/GuidItem/Collection.php');

class Kansas_Data_Collection
	extends Kansas_Core_Collection_Keyed {

	public function __construct(Traversable $array = null) {
		parent::__construct($array);
	}
		
	protected function getKey($item) {			//key
		$id = new System_Guid($item['Id']);
		return $id->getHex();
	}
	protected function parseKey($offset) { 	//key
		return Kansas_Core_GuidItem_Collection_ParseKey($offset);
	}

}