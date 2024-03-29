<?php declare(strict_types = 1 );
/**
 * Proporciona información relacionada con las carpetas, tiempo de ejecución, la petición actual e idiomas
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.1
 */

namespace Kansas;

use Generator;
use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use System\ArgumentOutOfRangeException;
use System\Collections\KeyNotFoundException;
use System\IO\File;
use System\IO\IOException;
use System\Localization\Resources as SysResources;
use System\Version;
use Kansas\Config;
use Kansas\PluginLoader;
use Kansas\Controller\ControllerInterface;
use Kansas\Http\ServerRequest;
use Kansas\Localization\Resources;
use Kansas\Plugin\PluginInterface;

use function array_merge;
use function constant;
use function microtime;
use function ini_get;
use function is_string;
use function rand;
use function realpath;
use function Kansas\Http\currentServerRequest;

require_once 'Psr/Http/Message/ServerRequestInterface.php';
require_once 'System/Version.php';
require_once 'Kansas/PluginLoader.php';
require_once 'Kansas/Controller/ControllerInterface.php';
require_once 'Kansas/Http/ServerRequest.php';
require_once 'Kansas/Plugin/PluginInterface.php';

/**
 * Objeto singleton con valores de entorno.
 * Carpetas especiales, temas, información sobre la solicitud actual, ...
 */
class Environment {
  
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

    protected static $instance;
    private $status;
    private $request;
    private $requestTime;
    private $t_inicio;
    private $version;
    private $phpVersion;
    private $specialFolders;
    private $theme          = ['shared'];
    private $fileClass      = 'System\IO\File\FileSystem';
    private $loaders        = [
        'controller'        => ['Kansas\\Controller\\'  => 'Kansas/Controller/'],
        'plugin'            => ['Kansas\\Plugin\\'	    => 'Kansas/Plugin/'],
        'provider'	        => []];
    private $specialFolderParts = [
        self::SF_PUBLIC     => '/../../public',
        self::SF_HOME       => '/../../..',
        self::SF_LIBS       => '/..',
        self::SF_LAYOUT     => '/../../layout',
        self::SF_TEMP       => '/../../../tmp',
        self::SF_THEMES     => '/../../themes',
        self::SF_FILES      => '/../../../private'];
    private $tempFolderParts = [
        self::SF_CACHE      => '/cache',
        self::SF_COMPILE    => '/view-compile',
        self::SF_SESSIONS   => '/sessions',
        self::SF_TRACK      => '/log/hints',
        self::SF_ERRORS     => '/log/errors'];
    private static $apacheRequestHeaders = 'apache_request_headers';
    

    protected function __construct(string $status, array $specialFolders) {
        $this->t_inicio = microtime(true);
        $this->status = $status;
        $this->specialFolders = $specialFolders;
        foreach($specialFolders as $key => $value) {
            if($value === false) {
                unset($this->specialFolders[$key]);
            }
        }
        $this->version = new Version('0.4');
    }
  
    public static function getInstance(string $status = null, array $specialFolders = []) : self {
        if(self::$instance == null) {
            global $environment;
            if(empty($status)) {
                $status = defined('APP_ENVIRONMENT')
                    ? constant("APP_ENVIRONMENT")
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

    public function getExecutionTime() : float {
        return microtime(true) - $this->getRequestTime();
    }
  
    public function getRequest(array $server = null, array $query = null, array $body = null, array $cookies = null, array $files = null) : ServerRequest {
        if(!isset($this->request)) {
            require_once 'Kansas/Http/currentServerRequest.php';
            $this->request = currentServerRequest($server, $query, $body, $cookies, $files, self::$apacheRequestHeaders);
        }
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request) : void {
        $this->request = $request;
    }
  
    public function setTheme($theme, $add = false) : void {
        if(is_string($theme)) {
            $theme = [$theme];
        }
        if(!is_array($theme)) {
            require_once 'System/ArgumentOutOfRangeException.php';
            require_once 'Kansas/Localization/Resources.php';
            throw new ArgumentOutOfRangeException('theme', Resources::ARGUMENT_OUT_OF_RANGE_EXCEPTION_ARRAY_STRING_EXPECTED_MESSAGE, $theme);
        }
        $this->theme = $add
            ? array_merge($this->theme, $theme)
            : $theme;
    }
  
    public function getThemePaths() : Generator {
        $themeFolder = $this->getSpecialFolder(self::SF_THEMES);
        foreach($this->theme as $theme) {
            $dir = realpath($themeFolder . DIRECTORY_SEPARATOR . $theme);
            if($dir) {
                yield $dir . DIRECTORY_SEPARATOR;
            }
        }
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
        
        if($message instanceof Throwable) {
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
  
    public function getSpecialFolder(int $specialFolder) {
        if(isset($this->specialFolders[$specialFolder])) {
            $dir = realpath($this->specialFolders[$specialFolder]);
        } elseif(isset($this->specialFolderParts[$specialFolder])) {
            $part = $this->specialFolderParts[$specialFolder];
        } elseif(isset($this->tempFolderParts[$specialFolder])) {
            $tmpPart = $this->tempFolderParts[$specialFolder];
        } else {
            require_once 'System/ArgumentOutOfRangeException.php';
            require_once 'System/Localization/Resources.php';
            throw new ArgumentOutOfRangeException('specialFolder', SysResources::ARGUMENT_OUT_OF_RANGE_EXCEPTION_DEFAULT_MESSAGE, $specialFolder);
        }
        if($specialFolder == self::SF_TEMP || 
            isset($tmpPart)) {
            $dir = $this->getTempDir($part);
            if(isset($tmpPart)) {
                $dir = realpath($dir . $tmpPart);
            }
        } elseif(isset($part)) {
            $dir = realpath($dir . $part);
        } 
        if($dir) {
            return $dir . DIRECTORY_SEPARATOR;
        }
        return false;
    }

    private function getTempDir($default) : string {
        require_once 'System/IO/File.php';
        if(File::IsGoodTmpDir($default)) {
            return realpath($default);
        }
        foreach(self::tmpDirGenerator(__DIR__ . '/../../../tmp') as $dir) {
            if(File::IsGoodTmpDir($dir)) {
                return realpath($dir);
            }
        }
        require_once 'System/IO/IOException.php';
        require_once 'Kansas/Localization/Resources.php';
        throw new IOException(Resources::IO_EXCEPTION_NO_TEMP_DIR_MESSAGE);
    }

    // Devuelve posibles valores para una carpeta temporal
    protected static function tmpDirGenerator($tempDir = null) {
        if(is_string($tempDir)) {
            yield $tempDir;
        }
        foreach ([$_ENV, $_SERVER] as $tab) {
            foreach (['TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot'] as $key) {
                if (isset($tab[$key])) {
                    yield (($key == 'windir') || ($key == 'SystemRoot'))
                        ? realpath($tab[$key] . DIRECTORY_SEPARATOR . 'temp')
                        : realpath($tab[$key]);
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
        $tempFile = tempnam(md5(uniqid((string)rand(), TRUE)), '');
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

    public function getVersion() : Version {
        return $this->version;
    }

    public function getPhpVersion() {
        if(!isset($this->phpVersion)) {
            $this->phpVersion = new Version(PHP_VERSION);
        }
        return $this->phpVersion;
    }

    protected function getLoader($loaderName) : PluginLoader {
        if(!isset($this->loaders[$loaderName])) {
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        if(is_array($this->loaders[$loaderName])) {
            $this->loaders[$loaderName] = new PluginLoader($this->loaders[$loaderName]);
        }
        return $this->loaders[$loaderName];
    }

    public function createController($controllerName) : ControllerInterface {
        $controllerClass = $this->getLoader('controller')->load($controllerName);
        return new $controllerClass();
    }

    public function createPlugin($pluginName, array $options) : PluginInterface {
        $pluginClass = $this->getLoader('plugin')->load($pluginName);
        return new $pluginClass($options);
    }

    public function createProvider($providerName) { 
        $providerClass = $this->getLoader('provider')->load($providerName);
        return new $providerClass();
    }

    public function addLoaderPaths($loaderName, $options) : void {
        if(!isset($this->loaders[$loaderName])) {
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        if($this->loaders[$loaderName] instanceof PluginLoader) {
            foreach($options as $prefix => $path) {
                $this->loaders[$loaderName]->addPrefixPath($prefix, realpath($path));
            }
        } else {
            $this->loaders[$loaderName] = array_merge($this->loaders[$loaderName], $options);
        }
    }

}
