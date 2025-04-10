<?php declare(strict_types = 1);
/**
  * Devuelve el robot o navegador y sistema operativo, a partir de USER_AGENT
  *
  * Contiene porciones de código de bbClone
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Request;

use function Kansas\Request\bbcParseUserAgent;

/**
 * Devuelve el robot o navegador y sistema operativo, a partir de USER_AGENT
 *
 * @param string $userAgent Cadena USER_AGENT a analizar
 * @return array Datos del robot, o navegador y sistema operativo detectado
 */
function getUserAgentData(string $userAgent) : array|false {
    require_once 'bbClone/robot.php';
    require_once 'Kansas/Request/bbcParseUserAgent.php';
    $data = bbcParseUserAgent($userAgent, $robot);
    if($data !== false) {
        return [ 'robot' => $data ];
    }
    $result = [];
    require_once 'bbClone/os.php';
    $data = bbcParseUserAgent($userAgent, $os);
    if($data !== false) {
        $result['os'] = $data;
    }
    require_once 'bbClone/browser.php';
    $data = bbcParseUserAgent($userAgent, $browser);
    if($data !== false) {
        $result['browser'] = $data;
    }
    return $result;
}
