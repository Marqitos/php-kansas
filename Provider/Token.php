<?php

namespace Kansas\Provider;

use System\Guid;
use Kansas\Provider\AbstractDb;
use Lcobucci\JWT\Token as jwtToken;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Parsing\Decoder;
use Lcobucci\JWT\Parsing\Encoder;

use function bin2hex;
use function explode;

require_once 'Kansas/Provider/AbstractDb.php';
require_once 'Lcobucci/JWT/Token.php';

class Token extends AbstractDb {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Obtiene un token
   * Comprueba la fecha de expiración y la firma
   *
   * @param Guid $id Id del token (claim jti)
   * @param string $signature Firma del token, si se especifica, se comprueba que coincida
   * @return mixed Token con el id solicitado, o false en caso de que no haya un token valido.
   */
  public function getToken(Guid $id, $signature) {
    if($this->cache && $this->cache->test('token-id-' . $id->getHex())) { // Obtiene el token desde cache
      $payload = $this->cache->load('token-id-' . $id->getHex());
      $parts = explode('.', $payload);
      if($signature !== false && (!isset($parts[2]) || $parts[2] != $signature)) { // Comprueba la firma
        return false;
      }
      require_once 'Lcobucci/JWT/Parser.php';
      $parser = new Parser();
      $token = $parser->parse($payload);
      if($token->isExpired()) { // Comprueba la fecha de expiración
        $this->cache->remove('token-id-' . $id->getHex());
        return false;
      }
      return $token;
    }

    $this->cleanUp(); // Elimina los tokens expirados
    $statement = $this->db->query( // Obtiene el token desde la base de datos
      'SELECT HEX(`header`) AS `header`, HEX(`payload`) AS `payload`, HEX(`signature`) AS `signature` FROM `Tokens` WHERE `id` =  UNHEX(?);');
    $rows = $statement->execute([
        $id->getHex()
      ]);
    if($row = $rows->current()) {
      require_once 'Lcobucci/JWT/Parser.php';
      require_once 'Lcobucci/JWT/Parsing/Encoder.php';
      $encoder = new Encoder();
      $payload = $encoder->base64UrlEncode(hex2bin($row['header'])) . '.' . $encoder->base64UrlEncode(hex2bin($row['payload']));
      if($row['signature'] != null) { // Comprueba la firma
        if($signature !== false && $encoder->base64UrlEncode(hex2bin($row['signature'])) != $signature) {
          return false;
        }
        $payload .= '.' . $encoder->base64UrlEncode(hex2bin($row['signature']));
      }
      if($this->cache) { // Guarda una copia en cache
        $this->cache->save($payload, 'token-id-' . $id->getHex());
      }
      $parser = new Parser();
      return $parser->parse($payload);
    }
    return false;
  }

  /**
   * Guarda un token
   *
   * @param Guid $id
   * @param jwtToken $token
   * @return void
   */
  public function saveToken(jwtToken $token) {
    require_once 'Lcobucci/JWT/Parsing/Decoder.php'; // Obtiene información del token
    $id = new Guid($token->getClaim('jti'));
    $user = $token->hasClaim('sub')
      ? $token->getClaim('sub')
      : null;
    $exp = $token->hasClaim('exp')
      ? $token->getClaim('exp')
      : null;
    $device = $token->hasClaim('dev')
      ? $token->getClaim('dev')
      : null;
    $payload = explode('.', (string) $token);
    $decoder = new Decoder(); 
    $hexHeader  = bin2hex($decoder->base64UrlDecode($payload[0]));
    $hexPayload = bin2hex($decoder->base64UrlDecode($payload[1]));
    $hexSignature = isset($payload[2])
      ? bin2hex($decoder->base64UrlDecode($payload[2]))
      : null;
    $statement = $this->db->query( // Guarda una copia en la base de datos
      'INSERT INTO `Tokens` '
      . '(`id`, `header`, `payload`, `signature`, `user`, `dev`, `exp`) '
      . 'VALUES (UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), UNHEX(?), ?);');
    $result = $statement->execute([
        $id->getHex(),
        $hexHeader,
        $hexPayload,
        $hexSignature,
        $user,
        $device,
        $exp
      ]);
    if($this->cache) { // Guarda una copia en cache
      $this->cache->save((string) $token, 'token-id-' . $id->getHex());
    }
    return $result;
  }

  /**
   * Obtiene las sesiones de un usuario
   *
   * @param Guid $userId
   * @return array
   */
  public function getSessions(Guid $userId) {
    $this->cleanUp(); // Elimina los tokens expirados
    $statement = $this->db->query( // Obtiene el token desde la base de datos
      'SELECT '
      . 'HEX(`TKN`.`id`) AS `id`, '
      . 'HEX(`TKN`.`header`) AS `header`, '
      . 'HEX(`TKN`.`payload`) AS `payload`, '
      . 'HEX(`TKN`.`signature`) AS `signature`, '
      . '`SIA`.`time`, '
      . '`SIA`.`remoteAddress` '
      . 'FROM `Tokens` AS `TKN` '
      . 'INNER JOIN `SignInAttempts` AS `SIA` '
      . 'ON HEX(`TKN`.`id`) = `SIA`.`session` '
      . 'WHERE `TKN`.`user` =  UNHEX(?) '
      . 'AND `SIA`.`status` = 1 '
      . 'ORDER BY `SIA`.`time`;');
    $rows = $statement->execute([
        $userId->getHex()
      ]);
    $result = [];
    while($row = $rows->current()) {
      require_once 'Lcobucci/JWT/Parser.php';
      require_once 'Lcobucci/JWT/Parsing/Encoder.php';
      $encoder = new Encoder();
      $payload = $encoder->base64UrlEncode(hex2bin($row['header'])) . '.' . $encoder->base64UrlEncode(hex2bin($row['payload']));
      if($row['signature'] != null) {
        $payload .= '.' . $encoder->base64UrlEncode(hex2bin($row['signature']));
      }
      $parser = new Parser();
      $token = $parser->parse($payload);
      $result[$row['id']] = [
        'token' => $token,
        'time'  => $row['time'],
        'remoteAddress'  => $row['remoteAddress']
      ];
      $rows->next();
    }
    return $result;
  }

  /**
   * Elimina de la base de datos todos los tokens que han expirado
   *
   * @return void
   */
  protected function cleanUp() {
    $statement = $this->db->query(
      'DELETE FROM `Tokens` WHERE `exp` <  ?;');
    return $statement->execute([
        time()
      ]);
  }
}