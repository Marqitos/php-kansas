<?php

namespace Kansas\Plugin;

use System\Configurable;
use Kansas\Plugin\PluginInterface;
use Kansas\Auth\ServiceInterface;
use System\NotSuportedException;
use Kansas\Auth\Exception as AuthException;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Auth/ServiceInterface.php';

class Membership extends Configurable implements PluginInterface, ServiceInterface {

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
        throw new NotSuportedException("Entorno no soportado [$environment]");
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
    } catch(AuthException $ex) {
      if($ex->getErrorCode() != AuthException::FAILURE_UNCATEGORIZED) {
        // Registar evento de intento de inicio de sesión
        // Comprobar si hay q bloquear el usuario, y realizar el bloqueo
      }
      throw $ex;
    }

  }

}