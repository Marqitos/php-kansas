<?php declare(strict_types = 1);
/**
  * Proporciona información relacionada con las carpetas, tiempo de ejecución, la petición actual e idiomas
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.1
  */

namespace Kansas;

use Kansas\PluginLoader;
use Kansas\Controller\ControllerInterface;
use Kansas\Http\ServerRequest;
use Kansas\Localization\Resources;
use Kansas\Plugin\PluginInterface;
use Psr\Http\Message\ServerRequestInterface;
use System\ArgumentOutOfRangeException;
use System\Collections\KeyNotFoundException;
use System\EnvStatus;
use System\IO\File;
use System\IO\IOException;
use System\Localization\Resources as SysResources;
use System\Version;

use function constant;
use function microtime;
use function ini_get;
use function is_string;
use function rand;
use function realpath;
use function Kansas\Http\currentServerRequest;

require_once 'PluginLoader.php';
require_once 'Kansas/Controller/ControllerInterface.php';
require_once 'Kansas/Http/ServerRequest.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Psr/Http/Message/ServerRequestInterface.php';
require_once 'System/EnvStatus.php';
require_once 'System/Version.php';

/**
  * Objeto singleton con valores de entorno.
  * Carpetas especiales, temas, información sobre la solicitud actual, ...
  */
class Environment {

    // Posibles valores para SpecialFolder
    const SF_PUBLIC     = 0x0001;
    const SF_HOME       = 0x0002;
    const SF_LIBS       = 0x0004;
    const SF_LAYOUT     = 0x0008;
    const SF_TEMP       = 0x0010;
    const SF_FILES      = 0x0020;
    const SF_THEMES     = 0x0108;
    const SF_CACHE      = 0x0110;
    const SF_COMPILE    = 0x0210;
    const SF_SESSIONS   = 0x0310;
    const SF_TRACK      = 0x0410;
    const SF_ERRORS     = 0x0510;
    const SF_V_CACHE    = 0x0610;

    protected static $instance;
    private $request;
    private $requestTime;
    private $tStart;
    private $version;
    private $phpVersion;
    private $fileClass      = 'System\IO\File\FileSystem';
    private $loaders        = [
        'controller'        => ['Kansas\\Controller\\'  => 'Kansas/Controller/'],
        'plugin'            => ['Kansas\\Plugin\\'      => 'Kansas/Plugin/'],
        'provider'          => []];
    private $specialFolderParts = [
        self::SF_PUBLIC     => '/../../public',
        self::SF_HOME       => '/../..',
        self::SF_LIBS       => '/..',
        self::SF_LAYOUT     => '/../../layout',
        self::SF_TEMP       => '/../../tmp',
        self::SF_FILES      => '/../../private'];
    private $tempFolderParts = [
        self::SF_CACHE      => '/cache',
        self::SF_V_CACHE    => '/cache/view',
        self::SF_COMPILE    => '/view-compile',
        self::SF_SESSIONS   => '/sessions',
        self::SF_TRACK      => '/log/hints',
        self::SF_ERRORS     => '/log/errors'];
    private static $apacheRequestHeaders = 'apache_request_headers';

    protected function __construct(
        private EnvStatus $status,
        private array $specialFolders
    ) {
        $this->tStart = microtime(true);
        $this->version = new Version('0.6');
    }

## Patrón Singleton
    public static function getInstance(EnvStatus $status, array $specialFolders = []) : self {
        if (self::$instance == null) {
            self::$instance = new self($status, $specialFolders);
        }
        return self::$instance;
    }
## -- Singleton

    public static function getStatus(): EnvStatus {
        return self::$instance->status;
    }

    public static function getRequestTime() {
        if (!isset(self::$instance->requestTime)) {
            $serverParams = self::$instance->getRequest()->getServerParams();
            if (isset($serverParams['REQUEST_TIME_FLOAT'])) {
                self::$instance->requestTime = $serverParams['REQUEST_TIME_FLOAT'];
            } elseif (isset($serverParams['REQUEST_TIME'])) {
                self::$instance->requestTime = $serverParams['REQUEST_TIME'];
            } else {
                self::$instance->requestTime = self::$instance->tStart;
            }
        }
        return self::$instance->requestTime;
    }

    public static function getExecutionTime() : float {
        return microtime(true) - self::$instance->getRequestTime();
    }

    public static function getRequest(?array $server = null, ?array $query = null, ?array $body = null, ?array $cookies = null, ?array $files = null) : ServerRequest {
        if (!isset(self::$instance->request)) {
            require_once 'Kansas/Http/currentServerRequest.php';
            self::$instance->request = currentServerRequest($server, $query, $body, $cookies, $files, self::$apacheRequestHeaders);
        }
        return self::$instance->request;
    }

    public static function setRequest(ServerRequestInterface $request) : void {
        self::$instance->request = $request;
    }

    public static function getFile($filename, $specialFolder = 0) {
        if ($specialFolder != 0) {
        $path = self::$instance->getSpecialFolder($specialFolder);
        $filename = $path . $filename;
        }
        if (class_exists(self::$instance->fileClass)) {
        return new self::$instance->fileClass($filename);
        }
    }

    public static function getSpecialFolder(int $specialFolder): string|false {
        if (isset(self::$instance->specialFolders[$specialFolder])) {
            $dir        = realpath(self::$instance->specialFolders[$specialFolder]);
        } elseif (isset(self::$instance->specialFolderParts[$specialFolder])) {
            $part       = self::$instance->specialFolderParts[$specialFolder];
        } elseif (isset(self::$instance->tempFolderParts[$specialFolder])) {
            $tmpPart    = self::$instance->tempFolderParts[$specialFolder];
            $part       = self::$instance->specialFolderParts[self::SF_TEMP];
        } else {
            require_once 'System/ArgumentOutOfRangeException.php';
            require_once 'System/Localization/Resources.php';
            throw new ArgumentOutOfRangeException('specialFolder', SysResources::E_ARGUMENT_OUT_OF_RANGE, $specialFolder);
        }
        if ($specialFolder == self::SF_TEMP ||
            isset($tmpPart)) {
            $dir = self::$instance->getTempDir($part);
            if (isset($tmpPart)) {
                $dir = realpath($dir . $tmpPart);
            }
        } elseif (isset($part)) {
            $dir = realpath(__DIR__ . $part);
        }
        if ($dir) {
            return $dir . DIRECTORY_SEPARATOR;
        } elseif (self::$instance->status == EnvStatus::DEVELOPMENT) {
            var_dump($dir, $specialFolder);
        }
        return false;
    }

    private function getTempDir($default): string {
        require_once 'System/IO/File.php';
        if (realpath(__DIR__ . $default) &&
            File::IsGoodTmpDir(realpath(__DIR__ . $default))) {
            return realpath(__DIR__ . $default);
        } elseif (realpath($default) &&
                  File::IsGoodTmpDir(realpath($default))) {
            return realpath($default);
        } elseif (self::$instance->status == EnvStatus::DEVELOPMENT) {
            var_dump (__DIR__ . $default, realpath(__DIR__ . $default),
                      $default, realpath($default));
        }

        foreach(self::tmpDirGenerator(__DIR__ . self::SF_TEMP) as $dir) {
            if(File::IsGoodTmpDir($dir)) {
                return realpath($dir);
            } elseif (self::$instance->status == EnvStatus::DEVELOPMENT) {
                var_dump ($dir, realpath($dir));
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

    // Attempt to detect by creating a temporary file
    $tempFile = tempnam(bin2hex(random_bytes(16)), '');
    if ($tempFile) {
      $dir = realpath(dirname($tempFile));
      unlink($tempFile);
      yield $dir;
    }
    yield '/tmp';
    yield '\\temp';
  }

    public static function getVersion() : Version {
        return self::$instance->version;
    }

    public static function getPhpVersion() {
        if (!isset(self::$instance->phpVersion)) {
        self::$instance->phpVersion = new Version(PHP_VERSION);
        }
        return self::$instance->phpVersion;
    }

    protected static function getLoader($loaderName) : PluginLoader {
        if (!isset(self::$instance->loaders[$loaderName])) {
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        if (is_array(self::$instance->loaders[$loaderName])) {
            self::$instance->loaders[$loaderName] = new PluginLoader(self::$instance->loaders[$loaderName]);
        }
        return self::$instance->loaders[$loaderName];
    }

    public static function createController($controllerName) : ControllerInterface {
        $controllerClass = self::$instance->getLoader('controller')->load($controllerName);
        return new $controllerClass();
    }

    public static function createPlugin($pluginName, array $options) : PluginInterface {
        $pluginClass = self::$instance->getLoader('plugin')->load($pluginName);
        return new $pluginClass($options);
    }

    public static function createProvider($providerName) {
        $providerClass = self::$instance->getLoader('provider')->load($providerName);
        return new $providerClass();
    }

    public static function addLoaderPaths($loaderName, $options) : void {
        if (!isset(self::$instance->loaders[$loaderName])) {
            require_once 'System/Collections/KeyNotFoundException.php';
            throw new KeyNotFoundException();
        }
        if (self::$instance->loaders[$loaderName] instanceof PluginLoader) {
            $loader = self::$instance->loaders[$loaderName];
            foreach($options as $prefix => $path) {
                $loader->addPrefixPath($prefix, realpath($path));
            }
        } elseif (isset(self::$instance->loaders[$loaderName])) {
            self::$instance->loaders[$loaderName] = [...self::$instance->loaders[$loaderName], ...$options];
        } else {
            self::$instance->loaders[$loaderName] = $options;
        }
    }

}
