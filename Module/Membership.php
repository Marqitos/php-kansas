<?php
//require_once 'System/Configurable/Abstract.php';
//require_once 'Kansas/Auth/Service/Interface.php';
//require_once 'Kansas/Module/Interface.php';

class Kansas_Module_Membership
  extends System_Configurable_Abstract
  implements Kansas_Module_Interface, Kansas_Auth_Service_Interface {

  /// Constructor
  public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    $authModule = $application->getModule('Auth');
    $authModule->addAuthService($this);
  }
  
  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions($environment) {
    switch ($environment) {
      case 'production':
      case 'development':
      case 'test':
        return [
          'actions' => [
            'signin' => [
              'path' => '/iniciar-sesion',
              'controller' => 'Membership',
              'action' => 'signIn'],
            'signup' => [
              'path' => '/registro',
              'controller' => 'Membership',
              'action' => 'signUp'],
            'reset-password' => [
              'path' => 'recordar',
              'controller' => 'Membership',
              'action' => 'resetPassword'],
            'change-password' => [
              'path' => 'cambiar-password',
              'controller' => 'Membership',
              'action' => 'changePassword'],
            'set-password' => [
              'controller' => 'Membership',
              'action' => 'setPassword']
          ]
        ];
      default:
        require_once 'System/NotSuportedException.php';
        throw new System_NotSuportedException("Entorno no soportado [$environment]");
    }
  }

  public function getVersion() {
    global $environment;
    return $environment->getVersion();
  }
  
  /// Miembros de Kansas_Auth_Service_Interface
  public function getActions() {
    return $this->options['actions'];
  }
  
  public function getAuthType() {
    return 'form';
  }

  public function getName() {
    return "membership";
  }

  
  /// Miembros estaticos
  public static function authenticate($email, $password, $remember) {
    require_once 'Kansas/Auth/Exception.php';
    global $application;
    $provider = $application->getProvider('Auth_Membership');
    $authModule = $application->getModule('Auth');
    // comprobar bloqueo de inicio de sessión
    try {
      $user = $provider->validate($email, $password);
      $authModule->setIdentity($user, $remember);
      return $user;
    } catch(Kansas_Auth_Exception $ex) {
      if($ex->getErrorCode() != Kansas_Auth_Exception::FAILURE_UNCATEGORIZED) {
        // Registar evento de intento de inicio de sesión
        // Comprobar si hay q bloquear el usuario, y realizar el bloqueo
      }
      throw $ex;
    }

  }

}