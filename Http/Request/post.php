<?php

namespace Kansas\Http\Request;

use function count;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function file_get_contents;
use function function_exists;
use function http_build_query;
use function urlencode;
use function stream_context_create;

/**
 * Realiza una peticion http, usando el metodo POST, y devuelve el resultado
 *
 * @param string $uri dirección donde realizar la petición http
 * @param array $post datos a incluir en la petición
 * @return string resultado de la petición
 */
function post($uri, array $post) {
    $postData = http_build_query($post); // Crea la cadena de valores
    if(function_exists('curl_init')) { // realiza la peticion mediante curl
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $uri,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
        ]);
        $result = curl_exec($curl);
        curl_close($curl);
        if($result !== false)
            return $result;
    }
    // realiza la petición mediante un contexto http/post
    $opts = [
        'http'=> [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                         "Content-Length: " . strlen($postData) . "\r\n",
            'content' => $postData
    ]];
    $context = stream_context_create($opts);
    return file_get_contents($uri, false, $context);
}