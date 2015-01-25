<?php

require_once('Kansas/Core/GuidItem/Collection.php');
/**
 * Devuelve la clave con la que se almacenará un objeto Kansas_Shop_Order_Item_Interface
 * en una colección Kansas_Shop_Order_Item_Collection
 * @param Kansas_Shop_Order_Item_Interface $item
 * @return string
 */
function Kansas_Shop_Order_Item_Collection_GetKey(Kansas_Shop_Order_Item_Interface $item) {
	return $item->getProductId()->getHex();
}

class Kansas_Shop_Order_Item_Collection
	extends Kansas_Core_Collection_Keyed {
	
	private $_order;
	
	public function __construct(Kansas_Shop_Order_Interface $order) {
		parent::__construct();
		$this->_order = $order;
	}
	
	protected function getKey($item) {			//key
		return Kansas_Shop_Order_Item_Collection_GetKey($item);
	}
	protected function parseKey($offset) { 	//key
		return Kansas_Core_GuidItem_Collection_ParseKey($offset);
	}
	
	// Miembros de IteratorAggregate
	public function getIterator() {
		$result = array();
		foreach($this->offset as $key => $value)
			if($value->getQuantity() != 0)
				$result[$key] = $value;
		return new ArrayIterator($result);
	}
	public function getAll() {
		return new ArrayIterator($this->offset);
	}
	
	public function createItem($product) {
		$item = new Kansas_Shop_Order_Item(array(
			'Id'				=> System_Guid::getEmpty(),
			//'Order' => $this->_order,
			'Product' 	=> $product,
			'Quantity'	=> 0
		));
		$this->add($item);
		return $item;
	}
	
	public function getItemsCount() {
		$count = 0;
		foreach($this->offset as $item)
			$count += $item->getQuantity();
		return $count;
	}
	public function getItemsPrice() {
		$count = 0;
		foreach($this->offset as $item)
			$count += $item->getPrice();
		return $count;
	}
	public function fixProducts() {
		foreach($this->offset as $item)
			$item->fixProduct();
	}
	
}