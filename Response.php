<?php

class Kansas_Response {
	
	public static function buildQueryString(array $queryData) {
		$queryItems = array();
		foreach($queryData as $key => $value)
			if(isset($value))
				$queryItems[] = urlencode($key) . '=' . urlencode($value);
		return count($queryItems) == 0?
			'':
			'?' . implode('&', $queryItems);
	}
	
	public static function buildUrl($url, array $queryData, $relative = false) {
		if($relative)
			$data = array_merge(parse_url($_SERVER['QUERY_STRING']),  parse_url($url));
		else
			$data = parse_url($url);
		$query = isset($url['query']) ? array_merge(self::parse_query($url['query']), $queryData)
																	: $queryData;
		if(isset($data['scheme'])) {
			$result = $data['scheme'] . '://';
			if(isset($data['user'])) {
				$result .= $data['user'];
				if(isset($data['pass']))
					$result .= ':' . $data['pass'];
				$result .= '@';
			}
		} else
			$result = '';
		if(isset($data['host']))
			$result .= $data['host'];
		if(isset($data['path']))
			$result .= $data['path'];
		$result .= self::buildQueryString($query);
		if(isset($data['fragment']))
			$result .= '#' . $data['fragment'];
		return $result;
	}
	
	public static function parse_query($query) { 
  $var  = explode('&', $var); 
  $arr  = []; 

  foreach($var as $val) { 
    $x          = explode('=', $val); 
    $arr[$x[0]] = html_entity_decode($x[1]); 
   } 
  unset($val, $x, $var); 
  return $arr; 
 } 
	
	public static function redirect($location, $exit = true) {
		header("Location: " . $location);
		if($exit)
			exit;
	}
	
	public static function readFile($filename, $retbytes=true) { 
		$chunksize = 1*(1024*1024); // how many bytes per chunk 
		$buffer = ''; 
		$cnt =0; 
		// $handle = fopen($filename, 'rb'); 
		$handle = fopen($filename, 'rb'); 
		if ($handle === false) 
			return false; 
		while (!feof($handle)) { 
			$buffer = fread($handle, $chunksize); 
			echo $buffer; 
			ob_flush(); 
			flush(); 
			if ($retbytes)
				$cnt += strlen($buffer); 
		} 
		$status = fclose($handle); 
		return ($retbytes && $status)?
			$cnt: // return num. bytes delivered like readfile() does. 
			$status; 

	}
	
}