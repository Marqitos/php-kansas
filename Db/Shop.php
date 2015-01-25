<?php

class Kansas_Db_Shop
	extends Kansas_Db {

	public function __construct(Zend_Db_Adapter_Abstract $db) {
		parent::__construct($db);
	}
	
	public function getCategories() {
		$sql = 'SELECT * FROM `ShopCategories` ORDER BY `Name`;';
		$rows = $this->db->fetchAll($sql);
		$result = new Kansas_Core_Slug_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Shop_Category($row));
		return $result;
	}

	public function getFamilies($empty = true) {
		$sql = $empty?
			'SELECT `SFM`.*, COUNT(SPR.Id) AS ProductCount FROM `ShopFamilies` AS `SFM` LEFT JOIN `ShopProducts` AS `SPR` ON SFM.Id = SPR.Family GROUP BY `SFM`.Id':
			'SELECT `SFM`.*, COUNT(SPR.Id) AS ProductCount FROM `ShopFamilies` AS `SFM` LEFT JOIN `ShopProducts` AS `SPR` ON SFM.Id = SPR.Family GROUP BY `SFM`.Id HAVING ProductCount != 0;';
		$rows = $this->db->fetchAll($sql);
		$result = new Kansas_Core_Slug_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Shop_Family($row));
		return $result;
	}

	public function getFamilyById(System_Guid $id) {
		$sql = 'SELECT `SFM`.*, COUNT(SPR.Id) AS ProductCount FROM `ShopFamilies` AS `SFM` LEFT JOIN `ShopProducts` AS `SPR` ON SFM.Id = SPR.Family WHERE `SFM`.Id = UNHEX(?) GROUP BY `SFM`.Id';
		$row = $this->db->fetchRow($sql, $id->getHex());
		return $row == null	? null
												:	new Kansas_Shop_Family($row);
	}

	public function getProductsByFamily($family) {
		if($family instanceof System_Guid)
			$id = $family;
		elseif($family instanceof Kansas_Shop_Family_Interface)
			$id = $family->getId();
		else
			throw new System_ArgumentOutOfRangeException('family');
		$sql = 'SELECT * FROM `ShopProducts` WHERE `Family` = UNHEX(?) ORDER BY `Name`;';
		$rows = $this->db->fetchAll($sql, $id->getHex());
		$result = new Kansas_Core_Slug_Collection();
		foreach($rows as $row) {
			$row['Family'] = $family;
			$result->add(new Kansas_Shop_Product($row));
		}
		return $result;
	}
	
	public function getProductById(System_Guid $id) {
		$sql = 'SELECT * FROM `ShopProducts` WHERE `Id` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, $id->getHex());
		return $row == null?
			null:
			new Kansas_Shop_Product($row);
	}
	
	public function getProducts() {
		$sql = 'SELECT * FROM `ShopProducts` ORDER BY `Name`;';
		$rows = $this->db->fetchAll($sql);
		$result = new Kansas_Core_Slug_Collection();
		foreach($rows as $row)
			$result->add(new Kansas_Shop_Product($row));
		return $result;
	}
	
	public function getCurrentOrder(System_Guid $userId, &$count, $force = false) {
		$valid = array('create', 'payment-created', 'payment-authorized', 'payment-void');
		$sql = 'SELECT * FROM `ShopOrders` WHERE `User` = UNHEX(?);';
		$rows = $this->db->fetchAll($sql, $userId->getHex());
		$count = count($rows);
		foreach($rows as $orderRow) {
			$sql = 'SELECT `Type` AS StepType, UNIX_TIMESTAMP(`Date`) AS StepDate, `Param` AS StepParam FROM `ShopOrderSteps` WHERE `Order` = UNHEX(?) ORDER BY StepDate DESC LIMIT 1;';
			$row = array_merge($orderRow, $this->db->fetchRow($sql, bin2hex($orderRow['Id'])));
			if(in_array($row['StepType'], $valid))
				return new Kansas_Shop_Order($row);
		}

		return $force?
			$this->createOrder($userId):
			null;

	}
	
	public function getOrderById(System_Guid $id) {
		$sql = 'SELECT * FROM `ShopOrders` WHERE `Id` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, $id->getHex());
		if($row == null)
			return null;
			
		$sql = 'SELECT `Type` AS StepType, UNIX_TIMESTAMP(`Date`) AS StepDate, `Param` AS StepParam FROM `ShopOrderSteps` WHERE `Order` = UNHEX(?) ORDER BY StepDate DESC LIMIT 1;';
		$result = array_merge($row, $this->db->fetchRow($sql, bin2hex($row['Id'])));
		return new Kansas_Shop_Order($result);
	}

	public function getOrdersByUser(Kansas_User $user, $empty = true) {
//		$sql = 'SELECT SHO.*, SOS.Type AS StepType, MAX(SOS.Date) AS StepDate, SOS.Param AS StepParam FROM `ShopOrders` AS SHO INNER JOIN `ShopOrderSteps` AS SOS ON SHO.Id = SOS.Order WHERE SHO.`Id` = UNHEX(?) GROUP BY SHO.Id ORDER BY StepDate DESC;';
		$sql = $empty?
			'SELECT * FROM `ShopOrders` WHERE `User` = UNHEX(?);':
			'SELECT `SHO`.*, SUM(SOI.Quantity) AS ItemsCount FROM `ShopOrders` AS `SHO` LEFT JOIN `ShopOrderItems` AS `SOI` ON SOI.Order = SHO.Id WHERE `User` = UNHEX(?) HAVING ItemsCount != 0;';

		$rows = $this->db->fetchAll($sql, $user->getId()->getHex());
		$result = new Kansas_Core_GuidItem_Collection();
		foreach($rows as $orderRow) {
			$sql = 'SELECT `Type` AS StepType, UNIX_TIMESTAMP(`Date`) AS StepDate, `Param` AS StepParam FROM `ShopOrderSteps` WHERE `Order` = UNHEX(?) ORDER BY StepDate DESC LIMIT 1;';
			$row = array_merge($orderRow, $this->db->fetchRow($sql, bin2hex($orderRow['Id'])));
			$result->add(new Kansas_Shop_Order($row));
		}
		return $result;
	}

	public function createOrder(System_Guid $userId) {
		$orderId = System_Guid::NewGuid();
		$stepId = System_Guid::NewGuid();
		$this->db->beginTransaction();
		try {
			$sql = 'INSERT INTO `ShopOrders` (`Id`, `User`) VALUES (UNHEX(?), UNHEX(?));';
			$this->db->query($sql, array($orderId->getHex(), $userId->getHex()));
			
			$this->addStep($orderId, array(
				'Type'	=> 'create',
			));
		
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
		}
		return $this->getOrderById($orderId);		
	}
	
	public function saveOrder(array $orderData, Kansas_Shop_Order_Item_Collection $items = null, array $steps = array()) {
		$orderId 	= new System_Guid($orderData['Id']);
		$userId		= new System_Guid($orderData['User']);
		$this->db->beginTransaction();
		try {
			if($orderId == System_Guid::getEmpty()) {
				$orderId = System_Guid::NewGuid();
				$steps[] = array('Type'	=> create);
			}
			$sql = 'REPLACE INTO `ShopOrders` (`Id`, `User`, `BillingAddress`, `BillingId`, `ShippingAddress`, `ShippingMethod`, `ShippingPrice`, `PaymentMethod`, `Payment`, `ItemsCount`, `ItemsPrice`, `TotalPrice`) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), ?, UNHEX(?), UNHEX(?), ?, UNHEX(?), UNHEX(?), ?, ?, ?);';
			$this->db->query($sql, array(
				$orderId->getHex(),
				self::getRowGuid($orderData, 'User'),
				self::getRowNullGuid($orderData, 'BillingAddress'),
				$orderData['BillingId'],
				self::getRowNullGuid($orderData, 'ShippingAddress'),
				self::getRowNullGuid($orderData, 'ShippingMethod'),
				$orderData['ShippingPrice'],
				self::getRowNullGuid($orderData, 'PaymentMethod'),
				self::getRowNullGuid($orderData, 'Payment'),
				$orderData['ItemsCount'],
				$orderData['ItemsPrice'],
				$orderData['TotalPrice']
			));
			if($items != null)
				$this->saveItems($items, $orderId);
			foreach($steps as $step)
				$this->addStep($orderId, $step);	
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
	
	public function getItemsByOrder(Kansas_Shop_Order $order) {
		$result = new Kansas_Shop_Order_Item_Collection($order);
		$sql = 'SELECT * FROM `ShopOrderItems` WHERE `Order` = UNHEX(?)';
		$rows = $this->db->fetchAll($sql, array($order->getId()->getHex()));
		foreach($rows as $row) {
			$row['Order'] = $order;
			$result->add(new Kansas_Shop_Order_Item($row));
		}
		return $result;
	}
	
	protected function saveItems(Kansas_Shop_Order_Item_Collection $items, System_Guid $orderId) {
		foreach($items->getAll() as $item) {
			$itemId = $item->getId();
			if($itemId == System_Guid::getEmpty())
				$itemId = System_Guid::NewGuid();
			$sql = 'REPLACE INTO `ShopOrderItems` (`Id`, `Order`, `Product`, `Quantity`) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), ?);';
			$this->db->query($sql, array(
				$itemId->getHex(),
				$orderId->getHex(),
				$item->getProductId()->getHex(),
				$item->getQuantity()
			));
		}
	}
	
	public function savePayment(array $paymentData) {
		$paymentId 	= new System_Guid($paymentData['Id']);
		$orderId 		= new System_Guid($paymentData['Order']);
		$userId			= new System_Guid($paymentData['User']);
		$methodId		= new System_Guid($paymentData['Method']);
		$this->db->beginTransaction();
		try {
			if(System_Guid::isEmpty($paymentId)) {
				$paymentId = System_Guid::NewGuid();
				$this->addStep($orderId, array(
					'Type'		=> 'payment-' . $paymentData['Status'],
					'Param'		=> $paymentId->getHex(),
					'Date'		=> $paymentData['Date']
				));
			}
			$sql = 'REPLACE INTO `ShopPayments` (`Id`, `Order`, `User`, `Method`, `Date`, `Amount`, `Status`, `ExternalId`, `Token`) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), FROM_UNIXTIME(?), ?, ?, ?, ?);';
			$this->db->query($sql, array(
				$paymentId->getHex(),
				$orderId->getHex(),
				$userId->getHex(),
				$methodId->getHex(),
				self::getRowNowDate($paymentData, 'Date'),
				$paymentData['Amount'],
				$paymentData['Status'],
				$paymentData['ExternalId'],
				$paymentData['Token']
			));
			$this->db->commit();
		} catch(Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
	
	public function getPaymentByToken($token, Kansas_Shop_Payment_Factory_Interface $shopModule) {
		$sql = 'SELECT `Id`, `Order`, `User`, `Method`, UNIX_TIMESTAMP(Date) AS `Date`, `Amount`, `Status`, `ExternalId`, `Token` FROM `ShopPayments` WHERE `Token` = ?;';
		$row = $this->db->fetchRow($sql, $token);
		return $row == null?
			null:
			$shopModule->buildPayment($row);
	}

	public function getPaymentById(System_Guid $id, Kansas_Shop_Payment_Factory_Interface $shopModule) {
		$sql = 'SELECT `Id`, `Order`, `User`, `Method`, UNIX_TIMESTAMP(Date) AS `Date`, `Amount`, `Status`, `ExternalId`, `Token` FROM `ShopPayments` WHERE `Id` = UNHEX(?);';
		$row = $this->db->fetchRow($sql, $id->getHex());
		return $row == null?
			null:
			$shopModule->buildPayment($row);
	}
	
	protected function addStep(System_Guid $orderId, $step) {
		$sql = 'INSERT INTO `ShopOrderSteps` (`Id`, `Order`, `Type`, `Date`, `Param`) VALUES (UNHEX(?), UNHEX(?), ?, FROM_UNIXTIME(?), ?);';
		$this->db->query($sql, array(
			self::getRowNewGuid($step, 'Id'),
			$orderId->getHex(),
			self::getRowNotNull($step, 'Type'),
			self::getRowNowDate($step, 'Date'),
			self::getRowNull(	 $step, 'Param')
		));
	}
	
	protected static function getRowNewGuid(array $row, $key) {
		$id = isset($row[$key])?
			new System_Guid($row[$key]):
			null;
		if(System_Guid::isEmpty($id))
			$id = System_Guid::NewGuid();
		return $id->getHex();
	}
	protected static function getRowNullGuid(array $row, $key) {
		$id = isset($row[$key])?
			new System_Guid($row[$key]):
			null;
		return System_Guid::isEmpty($id)?
			null:
			$id->getHex();
	}
	protected static function getRowGuid(array $row, $key) {
		$id = isset($row[$key])?
			new System_Guid($row[$key]):
			null;
		if(System_Guid::isEmpty($id))
			throw new System_ArgumentNullException($key);
		return $id->getHex();
	}
	protected static function getRowNotNull(array $row, $key) {
		if(!isset($row[$key]))
			throw new System_ArgumentNullException($key);
		return $row[$key];
	}
	protected static function getRowNull(array $row, $key) {
		return isset($row[$key])?
			$row[$key]:
			null;
	}
	protected static function getRowNowDate(array $row, $key) {
		return isset($row[$key])?
			$row[$key]:
			time();
	}
	
}

/*
CREATE TABLE IF NOT EXISTS `ShopCategories` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Slug` (`slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Categorias de productos';

CREATE TABLE IF NOT EXISTS `ShopFamilies` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Parent` binary(16) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`, `Parent`),
  UNIQUE KEY `Slug` (`slug`, `Parent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Familias de productos';

CREATE TABLE IF NOT EXISTS `ShopProductCategories` (
  `Id` binary(16) NOT NULL,
  `Product` binary(16) NOT NULL,
  `Category` binary(16) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Product` (`Product`, `Category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Categorias de cada producto';

CREATE TABLE IF NOT EXISTS `ShopProducts` (
  `Id` binary(16) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `Family` binary(16) NOT NULL,
	`Price` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Slug` (`slug`, `Family`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Productos';

CREATE TABLE IF NOT EXISTS `ShopOrders` (
  `Id`							binary(16)			NOT NULL,
  `User`						binary(16)			NOT NULL,
  `BillingAddress`	binary(16)			DEFAULT NULL,
  `BillingId`				varchar(100)		DEFAULT NULL,
  `ShippingAddress`	binary(16)			DEFAULT NULL,
  `ShippingMethod`	binary(16)			DEFAULT NULL,
	`ShippingPrice`		decimal(10, 2)	DEFAULT NULL,
  `Payment`					binary(16)			DEFAULT NULL,
  `PaymentMethod`		binary(16)			DEFAULT NULL,
  `ItemsCount`			int(11)					NOT NULL DEFAULT 0,
	`ItemsPrice`			decimal(10, 2)	NOT NULL DEFAULT 0,
	`TotalPrice`			decimal(10, 2)	NOT NULL DEFAULT 0,
  PRIMARY KEY 			(`Id`),
  UNIQUE KEY 				`BillingId`			(`BillingId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Pedidos';

CREATE TABLE IF NOT EXISTS `ShopOrderItems` (
  `Id` binary(16) NOT NULL,
  `Order` binary(16) NOT NULL,
  `Product` binary(16) NOT NULL,
  `Package` binary(16) DEFAULT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
	`UnitPrice` decimal(10, 2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Product` (`Order`, `Product`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Lineas de pedido';

CREATE TABLE IF NOT EXISTS `ShopPackages` (
  `Id` binary(16) NOT NULL,
  `Order` binary(16) NOT NULL,
  `Carrier` varchar(100) NOT NULL,
  `Tracking` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Paquetes enviados';

CREATE TABLE IF NOT EXISTS `ShopOrderSteps` (
  `Id` binary(16) NOT NULL,
  `Order` binary(16) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Date` datetime NOT NULL,
  `Param` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Type` (`Order`, `Type`, `Param`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Acciones de pedidos';

CREATE TABLE IF NOT EXISTS `ShopPayments` (
  `Id` binary(16) NOT NULL,
  `Order` binary(16) NOT NULL,
  `User` binary(16) NOT NULL,
  `Method` binary(16) NOT NULL,
  `Date` datetime NOT NULL,
	`Amount` decimal(10, 2) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `ExternalId` varchar(200) DEFAULT NULL,
  `Token` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Payment` (`Method`, `ExternalId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Pagos';


*/