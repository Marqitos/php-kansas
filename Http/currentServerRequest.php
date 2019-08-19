<?php

namespace Kansas\Http;

use Kansas\Http\ServerRequest;
use function array_key_exists;
use function is_callable;
use function Kansas\Http\marshalHeadersFromSapi;
use function Kansas\Http\marshalMethodFromSapi;
use function Kansas\Http\marshalProtocolVersionFromSapi;
use function Kansas\Http\marshalUriFromSapi;
use function Kansas\Http\normalizeServer;
use function Kansas\Http\normalizeUploadedFiles;
use function Kansas\Http\parseCookieHeader;

function currentServerRequest(array $server = null, array $query = null, array $body = null, array $cookies = null, array $files = null, $apacheRequestHeaders = null) {
    require_once 'Kansas/Http/normalizeServer.php';
    $server = normalizeServer(
      $server ?: $_SERVER,
      is_callable($apacheRequestHeaders) ? $apacheRequestHeaders : null
    );
    require_once 'Kansas/Http/normalizeUploadedFiles.php';
    $files   = normalizeUploadedFiles($files ?: $_FILES);
    require_once 'Kansas/Http/marshalHeadersFromSapi.php';
    $headers = marshalHeadersFromSapi($server);

    if (null === $cookies && array_key_exists('cookie', $headers)) {
      require_once 'Kansas/Http/parseCookieHeader.php';
      $cookies = parseCookieHeader($headers['cookie']);
    }

    require_once 'Kansas/Http/marshalUriFromSapi.php';
    require_once 'Kansas/Http/marshalMethodFromSapi.php';
    require_once 'Kansas/Http/marshalProtocolVersionFromSapi.php';

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