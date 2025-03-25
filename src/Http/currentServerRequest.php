<?php declare(strict_types = 1);
/**
 * Función que devuelve la petición realizada al servidor actual
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2021, Marcos Porto
 * @since v0.4
 */

namespace Kansas\Http;

use Kansas\Http\ServerRequest;
use function Kansas\Http\marshalHeadersFromSapi;
use function Kansas\Http\marshalMethodFromSapi;
use function Kansas\Http\marshalProtocolVersionFromSapi;
use function Kansas\Http\marshalUriFromSapi;
use function Kansas\Http\normalizeServer;
use function Kansas\Http\normalizeUploadedFiles;
use function Kansas\Http\parseCookieHeader;

/**
 * Devuelve la petición realizada al servidor actual
 *
 * @param  array $server (Opcional) Datos de $_SERVER o similares
 * @param  array $query (Opcional) Datos de $_GET o similares
 * @param  array $body (Opcional) Datos de $_POST o similares
 * @param  array $cookies (Opcional) Datos de $_COOKIE o similares
 * @param  array $files (Opcional) Datos de $_FILES o similares
 * @param  callable $apacheRequestHeaders
 * @return ServerRequest Petición realizada al servidor actual
 */
function currentServerRequest(array $server = null, array $query = null, array $body = null, array $cookies = null, array $files = null, callable $apacheRequestHeaders = null) : ServerRequest {
		require_once 'Kansas/Http/marshalHeadersFromSapi.php';
		require_once 'Kansas/Http/marshalMethodFromSapi.php';
		require_once 'Kansas/Http/marshalProtocolVersionFromSapi.php';
		require_once 'Kansas/Http/marshalUriFromSapi.php';
		require_once 'Kansas/Http/normalizeServer.php';
		require_once 'Kansas/Http/normalizeUploadedFiles.php';

		$server = normalizeServer(
			$server ?: $_SERVER,
			$apacheRequestHeaders
		);
		$files   = normalizeUploadedFiles($files ?: $_FILES);
		$headers = marshalHeadersFromSapi($server);

		if(null === $cookies &&
		   isset($headers['cookie'])) {
			require_once 'Kansas/Http/parseCookieHeader.php';
			$cookies = parseCookieHeader($headers['cookie']);
		}

		return new ServerRequest(
				$server,
				$files,
				marshalUriFromSapi($server, $headers),
				marshalMethodFromSapi($server),
				'php://input',
				$headers,
				$cookies ?: $_COOKIE,
				$query ?: $_GET,
				$body ?: $_POST,
				marshalProtocolVersionFromSapi($server)
		);
}