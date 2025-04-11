<?php
/**
  * Plugin para la autenticación mediante usuario y contraseña
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use Exception;
use Kansas\Configurable;
use System\EnvStatus;
use System\NotSupportedException;
use System\Version;
use Kansas\Auth\ServiceInterface as AuthService;
use Kansas\Auth\AuthException;
use Kansas\Environment;
use Kansas\Plugin\PluginInterface;

require_once 'Kansas/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Auth/ServiceInterface.php';

class Membership extends Configurable implements PluginInterface, AuthService {

  private $authPlugin;

  /// Constructor
  public function __construct(array $options) {
    parent::__construct($options);
    global $application;
    $this->authPlugin = $application->getPlugin('Auth');
    $this->authPlugin->addAuthService($this);
  }

  /// Miembros de Kansas_Module_Interface
  public function getDefaultOptions(EnvStatus $environment) : array {
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
            'path' => 'cambiar-contrasena',
            'controller' => 'Membership',
            'action' => 'changePassword'],
        'set-password' => [
            'controller' => 'Membership',
            'action' => 'setPassword']
        ]
    ];
  }

  public function getVersion() : Version {
    return Environment::getVersion();
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

  // TODO: Adaptar proveedores de datos mediante configuración
  public function authenticate($email, $password, $remember, $remoteAddress, $userAgent) {
    require_once 'Kansas/Auth/AuthException.php';
    global $application;
    $provider = $application->getProvider('Auth_Membership');
    // comprobar bloqueo de inicio de sessión
    if($this->canLogin($remoteAddress, $email)) {
      try {
        $user = $provider->validate($email, $password);
        $this->authPlugin->setIdentity($user, $remember, $remoteAddress, $userAgent);
        return $user;
      } catch(AuthException $ex) {
        if($ex->getErrorCode() != AuthException::FAILURE_UNCATEGORIZED) {
          // Registar evento de intento de inicio de sesión
          $this->authPlugin->registerFailLogin($remoteAddress, $email, $ex->getErrorCode());
        }
        throw $ex;
      } catch(Exception $ex) {
        $application->log($ex);
        throw new AuthException(AuthException::FAILURE_UNCATEGORIZED);
      }
    } else {

    }

  }

}
