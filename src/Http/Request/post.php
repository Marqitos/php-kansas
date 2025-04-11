<?php

namespace Kansas\Http\Request;

use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function file_get_contents;
use function function_exists;
use function http_build_query;
use function strlen;
use function stream_context_create;

/**
 * Realiza una peticion http, usando el metodo POST, y devuelve el resultado
 *
 * @param string $uri Dirección donde realizar la petición http
 * @param array $post Datos a incluir en la petición
 * @param string $headers (Opcional) Cabeceras para añadir a la petición
 * @return string resultado de la petición
 */
function post(string $uri, array $post, array $headers = []) : string {
  $postData = http_build_query($post); // Crea la cadena de valores
  if(function_exists('curl_init')) { // realiza la peticion mediante curl
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $uri,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $postData
    ]);
    if (!empty($headers)) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    $result = curl_exec($curl);
    curl_close($curl);
    if($result !== false) {
      return $result;
    }
  }
  // realiza la petición mediante un contexto http/post
  $headers = array_merge($headers, [
    'Content-Type: application/x-www-form-urlencoded',
    'Content-Length: ' . strlen($postData)
  ]);

  $opts = [
    'http'=> [
      'method'  => 'POST',
      'header'  => implode("\r\n", $headers),
      'content' => $postData
  ]];
  $context = stream_context_create($opts);
  return @file_get_contents($uri, false, $context);
}
