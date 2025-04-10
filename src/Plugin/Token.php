<?php declare(strict_types = 1);
/**
  * Plugin para el uso de tokens JWT
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Plugin;

use OutOfBoundsException;
use System\Configurable;
use System\EnvStatus;
use System\Guid;
use System\Version;
use Kansas\Plugin\PluginInterface;
use Kansas\Router\Token as Router;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as JWToken;

use function Kansas\Plugin\Token\buildToken;
use function Kansas\Plugin\Token\verifyToken;
use function Kansas\Request\getTrailData;
use function time;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Token extends Configurable implements PluginInterface {

    private $router;

    // Miembros de System\Configurable\ConfigurableInterface
    public function getDefaultOptions(EnvStatus $environment) : array {
        return [
            'device'    => false,
            'exp'       => 15 * 24 * 60 * 60, // 15 días
            'secret'    => false,
            'signer'    => 'HS256',
            'domain'    => '',
            'iss'       => $_SERVER['SERVER_NAME'],
            'session'   => null,
            'router'    => false];
    }

    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }

  /**
   * Crea un token a partir de una cadena jwt,
   * y comprueba que sea válido
   *
   * @param string $tokenString
   * @return Lcobucci\JWT\Token|false
   */
  public function parse(string $tokenString) {
      require_once 'Lcobucci/JWT/Parser.php';
      $parser = new Parser();
      $token = $parser->parse($tokenString);
      if ($this->options['secret']) { // Si está firmado, comprobamos la firma conforme el algoritmo de firma
          $alg        = $token->getHeader('alg');
          if (!self::loadAlg($alg) ||
              !verifyToken($token, $this->options['secret'])) {
              return false;
          }
      }
      if ($this->options['device'] === true &&
          $token->hasClaim('dev')) { // Comprobar dispositivo
          $hexDevice = self::getDevice();
          if (hex2bin($hexDevice) != hex2bin($token->getClaim('dev'))) {
              return false;
          }
      }
      return $token;
  }

  /**
   * Crea un enlace que realizara un dispatch al visitarlo, y devuelve la dirección
   *
   * @param array $data
   * @return string
   */
  public function createLink(array $data) {
      require_once 'System/Guid.php';
      $tokenData = [
          'iss'   => $this->options['iss'],
          'iat'   => time(),
          'exp'   => time() + $this->options['exp']
      ];
      if (isset($data['user'])) {
          $tokenData['sub'] = ($data['user'] instanceof Guid)
              ? $data['user']->getHex()
              : $data['user'];
          unset($data['user']);
      }
      $data       = array_merge($tokenData, $data);
      $token      = $this->createToken($data);
      $provider   = $application->getProvider('token');
      $provider->saveToken($token);
      $id         = new Guid($token->getClaim('jti'));

      if ($this->options['secret']) { // Devuelve un enlace firmado
          $signature = explode('.', (string) $token)[2];
          return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex() . '/' . $signature;
      }
      return $_SERVER['SERVER_NAME'] . '/token/' . $id->getHex();
  }


  public function createToken(array $data, bool $device = null) {
      $data = array_merge([
          'iss'   => $_SERVER['SERVER_NAME'],
          'iat'   => time(),
          'exp'   => time() + $this->options['exp']
      ], $data);
      if (isset($data['exp']) &&
          $data['exp'] === false) {
          unset($data['exp']);
      }
      if ($device === null) {
          $device = $this->options['device'];
      }
      if ($device === true) {
          global $environment;
          require_once 'Kansas/Request/getTrailData.php';
          $request    = $environment->getRequest();
          $userAgent  = getTrailData($request)['userAgent'];
          $data['dev']=  md5($userAgent); // guardar información del dispositivo
      }
      return $this->getToken($data);
  }

  public function updateToken(JWToken $token, array $changes) {
      $claims = $token->getClaims();
      $data = [];
      foreach ($claims as $claim) {
          if (isset($changes[$claim->getName()])) {
              $data[$claim->getName()] = $changes[$claim->getName()];
              unset($changes[$claim->getName()]);
          } else {
              $data[$claim->getName()] = $claim->getValue();
          }
      }
      $data = array_merge($data, $changes);
      return $this->getToken($data);
  }

  public function getToken(array $data) : JWToken {
      global $application;
      require_once 'Lcobucci/JWT/Builder.php';
      $builder = new Builder();
      foreach ($data as $claim => $value) {
          $builder->withClaim($claim, $value);
      }
      if ($this->options['secret']) { // Firma el token mediante el algoritmo solicitado
          if (self::loadAlg($this->options['signer'])) {
              $token = buildToken($builder, $this->options['secret']);
          } else {
              throw new OutOfBoundsException('El algoritmo de firma no es soportado');
          }
      } else {
          $token = $builder->getToken();
      }
      if (isset($data['jti'])) { // Si tiene un Id, lo guardamos en la base de datos
          $provider = $application->getProvider('token');
          $provider->saveToken($token);
      }
      return $token;
  }

  public function getRouter() {
    if (!isset($this->router)) {
      require_once 'Kansas/Router/Token.php';
      $this->router = new Router($this, $this->options);
    }
    return $this->router;
  }

  public function getISS() {
    return $this->options['iss'];
  }

  public function getEXP() {
    return $this->options['exp'];
  }

  public function getSessionDomain() {
    return $this->options['domain'];
  }

  public static function authenticate($idUser) {
    global $application;
    $authPlugin         = $application->getPlugin('Auth');
    $localizationPlugin = $application->getPlugin('Localization');
    $usersProvider      = $application->getProvider('Users');
    $locale             = $localizationPlugin->getLocale();
    $user               = $usersProvider->getById($idUser, $locale['lang'], $locale['country']);
    $authPlugin->setIdentity($user);
    return $user;
  }

  /**
   * Elimina un token de la base de datos
   *
   * @param JWToken $token
   * @return void
   */
  public static function deleteToken(JWToken $token) {
    global $application;
    require_once 'System/Guid.php';
    $id = new Guid($token->getClaim('jti'));
    $provider = $application->getProvider('Token');
    $provider->deleteToken($id);
  }

  /**
   * Carga las funciones de firma y validación
   *
   * @param string $alg
   * @return bool true si se han cargado las funciones con exito
   */
  public static function loadAlg(string $alg) : bool {
    if (function_exists('Kansas\Plugin\Token\verifyToken') ||
        function_exists('Kansas\Plugin\Token\buildToken')) {
      die('ya existe');
      return false;
    }
    $dir        = __DIR__ . '/Token/';
    $handler    = opendir($dir);
    $file       = null;
    while (($file = readdir($handler)) !== false) {
      if (filetype($dir . $file) == 'file' &&
        basename($file, '.php') == $alg) {
        break;
      }
    }
    closedir($handler);

    if ($file) {
      require_once $dir . $file;
      return true;
    }

    return false;
  }

  public static function getDevice() {
    global $environment;
    require_once 'Kansas/Request/getTrailData.php';
    $request    = $environment->getRequest();
    $userAgent  = getTrailData($request)['userAgent'];
    return md5($userAgent, false);
  }

}
