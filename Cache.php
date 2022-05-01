<?php
/**
 * Zend Framework 2.0
 */

namespace Kansas;

use System\Configurable;
use System\ArgumentOutOfRangeException;
use System\IO\File;
use System\IO\FileNotFoundException;
use Kansas\Cache\CacheInterface;

require_once 'System/Configurable.php';

abstract class Cache extends Configurable implements CacheInterface {

		/**
		 * =====> (int) lifetime :
		 * - Cache lifetime (in seconds)
		 * - If null, the cache is valid forever
		 *
		 * =====> (int) logging :
		 * - if set to true, a logging is activated throw Zend_Log
		 *
		 * @var array Frontend or Core directives
		 */
		protected $_directives = array(
			'lifetime' => 3600,
			'logging'  => false
		);

		/**
		 * Factory
		 *
		 * @param mixed  $backend         Nombre del backend (string) o implementación de Kansas\Cache\CacheInterface
		 * @param array  $backendOptions  associative array of options for the corresponding backend constructor
		 * @param boolean $customBackendNaming if true, the backend argument is used as a complete class name ; if false, the backend argument is used as the end of "Zend_Cache_Backend_[...]" class name
		 * @param boolean $autoload if true, there will no require_once for backend and frontend (useful only for custom backends/frontends)
		 * @throws System\ArgumentOutOfRangeException
		 * @return Kansas\Cache\CacheInterface
		 */
		public static function factory($backend, array $backendOptions = [], $customBackendNaming = false, $autoload = false) {
			if (is_string($backend)) {
				return self::_makeBackend($backend, $backendOptions, $customBackendNaming, $autoload);
			} elseif ($backend instanceof CacheInterface) {
				return $backend;
			} else {
				require_once 'System/ArgumentOutOfRange.php';
				throw new ArgumentOutOfRangeException('backend must be a backend name (string) or an object which implements Kansas\Cache\CacheInterface');
			}
		}

		/**
		 * Backend Constructor
		 *
		 * @param string  $backend
		 * @param array   $backendOptions
		 * @param boolean $customBackendNaming
		 * @param boolean $autoload
		 * @throws System\ArgumentOutOfRangeException		 
		 * @throws System\FileNotFoundException		 
		 * @return Kansas\Cache
		 */
		public static function _makeBackend($backend, array $backendOptions, $customBackendNaming = false, $autoload = false) {
				if (!$customBackendNaming) {
					$backend  = self::_normalizeName($backend);
				}
				// we use a custom backend
				if (!preg_match('~^[\w]+$~D', $backend)) {
					require_once 'System/ArgumentOutOfRange.php';
					throw new ArgumentOutOfRangeException("Invalid backend name [$backend]");
				}
				if (!$customBackendNaming) { // we use this boolean to avoid an API break
					$backendClass = 'Kansas\\Cache\\' . $backend;
				} else {
					$backendClass = $backend;
				}
				if (!$autoload) {
					$file = str_replace('\\', DIRECTORY_SEPARATOR, $backendClass) . '.php';
					if (!(File::IsReadable($file))) {
						require_once 'System/IO/FileNotFoundException.php';
						throw new FileNotFoundException("file $file not found in include_path");
					}
					require_once $file;
				}
				return new $backendClass($backendOptions);
		}

		/**
		 * Normalize frontend and backend names to allow multiple words TitleCased
		 *
		 * @param  string $name  Name to normalize
		 * @return string
		 */
		protected static function _normalizeName($name) {
			$name = strtolower($name);
			$name = str_replace(['-', '_', '.'], ' ', $name);
			$name = ucwords($name);
			$name = str_replace(' ', '', $name);
			if (stripos($name, 'ZendServer') === 0) {
				$name = 'ZendServer_' . substr($name, strlen('ZendServer'));
			}

			return $name;
		}

		/**
		 * Set the frontend directives
		 *
		 * @param  array $directives Assoc of directives
		 * @throws ArgumentOutOfRangeException
		 * @return void
		 */
		public function setDirectives(array $directives) {
			foreach($directives as $name => $value) {
				if (!is_string($name)) {
					require_once 'System/ArgumentOutOfRange.php';
					throw new ArgumentOutOfRangeException("Incorrect option name : $name");
				}
				$name = strtolower($name);
				if (array_key_exists($name, $this->_directives)) {
					$this->_directives[$name] = $value;
				}
			}
		}

		/**
		 * Get the life time
		 *
		 * if $specificLifetime is not false, the given specific life time is used
		 * else, the global lifetime is used
		 *
		 * @param  int $specificLifetime
		 * @return int Cache life time
		 */
		public function getLifetime($specificLifetime) {
			return ($specificLifetime === false) ? $this->_directives['lifetime']: $specificLifetime;
		}

}