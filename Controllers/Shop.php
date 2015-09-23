<?php

class Kansas_Controllers_Shop
	extends Kansas_Controller_Abstract {
		
	protected function createView() {
		$view = parent::createView();
		$count = 0;
		$view->assign('cart', Kansas_Shop_Order::getCurrent($count, true));
		$view->assign('order_count', $count);
		return $view; 
	}
	
	// Muestra la portada de la tienda
	public function index() {
		global $application;
		$router = $this->getParam('router');
		$show		= (int)$this->getParam('show', Kansas_Router_Shop::SHOW_ALL);
		$families = $application->getProvider('shop')->getFamilies(false);
		if(count($families) == 1) {
			$family = Kansas_Core_Collection_first($families);
			$url		= $router->assemble(array(
				'action' => 'viewFamily',
				'family' => Kansas_Core_Collection_first($families)
			));
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl($url);
			return $result;
		} else {
			$view = $this->createView();
			$view->assign('show', $show);
			$view->assign('toggleCategory', $router->assemble(array('show' => $show ^ Kansas_Router_Shop::SHOW_CATEGORY)));
			if(($show & Kansas_Router_Shop::SHOW_CATEGORY) != 0) {
				$categories = $application->getProvider('shop')->getCategories();
				$view->assign('categories', 		$categories);
				$view->assign('urlCategories',	$router->assemble(array(
					'action'	=> 'viewCategories',
					'categories'	=> $categories
				)));
			}
			$view->assign('toggleFamily', $router->assemble(array('show' => $show ^ Kansas_Router_Shop::SHOW_FAMILY)));
			if(($show & Kansas_Router_Shop::SHOW_FAMILY) != 0) {
				$view->assign('families', 		$families);
				$view->assign('urlFamilies',	$router->assemble(array(
					'action'	=> 'viewFamilies',
					'families'	=> $families
				)));
			}
			
			return $this->createResult($view, 'page.shop-index.tpl');
		}
	}
		
	/// Muestra la pagina de gategorias	
	public function categories() {
		global $application;
		$view = $this->createView();
		$router = $this->getParam('router');
		$view->assign('categories', $application->getProvider('shop')->getCategories());

		return $this->createResult($view, 'page.shop-categories.tpl');
	}
	
	// Muestra la pagina de una familia de productos
	public function family() {
		global $application;
		$view = $this->createView();
		$router = $this->getParam('router');
		$family	= $this->getParam('family');
		$view->setCacheId('shop-family-' . $family->getId()->__toString());
		if(!$this->isCached($view, 'page.shop-family.tpl')) {
			$view->assign('products', $family->getProducts());
			$view->assign('productThumbnails', $router->assemble(array(
				'action' => 'productThumbnails',
				'products'	=> $family->getProducts()
			)));
			$view->assign('viewProducts', $router->assemble(array(
				'action' => 'viewProducts',
				'products'	=> $family->getProducts()
			)));
		}
		
		return $this->createResult($view, 'page.shop-family.tpl');
		
	}
	
	/// Muestra la pagina de un producto
	public function product() {
		$view = $this->createView();
		$product = $this->getParam('product');
		$imageId = $this->getParam('img');
		$view->setCacheId('shop-product-' . $product->getId()->__toString() . '-' . $imageId);
		if(!$this->isCached($view, 'page.shop-product.tpl')) {
			global $application;
			$router = $this->getParam('router');
			$view->assign('family', $product->getFamily());
			$families = array(
				$product->getFamily()
			);
			$view->assign('urlFamilies',	$router->assemble(array(
				'action'		=> 'viewFamilies',
				'families'	=> $families
			)));
			$view->assign('viewProduct', $router->assemble(array(
				'action' 		=> 'viewProduct',
				'product'		=> $product
			)));
			
			$images = $application->getProvider('Image')->getTagPhotos($product->getId());
			$image = empty($imageId) || !isset($images[$imageId])?
				Kansas_Core_Collection_first($images):
				$image = $images[$imageId];
			$view->assign('images', $images);
			$view->assign('image', $image);
		}
		return $this->createResult($view, 'page.shop-product.tpl');
	}
	
	/// Muestra el pedido actual
	public function order() {
		global $application;
//		$count = 0;
//		$cart = Kansas_Shop_Order::getCurrent($count, true);
		$cart = $this->getParam('order');
		if($cart->getStatus() == 'payment-authorized') {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/tienda/order/checkout/review');
			return $result;
		}
		$shop = $application->getModule('Shop')->getShop();
		$view = $this->createView();
		$auth = $application->getModule('Auth');
		$user = $auth->hasIdentity() ?
			$auth->getIdentity():
			null;
		$address = $shop->getAddress($cart->getShippingAddressId(), $user);
		if($address instanceof Kansas_Places_Region_Interface)
			$view->assign('region', $address);
		else  {
			$view->assign('address', $address);
			$view->assign('region', $shop->getShipRegionByAddress($address));
		}
		$view->assign('shipMethods', $shop->getShipMethodsByAddress($address));
		
		return $this->createResult($view, 'page.shop-order.tpl');
	}
	
	// actualiza el pedido actual (elimina  y cambia las unidades de productos)
	public function orderUpdate() {
		$delete = array();
		$quantity = array();
		foreach($this->getRequest()->getParams() as $key => $value) {
			$keydata = explode('_', $key);
			if(count($keydata) == 2) {
				switch($keydata[1]) {
					case 'delete':
						$delete[$keydata[0]] = true;
						unset($quantity[$keydata[0]]);
						break;
					case 'quantity':
						if(!isset($delete[$keydata[0]]))
							$quantity[$keydata[0]] = $value;
						break;
				}
			}
		}
		$shippingMethod = $this->getParam('shipping', null);

		$count = null;
		$cart = Kansas_Shop_Order::getCurrent($count, true);

		foreach($delete as $key => $value)
			$cart->removeItem(new System_Guid($key));
			
		foreach($quantity as $key => $value)
			$cart->setItem(new System_Guid($key), $value);

		if(!empty($shippingMethod))
			$cart->setShippingMethodId(new System_Guid($shippingMethod));

		$cart->save();

		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl('/tienda/order');
		return $result;
	}
	
	// Añade un producto al carro de la compra
	public function buyProduct() {
		$count = null;
		$cart = Kansas_Shop_Order::getCurrent($count, true);
		$productId = new System_Guid($this->getParam('product'));
		$cart->addItem($productId);
		$cart->save();
		
		$ru 	= $this->getParam('ru', '/tienda');
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($ru);
		return $result;
	}

	// Establece la cantidad de un producto en carro de la compra
	public function setProduct() {
		$count = null;
		$cart = Kansas_Shop_Order::getCurrent($count, true);
		$productId	= new System_Guid($this->getParam('product'));
		$quantity 	= $this->getParam('quantity', 1);
		$product 		= $cart->getItem($productId);
		$product->setQuantity($quantity);
		$cart->save();
		
		$ru 	= $this->getParam('ru', '/tienda');
		$result = new Kansas_View_Result_Redirect();
		$result->setGotoUrl($ru);
		return $result;
	}
	
	// Redirije a la pagina para finalizar la compra
	public function checkout() {
		global $application;
		$count = null;
		$auth = $application->getModule('Auth');
		$cart = Kansas_Shop_Order::getCurrent($count, true);
		$result = new Kansas_View_Result_Redirect();
		if(!$auth->hasIdentity()) {
			// Usuario autentificado
			$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru', '/tienda/order/checkout')));
		} elseif(
			$cart->getShippingAddress() == null || // Direccion no establecida
			!$cart->getShippingAddress() instanceof Kansas_Places_Address_Interface) { // Direccion no determinada
			// Direccion de envio
			$result->setGotoUrl('/tienda/order/checkout/ship-address');
		} elseif(false) {
			// Direccion de facturacion
			$result->setGotoUrl('/tienda/order/checkout/bill-address');
		} elseif($cart->getPayment() == null || $cart->getPayment()->getStatus() == 'void') {
			// Forma de pago
			$result->setGotoUrl('/tienda/order/checkout/payment');
		} elseif($cart->getPayment() != null && $cart->getPayment()->getStatus() == 'created') {
			// Confirmar pago
			$result->setGotoUrl($cart->getPayment()->getAuthorizeUrl());
		} else {
			// Verificar compra
			$result->setGotoUrl('/tienda/order/checkout/review');
		}
		return $result;
	}
	
	// Permite seleccionar la direccion de envio
	public function shipAddress() {
		global $application;
		$count = null;
		$auth			= $application->getModule('Auth');
		$shop			= $application->getModule('Shop')->getShop();
		$cart			= Kansas_Shop_Order::getCurrent($count, true);
		$generic	= $this->getParam('generic', true);
		$url			= $this->getParam('url', '/tienda/order/ship-address');
		$view			= $this->createView();
		$user			= null;
		$shipAddress = array();
		if($auth->hasIdentity()) {
			$user = $auth->getIdentity();
			$placesProvider = $application->getProvider('places');
			$shipAddress = $placesProvider->getAddressByUser($user->getId());
			$view->assign('shipAddress', $shipAddress);
		} elseif(!$generic) {
			$result = new Kansas_View_Result_Redirect();
			$result->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru', $url)));
			return $result;
		}
		$address = $cart->getShippingAddress();
		$view->assign('currentAddress', $address);
		$view->assign('generic', $generic);
		$view->assign('option', $this->getParam('option', count($shipAddress) == 0? 'add': ''));
		if($generic) {
			$view->assign('shipRegions', $shop->getShipRegions());
			$view->assign('tab', $this->getParam('tab', 'generic'));
		} else {
			$view->assign('tab', 'personal');
		}
		return $this->createResult($view, 'page.shop-address.tpl');
		
		
	}
	
	// Establece la direccion de envio
	public function setShipAddress() {
		$generic = $this->getParam('generic-address', null);
		$personal = $this->getParam('personal-address', null);
		$result = new Kansas_View_Result_Redirect();
		if(!empty($generic) || !empty($personal)) {
			$count = null;
			$order = Kansas_Shop_Order::getCurrent($count, true);
			$id = empty($generic)?
				new System_Guid($personal):
				new System_Guid(md5($generic));
			$order->setShippingAddressId($id);
			$order->save();
			$result->setGotoUrl('/tienda/order');
		} else {
			$result->setGotoUrl('/tienda/order/ship-address');
		}
		
		return $result;
	}
	
	// Finaliza la compra mediante PayPal Express Checkout
	public function expressCheckout() {
		global $application;
		$count		= null;
		$auth			= $application->getModule('Auth');
		$cart			= Kansas_Shop_Order::getCurrent($count, true);
		$result		= new Kansas_View_Result_Redirect();
		if(count($cart->getItems()) == 0)	{ // NO hay compra
			$result->setGotoUrl('/tienda/order');
		} elseif($this->isAuthenticated($result)) {
			// Generar pago
			$payment = $cart->createPayment('PayPalExpress');
			$cart->save();
			$payment->save();
			$result->setGotoUrl($payment->getAuthorizeUrl());
		}
		return $result;
	}
	
	// Autoriza un pago realizado mediante paypal
	public function paymentPaypal() {
		global $application;
		$result		= null;
		if($this->isAuthenticated($result)) {
			$result		= new Kansas_View_Result_Redirect();
			// Grabar pago como autorizado
			$shop		= $application->getModule('Shop')->getShop();
			$token	= $this->getParam('token');
			
			$payment = $shop->getPaymentByToken($token);
			try {
				$payment->authorize();
			} catch(Paypal_NVP_Exception $exception) {
				switch($exception->getCode()) {
					case 10411: // Session de paypal expirada, renovar pago
						$payment->getStatus('void');
						$payment->save();
						$payment = $payment->renew();
						break;
					default:
						throw $exception;
				}
			}
			$payment->save();
			$payment->getOrder()->save();
			switch($payment->getStatus()) {
				case 'authorized':
					$result->setGotoUrl('/tienda/order/checkout/review');
					break;
				case 'created':
					$result->setGotoUrl($payment->getAuthorizeUrl());
					break;
				default:
					var_dump($payment->getStatus());
					exit;
			}
		}
		return $result;
	}
	
	// Cancela un pago
	public function paymentVoid() {
		$result		= null;
		if($this->isAuthenticated($result)) {
			global $application;
			$result		= new Kansas_View_Result_Redirect();
			// Grabar pago como cancelado
			$shop	= $application->getModule('Shop')->getShop();
			$params = $this->getRequest()->getParams();
			
			if(isset($params['token'])) {
				$payment	= $shop->getPaymentByToken($params['token']);
			} elseif(isset($param['Id'])) {
				$id = new System_Guid($parma['Id']);
				$payment	= $shop->getPaymentById($id);
			} else {
				$cart			= Kansas_Shop_Order::getCurrent(true);
				$payment	= $cart->getPayment();
			}
			$payment->void();
			$payment->save();
			$payment->getOrder()->save();
			$ru = $this->getParam('ru', '/tienda/order');
			$result->setGotoUrl($ru);
		}
		return $result;
	}
	public function paymentConfirm() {
		$result		= null;
		if($this->isAuthenticated($result)) {
			$result		= new Kansas_View_Result_Redirect();
			// Grabar pago como autorizado
			$count = 0;
			$order = Kansas_Shop_Order::getCurrent($count, true);
			$payment	= Kansas_Shop_Order::getCurrent($count, true)->getPayment();
			if($payment != null) {
				$payment->confirm();
				$payment->save();
				$payment->getOrder()->save();
				$result->setGotoUrl('/tienda/order/' . $payment->getOrderId()->__toString() . Kansas_Response::buildQueryString(array('msg' => 'payment-confirmed')));
			} else 
				$result->setGotoUrl('/tienda/order/' . Kansas_Response::buildQueryString(array('msg' => 'no-payment-confirm')));
		}
		return $result;
	}

	public function reviewCheckout() {
		$result = null;
		if($this->isAuthenticated($result)) {
			$cart			= Kansas_Shop_Order::getCurrent(true);
			// comprobar metodo de envio, direccion de envio y pago autorizado
			if($cart->getStatus() != 'payment-authorized') {
				$result	= new Kansas_View_Result_Redirect();
				$result->setGotoUrl('/tienda/order');
			} else {
				$result = $this->createView();
				$result = $this->createResult($result, 'page.shop-review.tpl');
			}
		}
		return $result;
	}
	
	public function orderView() {
		$view = $this->createView();
		$view->assign('order', $this->getParam('order'));
		$view->assign('msg', $this->getParam('msg'));
		return $this->createResult($view, 'page.shop-order-view.tpl');
	}
	
	public function orderList() {
		$result = null;
		if($this->isAuthenticated($result)) {
			global $application;
			$router = $this->getParam('router');
			$auth			= $application->getModule('Auth');
			$user 		= $auth->getIdentity();
			$shopProvider = $application->getProvider('shop');
//			$orders		= new Kansas_Core_GuidItem_Collection();
			$orders		= $shopProvider->getOrdersByUser($user, false);
			// remove empty
//			foreach($shopProvider->getOrdersByUser($user) as $item) {
//				if(count($item->getItems()) != 0)
//					$orders->add($item);
//			}
			
			if(count($orders) == 1) {
				$order = Kansas_Core_Collection_first($orders);
				$result		= new Kansas_View_Result_Redirect();
				$result->setGotoUrl('/' . $router->getBasePath() .'/' . $order->getId()->__toString()); // /tienda/orders/GUID
			} else {
				foreach($orders as $order)
					var_dump($order->getStatusText(), $order);
				exit;
			}
		}
		return $result;
	}
	
}