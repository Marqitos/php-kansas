<?php

class Kansas_Controllers_Account
	extends Kansas_Controller_Abstract {
		
	
	public function init(array $params) {
		parent::init($params);
	}
	
	static function getSignInRedirection($ru = '/') {
		return Kansas_View_Result_Redirect::gotoUrl(self::getRouter()->assemble([
			'action' => 'signin',
			'ru'     => $ru
		]));
	}
	
	public function index() {
		global $application;
		if(!$application->getModule('Auth')->hasIdentity())
			return self::getSignInRedirection('/' . trim($this->getRequest()->getUri()->getPath(), '/'));
		else
			return $this->createViewResult('page.account.tpl', [
        'title' => 'Perfil de usuario'
      ]);
	}
	
	protected static function getExternalSignIn($params) {
		global $application;
		$externalSignin = [];
		$params = array_merge($_REQUEST, $params);
		foreach($application->getModule('Auth')->getAuthServices('external') as $name => $externalService)
			$externalSignin[] = array_merge($externalService->getLoginUrl($params), ['name' => $name]);
		return $externalSignin;		
	}
	
	public function signOut() {
		global $application;
		$ru	= $this->getParam('ru', '/');
		$application->getModule('Auth')->clearIdentity();
		$redirect = new Kansas_View_Result_Redirect();
		$redirect->setGotoUrl($ru);
		return $redirect;
	}
	
	public function fbSignIn() {
		global $application;
		$facebook = $this->getModule()->getAuthService('facebook')->getCore();
		$ru				= $this->getParam('ru', '/');
		if(intval($facebook->getClass()->getUser()) == 0)
      return Kansas_View_Result_Redirect::gotoUrl('/account/signin' . http_build_query([
        'ru'  => $ru
      ]));
		elseif($facebook->isRegistered()) {
			$authResult				= $application->getModule('Auth')->authenticate($facebook);
			if($authResult->isValid())
				return Kansas_View_Result_Redirect::gotoUrl($ru);
			else
				return Kansas_View_Result_Redirect::gotoUrl('/account/signin' . http_build_query([
          'ru'	=> $ru
        ]));
				
		} else
      return Kansas_View_Result_Redirect::gotoUrl('/account/fb/register' . http_build_query([
        'ru'	=> $ru
      ]));
	}
	
	public function fbRegister() {
		global $application;
		$ru				= $this->getParam('ru', '/');
		$facebook = $application->createAuthMembership('facebook');
		if(isset($_REQUEST['signed_request'])) {
			$facebook->register();
			$result	= $application->getModule('Auth')->authenticate($facebook);
			$redirect = new Kansas_View_Result_Redirect();
			if($result->isValid())
				$redirect->setGotoUrl($ru);
			else
				$redirect->setGotoUrl('/account/signin' . Kansas_Response::buildQueryString(array('ru'	=> $ru)));
			return $redirect;
		} else {
      $application->getView()->setCaching(false);
			return $this->createResult('page.fb-register.tpl',[
        'user'    => $facebook->getClass()->getUser(),
        'signin'  => true,
        'ru'      => $ru,
        'fb_id'   => $facebook->getClass()->getAppId()
      ]);
		}
	}
  
  public function admin(array $vars = []) {
    global $application;
    if($vars['requestType'] == 'smarty') {
      $auth = $application->getModule('auth');
      $provider = $application->getProvider('users');
      $defaultScope = $auth->getDefaultScope();
      $data = [];
      $data['roles'] = $auth->getRolesByScope();
      $users = $provider->getAll();
      
      if(isset($_GET['createResult'])) {
        if($_GET['createResult'] == '1') { // Usuario creado con exito
          $data['message'] = 1;
          if(isset($_GET['userId']))
            $data['newUser'] = $_GET['userId'];
        } elseif(intval($_GET['createResult']) < 0) { // Error de validación de nuevo usuario
          $data['newUserError'] = abs(intval($_GET['createResult']));
          $cache = $application->getModule('BackendCache');
          if(isset($_GET['userId']) && $cache && $cache->test('model-user-'. $_GET['userId'])) { // datos almacenados en cache
            $data['newUserData'] = unserialize($cache->load('model-user-'. $_GET['userId']));
            $data['newUserData']['id'] = $_GET['userId'];
          }
          elseif(isset($_REQUEST['userData']))  // datos serializados como parametro
            $data['newUserData'] = unserialize($_REQUEST['userData']);
          else
            $data['newUserError'] = 0;

          if(isset($data['newUserData']['selectedRol'])) {
            $data['selectedRol'] = Kansas_Application_Module_Auth::rolKey($data['newUserData']['selectedRol']);
            unset($data['newUserData']['selectedRol']);
          }
        } else // Error desconocido
          $data['message'] = 2;
      }
      $data['users'] = [];
      foreach($users as $user) {
        $user['roles'] = $provider->getRolesByUser(new System_Guid($user['id']), new System_Guid($defaultScope['id']));
        $data['users'][$user['id']] = $user;
      }
      if(!isset($data['selectedRol']) || !isset($data['roles'][$data['selectedRol']])) {
        foreach ($data['roles'] as $key => $rol) {
          if($rol['rol'] == str_repeat('0', 32)) {
            $data['selectedRol'] = $key;
            break;
          }
        }
      }

      return $this->createViewResult('part.account-admin.tpl', $data);
    } else
      return $application->dispatch(array_merge($vars, [
        'controller'    => 'admin',
        'action'        => 'dispatch',
        'dispatch'      => [
          'controller'      => 'account',
          'action'          => 'admin']]));
  }
	
  // Lista de roles, en el panel de administración
  public function adminRoles(array $vars = []) {
    global $application;
    if($vars['requestType'] == 'smarty') {
      $auth = $application->getModule('auth');
      $data = [];
      $data['roles'] = $auth->getRolesByScope();

      return $this->createViewResult('part.account-admin-roles.tpl', $data);
    } else
      return $application->dispatch([
        'controller'    => 'admin',
        'action'        => 'dispatch',
        'dispatch'      => array_merge($vars, [
          'controller'      => 'account',
          'action'          => 'admin'])]);
  }
  
  // Crear usuario
  public function adminCreateUser(array $vars = []) {
    global $application;
    if(isset($_REQUEST['create'])) { // Añadir usuario
      $provider = $application->getProvider('users');
      // Validar entrada
      $validationErrors = 0;
      if(isset($_REQUEST['name']) && !empty($_REQUEST['name'])) // Nombre requerido
        $user['name'] = $_REQUEST['name'];
      else
        $validationErrors -= 1;
        
      if(isset($_REQUEST['email']) && !empty($_REQUEST['email'])) { // Email requerido
        $user['email'] = trim($_REQUEST['email']);
        if($provider->getByEmail($user['email'])) // Email unico
          $validationErrors -= 4;
      } else
        $validationErrors -= 2;

      $user['roles'] = [];
      if(isset($_REQUEST['roles'])) {
        $roles;
        if(is_string($_REQUEST['roles']))
          $roles = [$_REQUEST['roles']];
        elseif(is_array($_REQUEST['roles']))
          $roles = $_REQUEST['roles'];
        else {
          $roles = [];
          $validationErrors -= 8; // formato de roles no valido
        }
        foreach($roles as $rol)
          $user['roles'][] = self::getRol($rol);
      }
      if(isset($_REQUEST['rol']))
        $user['selectedRol'] = self::getRol($_REQUEST['rol']);
      
      $user['comment'] = isset($_REQUEST['comment'])? $_REQUEST['comment']: '';
      if($validationErrors == 0) { // Crear usuario
        if(isset($user['selectedRol'])) {
          $user['roles'][] = $user['selectedRol'];
          unset($user['selectedRol']);
        }
        if(count($user['roles']) == 0)
          unset($user['roles']);
          
        $createResult = $provider->create($user);
        return Kansas_View_Result_Redirect::gotoUrl('/admin/account?' . http_build_query([
          'createResult'  => $createResult,
          'userId'        => $user['id']
        ]));
      } elseif($cache = $application->getModule('BackendCache')) { // Guardar datos en cache y volver a editar
        $id;
        if(count($user['roles']) == 0)
          unset($user['roles']);
        if(!isset($_REQUEST['id']) || !System_Guid::tryParse($_REQUEST['id'], $id))
          $id = System_Guid::newGuid();
        $cache->save(serialize($user), 'model-user-'. $id->getHex(), ['model']);
        return Kansas_View_Result_Redirect::gotoUrl('/admin/account?' . http_build_query([
          'createResult'  => $validationErrors,
          'userId'        => $id->getHex()
        ]));
      } else { // volver a editar
        if(count($user['roles']) == 0)
          unset($user['roles']);
        return Kansas_View_Result_Redirect::gotoUrl('/admin/account?' . http_build_query([
          'createResult'  => $validationErrors,
          'userData'      => serialize($user) 
        ]));
      }
    } elseif(isset($_REQUEST['add'])) { // Añadir role
      
      
    }
    return Kansas_View_Result_Redirect::gotoUrl('/admin/account');
  }
  
  public static function getRol($text) {
    $id = substr($text, 33, 32);
    $name = substr($text, 66);
    $result = [];
    $result['scope'] = substr($text, 0, 32);
    if(strlen($id) == 32) {
      $result['rol'] = $id;
      if(strlen($name) > 0)
        $result['name'] = $name;
    } else {
      $result['name'] = $id;
    }
    return $result;
  }
  
  // Eliminar usuario
  public function adminDeleteUser(array $vars = []) {
    global $application;
    $provider = $application->getProvider('users');
    $userId = new System_Guid($vars['user']);
    return Kansas_View_Result_Redirect::gotoUrl('/admin/account?' . http_build_query([
      'deleteResult'  => $provider->deleteUser($userId) == 1
    ]));
    
  }
}