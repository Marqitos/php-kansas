<?php declare(strict_types = 1);
/**
  * Devuelve los datos basicos del navegación de la solicitud actual
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\Request;

use Psr\Http\Message\ServerRequestInterface;
use Kansas\Autoloader;
use Kansas\Environment;

require_once 'Psr/Http/Message/ServerRequestInterface.php';

// Obtiene los datos basicos de navegación de la solicitud actual
#[SupressWarnings('php:S3776')]
function getTrailData(ServerRequestInterface $request) : array {
    $uri    = $request->getUri();
    $data   = [
        'time'          => Environment::getRequestTime(),
        'hostname'      => $uri->getHost(),
        'uri'           => $request->getRequestTarget(),
        'page'          => trim($uri->getPath()),
        'environment'   => Environment::getStatus()];

    $serverParams = $request->getServerParams();
    if((stristr(PHP_OS, "darwin") !== false) &&
        isset($serverParams['HTTP_PC_REMOTE_ADDR']) &&
        !empty($serverParams['HTTP_PC_REMOTE_ADDR'])) {
        $data['remoteAddress'] = $serverParams['HTTP_PC_REMOTE_ADDR'];
    } elseif(isset($serverParams['REMOTE_ADDR'])) {
        $data['remoteAddress'] = $serverParams['REMOTE_ADDR'];
    }

    if($request->hasHeader('user-agent')) {
        $data['userAgent'] = $request->getHeader('user-agent')[0];
    } elseif(isset($serverParams['HTTP_USER_AGENT'])) {
        $data['userAgent'] = $serverParams['HTTP_USER_AGENT'];
    }

    require_once 'Kansas/Autoloader.php';
    $marker = false;
    if (Autoloader::isReadable('BBClone/constants.php') &&
        Autoloader::isReadable('BBClone/lib/marker.php') &&
        Autoloader::isReadable('BBClone/lib/io.php')) {
        require_once 'BBClone/constants.php';
        require_once 'BBClone/lib/io.php';
        require_once 'BBClone/lib/marker.php';
        $marker = new bbc_marker;
    }

    if ($marker) {
        $httpHost       = isset($getServerParams['HTTP_HOST'])
            ? bbc_clean($getServerParams['HTTP_HOST'], $BBC_SEP)
            : '';
        $httpReferer    = isset($getServerParams['HTTP_REFERER'])
            ? bbc_clean($getServerParams['HTTP_REFERER'], $BBC_SEP)
            : '';
        $serverName     = isset($getServerParams['SERVER_NAME'])
            ? bbc_clean($getServerParams['SERVER_NAME'], $BBC_SEP)
            : '';
        $serverAddr     = isset($getServerParams['SERVER_ADDR'])
            ? bbc_clean($getServerParams['SERVER_ADDR'], $BBC_SEP)
            : '';
        $localAddr      = isset($getServerParams['LOCAL_ADDR'])
            ? bbc_clean($getServerParams['LOCAL_ADDR'], $BBC_SEP)
            : '';
        $serverAddr     = empty($serverAddr)
            ? $localAddr
            : $serverAddr;
        $serverAddr     = $this->bbc_valid_ip($serverAddr)
            ? $serverAddr
            : '127.0.0.1';
        $data['referer'] = empty($httpReferer)
            ? 'unknown'
            : $marker->bbc_filter_ref($httpHost, $httpReferer, $serverName, $serverAddr);
    } elseif(isset($serverParams['HTTP_REFERER'])) {
        $data['referer'] = $serverParams['HTTP_REFERER'];
    }

    if ($marker) {
        //$prx = bbc_parse_headers();

        //$data['prx'] = bbc_get_remote_addr($REMOTE_ADDR, $HTTP_X_REMOTECLIENT_IP);

    }

    return $data;
}
