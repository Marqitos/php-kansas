<?php

//Representa una linea de pedido.
interface Kansas_Shop_Order_Item_Interface
	extends Kansas_Core_GuidItem_Interface {
	
	public function getOrderId();
	public function getOrder();
	
	public function getProductId();
	public function getProduct();
	
	public function getName();
	public function getDescription();
	public function getUnitPrice();
	
	public function getQuantity();
}