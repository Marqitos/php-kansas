<?php
namespace Kansas;

use Exception;
use System\ArgumentOutOfRangeException;
use System\IO\File;
use System\IO\IOException;
use System\Version;
use Kansas\Config;
use function microtime;
use function ini_get;
use function getenv;
use function array_merge;
use function is_string;
use function realpath;
use function Kansas\Http\currentServerRequest;

require_once 'System/Version.php';

class Environment {
  
    private $status;
    private $request;
    private $request_time;
    private $t_inicio;
    private $version;
    private $phpVersion;
    private $theme = ['shared'];
    private static $apacheRequestHeaders = 'apache_request_headers';
    protected static $instance;
    private $fileClass = 'System\IO\File\FileSystem';
    private $specialFolders;
    
    const ENV_CONSTRUCTION	= 'construction';
    const ENV_DEVELOPMENT 	= 'development';
    const ENV_PRODUCTION	= 'production';
    const ENV_TEST			= 'test';

    const SF_PUBLIC 	= 0x0001;
    const SF_HOME		= 0x0002;
    const SF_LIBS		= 0x0004;
    const SF_LAYOUT 	= 0x0008;
    const SF_THEMES 	= 0x0108;
    const SF_TEMP       = 0x000F;
    const SF_CACHE	    = 0x010F;
    const SF_COMPILE	= 0x020F;
    const SF_SESSIONS	= 0x030F;
    const SF_TRACK 	    = 0x040F;
    const SF_ERRORS 	= 0x050F;
    const SF_FILES	    = 0x0010;

    protected function __construct($status, array $specialFolders) {
        $this->t_inicio = microtime(true);
        $this->status = $status;
        $this->specialFolders = $specialFolders;
        $this->version = new Version('0.4');
    }
  
  public static function getInstance($status = null, array $specialFolders = []) {
    if(self::$instance == null) {
      global $environment;
      if(empty($status))
        $status = getenv('APPLICATION_ENV');
      if(empty($status) && defined('APP_ENVIRONMENT'))
        $status = APP_ENVIRONMENT;
      if(empty($status))
        $status = self::ENV_PRODUCTION;
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
  
    public function setTheme($theme) {
        if(is_string($theme))
            $theme = explode(':', $theme);
        if(!is_array($theme)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException();
        }
        if($theme[0] == '') {
            unset($theme[0]);
            $this->theme = array_merge($this->theme, $theme);        
        } else
            $this->theme = $theme;
    }
  
    public function getThemePaths() {
        $func = function($theme) {
            return realpath(
                $this->getSpecialFolder(self::SF_THEMES). '/' . $theme . '/');
        };
        return array_reverse(array_map($func, $this->theme));
    }

    public function getFile($filename, $specialFolder = 0) {
        if($specialFolder != 0) {
            $path = $this->getSpecialFolder($especialFolder);
            $filename = $path . $filename;
        }
        if(class_exists($this->fileClass)) {
            return new $this->fileClass($filename);
        }
    }
  
  public static function log($level, $message) {
    $time = self::$instance->getExecutionTime();
    
    if($message instanceof Exception) {
      $message = $message->getMessage();
      $fileError = $message->getFile();
      $lineError = $message->getLine();
    } elseif(is_array($message)) {
      $fileError = $message['file'];
      $lineError = $message['line'];
      $message = $message['message'];
    }
            
    if(self::$instance->status != self::ENV_DEVELOPMENT && $level != E_USER_WARNING)  
      return;
      
    $level = ($level == E_USER_ERROR)    ? 'ERROR'
               : (($level == E_USER_WARNING) ? 'WARNING'
               : (($level == E_USER_NOTICE)  ? 'NOTICE'
                                             : $level));
    
    echo "<!-- log [" . $level . "]\n" . $time . ' - ' . $message . "\n";
    if(isset($lineError))
      echo $lineError . ' -> ' . $fileError . "\n";
    echo " -->\n";
  }
  
    public function getSpecialFolder($specialFolder) {
        if(!is_int($specialFolder)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            throw new ArgumentOutOfRangeException('Se esperaba un entero');
        }
        if(isset($this->specialFolders[$specialFolder]) &&
           $dir = realpath($this->specialFolders[$specialFolder]))
            return $dir . '/';

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
                    if (File::IsGoodTmpDir($dir)) return realpath($dir) . '/';
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
        throw new ArgumentOutOfRangeException('El valor especificado no es válido');
    }

    // Devuelve posibles valores para una carpeta temporal
    protected static function tmpDirGenerator($tempDir = null) {
        if(is_string($tempDir))
            yield $tempDir;
        foreach ([$_ENV, $_SERVER] as $tab) {
            foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
                if (isset($tab[$key])) {
                if (($key == 'windir') or ($key == 'SystemRoot'))
                    yield realpath($tab[$key] . '\\temp');
                else
                    yield realpath($tab[$key]);
                }
            }
        }
        $upload = ini_get('upload_tmp_dir');
        if ($upload)
            yield realpath($upload);

        if (function_exists('sys_get_temp_dir'))
            yield sys_get_temp_dir();

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
        // Intentar busqueda en cache
        
        // Cargar desde archivo ini
        require_once 'Kansas/Config.php';
        return Config::ParseIni($filename, $iniOptions, $this->getStatus());
    }

    public function getVersion() {
        return $this->version;
    }

    public function getPhpVersion() {
        if(!isset($this->phpVersion))
            $this->phpVersion = new Version(PHP_VERSION);
        return $this->phpVersion;
    }
  
}