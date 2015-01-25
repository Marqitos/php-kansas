<?php

class Kansas_Shop_Payment_Type
	extends Kansas_Core_GuidItem_Model {
		
	private static $_payments;
	
	public function getName() {
		return $this->row['Name'];
	}
	
	public static function getPayPalExpress() {
		$payments = self::getPayments();
		return $payments['A47C183B-D22C-4627-8FF2-E242F17260B8'];
	}
	public static function getPayPal() {
		$payments = self::getPayments();
		return $payments['53531B54-99DE-46BE-9588-62B0AC51AD0C'];
	}
	
	protected static function getPayments() {
		if(self::$_payments == null) {
			self::$_payments = new Kansas_Core_GuidItem_Collection();
			self::$_payments->add(new self(array(
				'Id' => 'A47C183B-D22C-4627-8FF2-E242F17260B8',
				'Name' => 'PayPal Express Checkout'
			)));
			self::$_payments->add(new self(array(
				'Id' => '53531B54-99DE-46BE-9588-62B0AC51AD0C',
				'Name' => 'PayPal'
			)));
			
			
		}
		return self::$_payments;
	}
	
}