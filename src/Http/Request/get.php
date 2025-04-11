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
 * Realiza una peticion http, usando el metodo GET, y devuelve el resultado
 *
 * @param string $uri Dirección donde realizar la petición http
 * @param string $headers (Opcional) Cabeceras para añadir a la petición
 * @return string resultado de la petición
 */
function get(string $uri, array $headers = []) : mixed {
  if (function_exists('curl_init')) { // realiza la peticion mediante curl
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $uri
    ]);
    if (!empty($headers)) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    $result = curl_exec($curl);
    // Comprobar si occurió algún error
    if (curl_errno($curl)) {
      // TODO: Devolver un error
      // echo 'Curl error: ' . curl_error($curl);
    }
    curl_close($curl);
    if ($result !== false) {
      return $result;
    }
  }
  // realiza la petición mediante un contexto http/get
  $opts = [
    'http'=> [
      'method'  => 'GET']];
  if (!empty($headers)) {
    $opts['http']['header'] = implode("\r\n", $headers);
  }
  $context = stream_context_create($opts);
  return @file_get_contents($uri, false, $context);
}
