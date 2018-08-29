<?php
namespace Kansas;

use Exception;
use System\ArgumentOutOfRangeException;
use System\IO\File;
use System\Version;
use Kansas\Http\ServerRequest;
use function microtime;
use function Kansas\Http\normalizeServer;
use function Kansas\Http\normalizeUploadedFiles;
use function Kansas\Http\marshalHeadersFromSapi;
use function Kansas\Http\parseCookieHeader;
use function Kansas\Http\marshalUriFromSapi;
use function Kansas\Http\marshalMethodFromSapi;
use function Kansas\Http\marshalProtocolVersionFromSapi;

class Environment {
  
  private $_status;
  private $request;
  private $request_time;
  private $t_inicio;
  private $_version;
  private $_phpVersion;
  private $_theme = ['shared'];
  private static $apacheRequestHeaders = 'apache_request_headers';
  protected static $instance;
  private $fileClass = 'System\IO\File\FileSystem';
  
  const ENV_CONSTRUCTION	= 'construction';
  const ENV_DEVELOPMENT 	= 'development';
  const ENV_PRODUCTION		= 'production';
  const ENV_TEST					= 'test';

  const SF_HOME			= 0x0001;
  const SF_PUBLIC 	= 0x0002;
  const SF_LIBS			= 0x0003;
  const SF_LAYOUTS 	= 0x0004;
  const SF_CACHE		= 0x0005;
  const SF_COMPILE	= 0x0006;
  const SF_FILES		= 0x0007;

  protected function __construct($status) {
    $this->_status = $status;
    $this->t_inicio = microtime(true);
    $this->_version = new Version('0.3');
  }
  
  public static function getInstance($status = null, $t_inicio = null) {
    if(self::$instance == null) {
      global $environment;
      if(empty($status))
        $status = getenv('APPLICATION_ENV');
      if(empty($status) && defined('APP_ENVIRONMENT'))
        $status = APP_ENVIRONMENT;
      if(empty($status))
        $status = self::ENV_PRODUCTION;
      $environment = self::$instance = new self($status, $t_inicio);
    }
    return self::$instance;
  }
  
  public function getStatus() {
    return $this->_status;
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
      require_once 'Kansas/Http/normalizeServer.php';
      $server = normalizeServer(
        $server ?: $_SERVER,
        is_callable(self::$apacheRequestHeaders) ? self::$apacheRequestHeaders : null
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

      $this->request = new ServerRequest(
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
    return $this->request;
  }
  
  public function setTheme($theme) {
    if(is_string($theme))
      $theme = explode(':', $theme);
    if(!is_array($theme)) {
      throw new ArgumentOutOfRangeException();
    }
    if($theme[0] == '') {
      unset($theme[0]);
      $this->_theme = array_merge($this->_theme, $theme);        
    } else
      $this->_theme = $theme;
  }
  
  public function getViewPaths() {
    $func = function($theme) {
      return realpath(LAYOUTS_PATH . $theme . '/');
    };
    return array_reverse(array_map($func, $this->_theme));
  }
  
  public function getThemePaths() {
    $func = function($theme) {
      return realpath(THEMES_PATH . $theme . '/');
    };
    return array_reverse(array_map($func, $this->_theme));
  }

  public function getFile($filename, $specialFolder) {
    if($specialFolder != 0) {
      require_once 'System/NotSuportedException.php';
      throw new NotSuportedException();
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
            
    if(self::$instance->_status != self::ENV_DEVELOPMENT && $level != E_USER_WARNING)  
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
  
/**
  * Determine system TMP directory and detect if we have read access
  * inspired from Zend_File_Transfer_Adapter_Abstract
  *
  * @return string
  * @throws System_IO_Exception if unable to determine directory
  */
  public static function getTmpDir() {
    foreach ([$_ENV, $_SERVER] as $tab) {
      foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
        if (isset($tab[$key])) {
          if (($key == 'windir') or ($key == 'SystemRoot'))
            $dir = realpath($tab[$key] . '\\temp');
          else
            $dir = realpath($tab[$key]);
          if (File::IsGoodTmpDir($dir))
            return $dir;
        }
      }
    }
    $upload = ini_get('upload_tmp_dir');
    if ($upload) {
      $dir = realpath($upload);
      if (File::IsGoodTmpDir($dir))
        return $dir;
    }
    if (function_exists('sys_get_temp_dir')) {
      $dir = sys_get_temp_dir();
      if ($this->_isGoodTmpDir($dir))
        return $dir;
    }
    // Attemp to detect by creating a temporary file
    $tempFile = tempnam(md5(uniqid(rand(), TRUE)), '');
    if ($tempFile) {
      $dir = realpath(dirname($tempFile));
      unlink($tempFile);
      if (File::IsGoodTmpDir($dir))
        return $dir;
    }
    if (File::IsGoodTmpDir('/tmp'))
      return '/tmp';
    if (File::IsGoodTmpDir('\\temp'))
      return '\\temp';
    require_once 'System/IO/IOException.php';
    throw new System_IO_Exception('Could not determine temp directory, please specify a temp directory manually');
  }


  public function getVersion() {
    return $this->_version;
  }

  public function getPhpVersion() {
    if(!isset($this->_phpVersion))
      $this->_phpVersion = new System_Version(PHP_VERSION);
    return $this->_phpVersion;
  }
  
}