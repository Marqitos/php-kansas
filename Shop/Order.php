<?php

class Kansas_Shop_Order
	extends Kansas_Core_Guiditem_Model
	implements Kansas_Shop_Order_Interface {
		
	const STATUS_EMPTY = 				0x00;
	const STATUS_SHOPPING = 		0x01;
	const STATUS_PAID =					0x02;
	const STATUS_PROCESS =			0x03;
	const STATUS_DELIVER =			0x05;
	const STATUS_NOT_DELIVER =	0x06;
	const STATUS_RECEIVED =			0x07;

	private $_user;
	private $_userId;
	private $_items;
	private $_billingAddress;
	private $_billingAddressId;
	private $_shippingAddress;
	private $_shippingAddressId;
	private $_shippingMethod;
	private $_shippingMethodId;
	private $_payment;
	private $_paymentId;
	private $_steps = array();
	
	protected function init() {
		parent::init();
		if(!empty($this->row['User'])) {
			if($this->row['User'] instanceof Kansas_User_Interface) {
				$this->_user		= $this->row['User'];
				$this->_userId	= $this->_user->getId();
			} else
				$this->_userId	= new System_Guid($this->row['User']);
		}
		if(!empty($this->row['ShippingAddress']))
			if($this->row['ShippingAddress'] instanceof Kansas_Core_GuidItem_Interface)
				$this->setShippingAddress($this->row['ShippingAddress']);
			else
				$this->_shippingAddressId = new System_Guid($this->row['ShippingAddress']);
		if(!empty($this->row['ShippingMethod']))
			if($this->row['ShippingMethod'] instanceof Kansas_Shop_Ship_Method_Interface)
				$this->setShippingMethod($this->row['ShippingMethod']);
			else
				$this->_shippingMethodId = new System_Guid($this->row['ShippingMethod']);
		if(!empty($this->row['Payment']))
			if($this->row['Payment'] instanceof Kansas_Shop_Payment_Interface)
				$this->setPayment($this->row['Payment']);
			else
				$this->_paymentId = new System_Guid($this->row['Payment']);
	}
	

	public function getUserId() {
		return $this->_userId;
	}
	public function getUser() {
		if($this->_user == null && $this->_userId != null)
			$this->_user = Kansas_Application::getInstance()->getProvider('Users')->getById($this->_userId);
		
		return $this->_user;
	}
	
	public function getItems() {
		if($this->_items == null) {
			if($this->getId() == System_Guid::getEmpty())
				$this->_items = new Kansas_Shop_Order_Item_Collection($this);
			else 
				$this->_items = Kansas_Application::getInstance()->getProvider('Shop')->getItemsByOrder($this); // Cargar desde base de datos
		}
		return $this->_items;
	}
	public function getBillingAddress() {
		
		return $this->_billingAddress;
	}
	public function getBillingAddressId() {
		
		return $this->_billingAddressId;
	}

	public function getShippingAddress() {
		if($this->_shippingAddress == null) {
			$shop = Kansas_Application::getInstance()->getModule('Shop')->getShop();
			$this->_shippingAddress = $shop->getShippingAddress($this->getShippingAddressId(), $this->getUserId());
		}
		return $this->_shippingAddress;
	}
	public function getShippingAddressId() {
		return $this->_shippingAddressId;
	}
	public function setShippingAddress(Kansas_Core_GuidItem_Interface $shippingAddress) {
		$this->_shippingAddress		= $shippingAddress;

		$this->_shippingAddressId	= $shippingAddress->getId();
		$this->row['ShippingAddress']	= $shippingAddress->getId()->getHex();
		$this->updateShippingMethod();
	}
	public function setShippingAddressId(System_Guid $shippingAddressId) {
		if($this->_shippingAddress != null && $this->_shippingAddress->getId() != $shippingAddressId)
			$this->_shippingAddress = null;
		$this->_shippingAddressId	= $shippingAddressId;
		$this->row['ShippingAddress']	= $shippingAddressId->getHex();
		$this->updateShippingMethod();
	}
	
	public function getStatus() {
		return $this->row['StepType'];
	}
	
	public function getShippingMethod() {
		if($this->_shippingMethod == null) {
			$shop = Kansas_Application::getInstance()->getModule('Shop')->getShop();
			$this->_shippingMethod = $shop->getShippingMethod($this->getShippingMethodId());
		}
		return $this->_shippingMethod;
	}
	public function getShippingMethodId() {
		return $this->_shippingMethodId;
	}
	public function setShippingMethod(Kansas_ShopKansas_Shop_Ship_Method_Interface $shippingMethod = null) {
		$this->_shippingMethod				= $shippingMethod;
		$this->_shippingMethodId			= $shippingMethod != null?
			$shippingMethod->getId():
			null;
		$this->row['ShippingMethod']	= $shippingMethod != null?
			$shippingMethod->getId()->getHex():
			null;
		$this->updateShippingPrice();
	}
	public function setShippingMethodId(System_Guid $shippingMethodId = null) {
		$this->_shippingMethod				= null;
		$this->_shippingMethodId			= $shippingMethodId;
		$this->row['ShippingMethod']	= $shippingMethodId != null?
			$shippingMethodId->getHex():
			null;
		$this->updateShippingPrice();
	}
	
	public function getPayment() {
		if($this->_payment == null && !System_Guid::isEmpty($this->getPaymentId()))
			$this->_payment = Kansas_Application::getInstance()->getModule('Shop')->getShop()->getPaymentById($this->getPaymentId());
		return $this->_payment;
	}
	public function getPaymentId() {
		return $this->_paymentId;
	}
	public function setPayment(Kansas_Shop_Payment_Interface $payment) {
		$this->_payment = $payment;
		$this->_paymentId = $payment->getId();
		$this->row['Payment'] = $payment->getId()->getHex();
	}

		
	public function getItemsCount() {
		return intval($this->row['ItemsCount']);
	}
	public function getItemsPrice() {
		return floatval($this->row['ItemsPrice']);
	}
	public function getHtmlItemsPrice() {
		return number_format($this->getItemsPrice(), 2) . ' &euro;';
	}
	
	public function getTotalPrice() {
		return floatval($this->row['TotalPrice']);
	}
	public function getHtmlTotalPrice() {
		return number_format($this->getTotalPrice(), 2) . ' &euro;';
	}
	
	public function getStatusText() {
		$status = '';
		switch($this->getStatus()) {
			case 'create':
				return 'Pedido actual';
			case 'payment-confirmed':
				$status = 'Pagado';
				break;
			
			
			default:
				$status = $this->getStatus();
				break;
		}
		return $status . ' (' . date('d-m-Y', intval($this->row['StepDate'])) . ')';
	}
	
	// Valida si el metodo d envio es compatible con la direccion de envio.
	public function updateShippingMethod() {
		if(System_Guid::isEmpty($this->getShippingMethodId()))
			return;
		$shop = Kansas_Application::getInstance()->getModule('Shop')->getShop();
		$compatibleMethods = $shop->getShipMethodsByAddress($this->getShippingAddress());
		if(!isset($compatibleMethods[$this->getShippingMethodId()]))
			$this->setShippingMethod(null);
	}

	public function updateShippingPrice() {
		$this->row['ShippingPrice'] = System_Guid::isEmpty($this->getShippingMethodId())?
			0: $this->getShippingMethod()->getPrice();
		$this->updateTotalPrice();
	}
	
	public function updateTotalPrice() {
		$this->row['TotalPrice'] = $this->getItemsCount() == 0?
			0:
			$this->row['ShippingPrice'] + $this->row['ItemsPrice'];
	}
	
	public function updateItems(){
		$this->row['ItemsCount'] = $this->getItems()->getItemsCount();
		$this->row['ItemsPrice'] = $this->getItems()->getItemsPrice();
		$this->updateTotalPrice();
	}
	
	public static function create() {
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()) {
			$user = $auth->getIdentity();
			$order = Kansas_Application::getInstance()->getProvider('Shop')->createOrder($user->getId());
		} else {
			$order = new self(array(
				'Id'							=> System_Guid::getEmpty(),
				'User'						=> System_Guid::getEmpty(),
				'BillingAddress'	=> null,
				'ShippingAddress'	=> null,
				'ShippingMethod'	=> null,
				'ItemsCount'			=> 0,
				'ShippingPrice'		=> 0,
				'ItemsPrice'			=> 0,
				'TotalPrice'			=> 0,
				'StepType'				=> 'empty'
			));
		}
		return $order;
	}
	
	public function addItem(System_Guid $productId, $quantity = 1) { 
		$item = $this->getItem($productId);
			$item->setQuantity($item->getQuantity() + $quantity);
		$this->updateItems();
	}
	
	public function setItem(System_Guid $productId, $quantity) {
		$this->getItem($productId)->setQuantity($quantity);
		$this->updateItems();
	}
	public function removeItem(System_Guid $productId) {
		$this->setItem($productId, 0);
	}
	
	public function getItem(System_Guid $productId) {
		$items = $this->getItems();
		if(isset($items[$productId]))
			return $items[$productId];

		$product = Kansas_Application::getInstance()->getProvider('Shop')->getProductById($productId);
		return $items->createItem($product);
	}
	
	public function save() {
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()) {
			if($this->getUserId() == System_Guid::getEmpty())
				$this->row['User'] = $auth->getIdentity()->getId();
			Kansas_Application::getInstance()->getProvider('Shop')->saveOrder($this->row, $this->_items, $this->_steps);
		} elseif(Zend_Session::namespaceIsset('Shop')) {
			$shopSession = new Zend_Session_Namespace('Shop');
			Kansas_Application::getInstance()->getProvider('Model')->updateModel($shopSession->order, $this);
		}
		
	}
	
	public function merge(Kansas_Shop_Order_Interface $order) {
		// Agregar productos comprados
		foreach($order->getItems() as $item)
			$this->addItem($item->getProductId(), $item->getQuantity());
		
		// Mezclar direcciones
		
	}
	
	/**
	 * Devuelve el carro de compra actual.
	 *
	 * @var int:&$count Devuelve ...
	 * @var bool:$force Indica si se debe crear un carro de compra en caso de que no exista.
	 */
	public static function getCurrent(&$count, $force = false) {
		$auth = Zend_Auth::getInstance();
		$application = Kansas_Application::getInstance();
		$order = null;
		// Obtener de Models
		if(Zend_Session::namespaceIsset('Shop')) {
			$shopSession = new Zend_Session_Namespace('Shop');
			if(isset($shopSession->order))
				$order = $application->getProvider('Model')->getModel($shopSession->order);
		}
		if($auth->hasIdentity()) {
			// Obtener de ShopOrders
			$user = $auth->getIdentity();
			$shopProvider = $application->getProvider('Shop');
			if($order != null) {
				$sessionOrder = $order;
				Zend_Session::namespaceUnset('Shop');
			}
			$order = $shopProvider->getCurrentOrder($user->getId(), $count);
			if(isset($sessionOrder)) {
				if($order == null)
					$order = $sessionOrder;
				else
					$order->merge($sessionOrder);				
				$order->save();
			}

		}
		if($order != null)
			return $force || $order->getItemsCount() > 0 ?
				$order:
				false;
		if($force) {
			// Crear carro de compra
			if($auth->hasIdentity()) {
				// Almacenar en ShopOrders
				$user = $auth->getIdentity();
				$order = $application->getProvider('Shop')->createOrder($user->getId());
			} else {
				// Almacenar en models
				$order = Kansas_Shop_Order::create();
				$shopSession = new Zend_Session_Namespace('Shop');
				$shopSession->order = $application->getProvider('model')->createModel($order);
			}
			return $order;
		}
		return false;
	}
	
	public function createPayment($paymentMethod) {
		// Solo se soporta la creacion de un pago, cuando no hay un pago, o hay uno sin confirmar o cancelado.
		if(array_search($this->row['StepType'], array(
				'create',
				'payment-created',
				'payment-authorized',
				'payment-void')) === false)
			throw new System_NotSupportedException('El pago ya ha sido realizado');		
			
		$shop = Kansas_Application::getInstance()->getModule('Shop')->getShop();
		$payment = $this->getPayment();
		if ($payment != null && 
				array_search($payment->getStatus(), array(
					'created',
					'authorized'))) {
			$paymentMethod	= $shop->getPaymentMethod($paymentMethod);
			if($payment->getStatus()	== 'created') {
				if($payment->getMethodId() == $paymentMethod->getId())
					return $payment;
				else {
					$payment->void();
					$payment->save();
				}
			} else {
				throw new System_NotImplementedException();
				
			}
		}
		
		$this->getItems()->fixProducts();
		$payment = $shop->createPayment($this, $paymentMethod);
		$this->setPayment($payment);
		return $payment;
	}
	
	public function addStep($type, $date = null, $param = null) {
		$this->_steps[] = array(
			'Type'		=> $type,
			'Param'		=> $param,
			'Date'		=> $date
		);
		$this->row['StepType']	= $type;
		$this->row['StepParam'] = $param;
		$this->row['StepDate'] 	= $date;
	}
	
	public function getView() {
		switch($this->getStatus()) {
			case 'create':
			case 'payment-void':
				return 'edit';
			case 'payment-created':
			case 'payment-authorized':
				return 'fixed';
			
		}
	}
	
	/* Metodos de Serializable */
	public function serialize() {
		if($this->_items != null) {
			$items = array();
			foreach($this->_items as $item)
				$items[$item->getProductId()->getHex()] = $item->getQuantity();
			$this->row['_items'] = $items;
		}
		return parent::serialize();
	}
	
	public function unserialize($serialized) {
		parent::unserialize($serialized);
		if(isset($this->row['_items'])) {
			foreach($this->row['_items'] as $key => $value)
				$this->setItem(new System_Guid($key), $value);
			unset($this->row['_items']);
		}
	}
		
}