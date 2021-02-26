<?php
/**
 * Proporciona información relacionada con las carpetas, tiempo de ejecución, la petición actual e idiomas
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.1
 */

 namespace Kansas;

use Exception;
use System\ArgumentOutOfRangeException;
use System\Collections\KeyNotFoundException;
use System\IO\File;
use System\IO\IOException;
use System\Version;
use Kansas\Config;
use Kansas\PluginLoader;
use Kansas\Controller\ControllerInterface;
use Kansas\Loader\NotCastException;
use Kansas\Plugin\PluginInterface;
use function microtime;
use function ini_get;
use function getenv;
use function array_merge;
use function is_string;
use function realpath;
use function Kansas\Http\currentServerRequest;

require_once 'System/Version.php';

/**
 * Objeto singleton con valores de entorno.
 * Carpetas especiales, temas, información sobre la solicitud actual, ...
 */
class Environment {
  
    private $status;
    private $request;
    private $requestTime;
    private $t_inicio;
    private $version;
    private $phpVersion;
    private $theme = ['shared'];
    private static $apacheRequestHeaders = 'apache_request_headers';
    protected static $instance;
    private $fileClass = 'System\IO\File\FileSystem';
    private $specialFolders;
    private $loaders = [
        'controller' => ['Kansas\\Controller\\' => 'Kansas/Controller/'],
        'plugin'     => ['Kansas\\Plugin\\'	    => 'Kansas/Plugin/'],
        'provider'	 => ['Kansas\\Provider\\'   => 'Kansas/Provider/']
    ];
    
    // Posibles valores de Status
    const ENV_CONSTRUCTION	= 'construction';
    const ENV_DEVELOPMENT 	= 'development';
    const ENV_PRODUCTION	= 'production';
    const ENV_TEST			= 'test';

    // Posibles valores para SpecialFolder
    const SF_PUBLIC 	= 0x0001;
    const SF_HOME		= 0x0002;
    const SF_LIBS		= 0x0004;
    const SF_LAYOUT 	= 0x0008;
    const SF_TEMP       = 0x0010;
    const SF_FILES	    = 0x0020;
    const SF_THEMES 	= 0x0108;
    const SF_CACHE	    = 0x0110;
    const SF_COMPILE	= 0x0210;
    const SF_SESSIONS	= 0x0310;
    const SF_TRACK 	    = 0x0410;
    const SF_ERRORS 	= 0x0510;

    protected function __construct($status, array $specialFolders) {
        $this->t_inicio = microtime(true);
        $this->status = $status;
        $this->specialFolders = $specialFolders;
        $this->version = new Version('0.4');
    }
  
    public static function getInstance($status = null, array $specialFolders = []) {
        if(self::$instance == null) {
            global $environment;
            if(empty($status)) {
                $status = defined('APP_ENVIRONMENT')
                    ? APP_ENVIRONMENT
                    : self::ENV_PRODUCTION;
            }
            $environment = self::$instance = new self($status, $specialFolders);
        }
        return self::$instance;
    }
  
    public function getStatus() {
        return $this->status;
    }
  
    public function getRequestTime() {
        if(!isset($this->requestTime)) {
            $serverParams = $this->getRequest()->getServerParams();
            if(isset($serverParams['REQUEST_TIME_FLOAT'])) {
                $this->requestTime = $serverParams['REQUEST_TIME_FLOAT'];
            } else if(isset($serverParams['REQUEST_TIME'])) {
                $this->requestTime = $serverParams['REQUEST_TIME'];
            } else {
                $this->requestTime = $this->t_inicio;
            }
        }
        return $this->requestTime;
    }

    public function getExecutionTime() {
        return microtime(true) - $this->getRequestTime();
    }
  
    public function getRequest(array $server = null, array $query = null, array $body = null, array $cookies = null, array $files = null) {
        if(!isset($this->request)) {
            require_once 'Kansas/Http/currentServerRequest.php';
            $this->request = currentServerRequest($server, $query, $body, $cookies, $files, self::$apacheRequestHeaders);
        }
        return $this->request;
    }
  
    public function setTheme($theme, $add = false) {
        if(is_string($theme)) {
            $theme = [$theme];
        }
        if(!is_array($theme)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('theme');
        }
        $this->theme = $add
            ? array_merge($this->theme, $theme)
            : $theme;
    }
  
    public function getThemePaths() {
        $func = function($theme) {
            return realpath(
                $this->getSpecialFolder(self::SF_THEMES). '/' . $theme . '/');
        };
        return array_map($func, $this->theme);
    }

    public function getFile($filename, $specialFolder = 0) {
        if($specialFolder != 0) {
            $path = $this->getSpecialFolder($specialFolder);
            $filename = $path . $filename;
        }
        if(class_exists($this->fileClass)) {
            return new $this->fileClass($filename);
        }
    }
  
    public static function log($level, $message) {
        $time = self::$instance->getExecutionTime();
        
        if($message instanceof Exception) {
            $fileError = $message->getFile();
            $lineError = $message->getLine();
            $message = $message->getMessage();
        } elseif(is_array($message)) {
            $fileError = $message['file'];
            $lineError = $message['line'];
            $message = $message['message'];
        }
                
        if(self::$instance->status != self::ENV_DEVELOPMENT && 
           $level != E_USER_WARNING) {
            return;
        }
        
        $level =  ($level == E_USER_ERROR)   ? 'ERROR'
               : (($level == E_USER_WARNING) ? 'WARNING'
               : (($level == E_USER_NOTICE)  ? 'NOTICE'
                                             : $level));
        
        echo "<!-- log [" . $level . "]\n" . $time . ' - ' . $message . "\n";
        if(isset($lineError)) {
            echo $lineError . ' -> ' . $fileError . "\n";
        }
        echo " -->\n";
    }
  
    public function getSpecialFolder($specialFolder) {
        if(!is_int($specialFolder)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('Se esperaba un entero');
        }
        if(isset($this->specialFolders[$specialFolder]) &&
           $dir = realpath($this->specialFolders[$specialFolder])) {
            return $dir . '/';
        }

        switch($specialFolder) {
            case self::SF_PUBLIC:
                return realpath(dirname(__FILE__) . '/../../public') . '/';
            case self::SF_HOME:
                return realpath(dirname(__FILE__) . '/../../..') . '/';
            case self::SF_LIBS:
                return realpath(dirname(__FILE__) . '/..') . '/';
            case self::SF_LAYOUT:
                return realpath(dirname(__FILE__) . '/../../layout') . '/';
            case self::SF_THEMES:
                return realpath(dirname(__FILE__) . '/../../themes') . '/';
            case self::SF_TEMP:
                require_once 'System/IO/File.php';
                foreach(self::tmpDirGenerator(dirname(__FILE__) . '/../../../tmp') as $dir) {
                    if (File::IsGoodTmpDir($dir)) {
                        return realpath($dir) . '/';
                    }
                }
                require_once 'System/IO/IOException.php';
                throw new IOException('No se puede determinar un directorio temporal, especifique uno manualmente.');
            case self::SF_CACHE:
                return realpath($this->getSpecialFolder(self::SF_TEMP) . 'cache') . '/';
            case self::SF_COMPILE:
                return realpath($this->getSpecialFolder(self::SF_TEMP) . 'view-compile') . '/';
            case self::SF_SESSIONS:
                return realpath($this->getSpecialFolder(self::SF_TEMP) . 'sessions') . '/';
            case self::SF_TRACK:
                return realpath($this->getSpecialFolder(self::SF_TEMP) . 'log/hints') . '/';
            case self::SF_ERRORS:
                return realpath($this->getSpecialFolder(self::SF_TEMP) . 'log/errors') . '/';
            case self::SF_FILES:
                return realpath(dirname(__FILE__) . '/../../../private');
        }
        require_once 'System/ArgumentOutOfRangeException.php';
        throw new ArgumentOutOfRangeException('specialFolder', 'El valor especificado no es válido');
    }

    // Devuelve posibles valores para una carpeta temporal
    protected static function tmpDirGenerator($tempDir = null) {
        if(is_string($tempDir)) {
            yield $tempDir;
        }
        foreach ([$_ENV, $_SERVER] as $tab) {
            foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
                if (isset($tab[$key])) {
                    if (($key == 'windir') || ($key == 'SystemRoot')) {
                        yield realpath($tab[$key] . '\\temp');
                    } else {
                        yield realpath($tab[$key]);
                    }
                }
            }
        }
        $upload = ini_get('upload_tmp_dir');
        if ($upload) {
            yield realpath($upload);
        }

        if (function_exists('sys_get_temp_dir')) {
            yield sys_get_temp_dir();
        }

        // Attemp to detect by creating a temporary file
        $tempFile = tempnam(md5(uniqid(rand(), TRUE)), '');
        if ($tempFile) {
            $dir = realpath(dirname($tempFile));
            unlink($tempFile);
            yield $dir;
        }
        yield '/tmp';
        yield '\\temp';
    }

    public function getConfig($filename, array $iniOptions = []) {
        // TODO: Intentar busqueda en cache
        // Cargar desde archivo ini
        require_once 'Kansas/Config.php';
        return Config::ParseIni($filename, $iniOptions, $this->getStatus());
    }

    public function getVersion() {
        return $this->version;
    }

    public function getPhpVersion() {
        if(!isset($this->phpVersion)) {
            $this->phpVersion = new Version(PHP_VERSION);
        }
        return $this->phpVersion;
    }

    protected function getLoader($loaderName) {
        if(!isset($this->loaders[$loaderName])) {
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        if(is_array($this->loaders[$loaderName])) {
            require_once 'Kansas/PluginLoader.php';
            $this->loaders[$loaderName] = new PluginLoader($this->loaders[$loaderName]);
        }
        return $this->loaders[$loaderName];
    }

    public function createController($controllerName) {
        $controllerClass = $this->getLoader('controller')->load($controllerName);
        $class           = new $controllerClass();
        require_once 'Kansas/Controller/ControllerInterface.php';
        if(!$class instanceof ControllerInterface) {
            require_once 'Kansas/Loader/NotCastException.php';
            throw new NotCastException($controllerName, 'ControllerInterface');
        }
        return $class;
    }

    public function createPlugin($pluginName, array $options) {
        $pluginClass = $this->getLoader('plugin')->load($pluginName);
        $class       = new $pluginClass($options);
        require_once 'Kansas/Plugin/PluginInterface.php';
        if(!$class instanceof PluginInterface) {
            require_once 'Kansas/Loader/NotCastException.php';
            throw new NotCastException($pluginName, 'PluginInterface');
        }
        return $class;
    }

    public function createProvider($providerName) { 
        $providerClass = $this->getLoader('provider')->load($providerName);
        return new $providerClass();
    }

    public function addLoaderPaths($loaderName, $options) {
        if(!isset($this->loaders[$loaderName])) {
            var_dump($loaderName, $options);
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        require_once 'Kansas/PluginLoader.php';
        if($this->loaders[$loaderName] instanceof PluginLoader) {
            foreach($options as $prefix => $path) {
                $this->loaders[$loaderName]->addPrefixPath($prefix, realpath($path));
            }
        } else {
            $this->loaders[$loaderName] = array_merge($this->loaders[$loaderName], $options);
        }
    }

}
