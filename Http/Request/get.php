<?php

namespace Kansas\Http\Request;

use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function file_get_contents;
use function function_exists;
use function stream_context_create;

/**
 * Realiza una peticion http, usando el metodo POST, y devuelve el resultado
 *
 * @param string $uri dirección donde realizar la petición http
 * @param array $post datos a incluir en la petición
 * @return string resultado de la petición
 */
function get($uri, $userAgent = null) {
    if(function_exists('curl_init')) { // realiza la peticion mediante curl
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $uri
        ]);
        $result = curl_exec($curl);
        curl_close($curl);
        if($result !== false) {
            return $result;
        }
    }
    // realiza la petición mediante un contexto http/post
    $opts = [
        'http'=> [
            'method'  => 'GET']];
    if($userAgent != null) {
        $opts['http']['header'] = "User-Agent: $userAgent\r\n";
    }
    $context = stream_context_create($opts);
    return @file_get_contents($uri, false, $context);
}