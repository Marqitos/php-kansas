<?php

class Kansas_Controller_API
	extends Kansas_Controller_Abstract {
	
	public function init(array $params) {
		parent::init($params);
		// Cargar autenticación
		global $application;
		$application->setModule('Digest', []);
		$auth			= $application->getModule('Auth');
		$authAdapter 	= $auth->createAuthMembership('digest', ['API']);
		$authResult		= $auth->authenticate($authAdapter);
		if(!$authResult->isValid()) {
			try {
				$result = $application->createErrorResult(new System_Net_WebException(403));
			} catch(Exception $e) {
				$result = new Kansas_View_Result_String('Acceso no autorizado');
			}
			$authAdapter->requireLogin($result);
		}
	}
	
	public function config() { // Devuelve la configuración sin los datos de modulos
		global $application;
		$config = array_merge($application->getConfig());
		unset($config['module']);
		$this->setRelative($config);
		return new Kansas_View_Result_Json($config);
	}
	
	public function files() { // Devuelve los archivos
		$path = realpath(BASE_PATH) . DIRECTORY_SEPARATOR . $this->getParam('path', '');
		$result = file_exists($path)	? $this->getDir($path)
																	: null;
		return new Kansas_View_Result_Json($result);
	}
	
	protected function getDir($directory) {
		$handle = opendir($directory);
		$result = [];
		while (false !== ($entrada = readdir($handle))) {
			if ($entrada == '.' || $entrada == '..')
				continue;
				
			if(is_file($directory . DIRECTORY_SEPARATOR . $entrada)) {
				$result[$entrada] = [
					'type' => 'file',
					'size'	=> filesize($directory . DIRECTORY_SEPARATOR . $entrada),
					'md5'  => md5_file($directory . DIRECTORY_SEPARATOR . $entrada)
				];
			} elseif(is_dir($directory . DIRECTORY_SEPARATOR . $entrada))
				$result[$entrada] = [
					'type' => 'directory'
				]; // $this->getDir($directory . DIRECTORY_SEPARATOR . $entrada);
				else {
				var_dump($entrada);
			}
		}
		closedir($handle); 
		return $result;
	}
	
	// Establece las rutas en la configuración, como rutas relativas
	// Devuelve las lista de directorios y el tipo de datos que almacenan
	protected function setRelative(&$config) {
		$result = [];
		$relative;
		if(isset($config['loader'])) {
			foreach($config['loader'] as $module => $namespaces) {
				foreach($namespaces as $key => $path) {
					if($this->isRelative($path, $relative)) {
						$config['loader'][$module][$key] = $relative;
						$result[$relative] = $module;
					}
				}
			}
		}
		if(isset($config['view'])) {
			foreach(['compileDir', 'cacheDir', 'pluginDir'] as $key) {
				if(isset($config['view'][$key]) && $this->isRelative($config['view'][$key], $relative)) {
					$config['view'][$key] = $relative;
					$result[$relative] = 'view-' . $key;
				}
			}
			if(isset($config['view']['scriptPath'])) {
				foreach($config['view']['scriptPath'] as $layout => $path) {
					if($this->isRelative($path, $relative)) {
						$config['view']['scriptPath'][$layout] = $relative;
						$result[$relative] = 'layout';
					}
				}
			}
		}
		
		return $result;
	}
	
	protected function isRelative($absolute, &$relative) {
		$realpath = self::removeDots($absolute); // realpath($absolute);
		$basepath = self::removeDots(BASE_PATH);
		$count = strlen($basepath);
		if(System_String::startWith($realpath, $basepath)) {
			$relative = substr($realpath, $count);
			return true;
		} else
			return false;
	}
	
	public static function removeDots($path) {
		if(DIRECTORY_SEPARATOR != '/')
			$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$root = ($path[0] === DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';

		$segments = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
		$ret = array();
		foreach($segments as $segment){
			if (($segment == '.') || empty($segment)) {
				continue;
			}
			if ($segment == '..') {
				array_pop($ret);
			} else {
				array_push($ret, $segment);
			}
		}
		return $root . implode(DIRECTORY_SEPARATOR, $ret);
	}
	
}