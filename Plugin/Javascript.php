<?php declare(strict_types = 1);
/**
 * Plugin el procesamiento de archivos javascript. Une, compacta y devuelve el código solicitado
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.4
 */

namespace Kansas\Plugin;

use System\Configurable;
use System\NotSupportedException;
use System\Version;
use Kansas\Controller\ControllerInterface;
use Kansas\Controller\Index;
use Kansas\Plugin\PluginInterface;
use Kansas\View\Result\Javascript as ViewResultJavascript;
use JShrink\Minifier;
use Packager;

use function hash_file;
use function is_readable;
use function md5;
use function serialize;
use function unserialize;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';

class Javascript extends Configurable implements PluginInterface {
  
    private $packager;
  
    public function __construct(array $options = []) {
        require_once 'Kansas/Controller/Index.php';
        parent::__construct($options);
        Index::addAction('javascript', [self::class, 'controllerAction']); // Añade una acción al controlador principal
    }
  
    /// Miembros de Kansas\Plugin\Interface
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
        case 'production':
            return [
                'packages' => [],
                'minifier' => [
                    'flaggedComments' => false],
                'verifyFiles' => false]; // En producción no comprobamos cambios en los archivos individuales
                case 'development':
        case 'test':
            return [
                'packages' => [],
                'minifier' => false, // En entorno de desarrollo no minimiza la salida de javascript
                'verifyFiles' => true];
        default:
            require_once 'System/NotSupportedException.php';
            throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }

    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }

    public function getPackager() {
        if($this->packager == null){
            require_once 'packager/packager.php';
            $this->packager = new Packager($this->options['packages']);
        }
        return $this->packager;
    }
  
    public function build($components, &$md5 = null) {
        global $application;
        $md5 = false;
        if($cache = $application->hasPlugin('BackendCache')) { // Se puede obtener el resultado javascript de cache
            if($cache->test('js-' . md5(serialize($components)))) {
                $data = $cache->load('js-' . md5(serialize($components)));
                $md5 = md5($data);
                if($this->options['verifyFiles']) {
                    $dataList = unserialize($data);
                    if($dataList['packages'] == $this->options['packages']) {
                        foreach($dataList['files'] as $path => $crc) { // Comprueba si alguno de los archivos ha cambiado
                            if(!is_readable($path) || $crc != hash_file("crc32b", $path)) {
                                $md5 = false;
                                break;
                            }
                        }
                    } else {
                        $md5 = false;
                    }
                }
            }
            if($md5 !== false && $cache->test('js-' . $md5)) { // Si no hay cambios devuelve desde cache
                return $cache->load('js-' . $md5);
            }
        }
        $jsCode = $this->javascriptFromComponents($components, $this->options['minifier']);
        if($cache = $application->hasPlugin('BackendCache')) { // Se puede guardar el resultado javascript en cache
            $files = $this->getPackager()->components_to_files($components);
            $fileList = $this->getPackager()->complete_files($files);
            $dataList = [
                'packages'  => $this->options['packages'],
                'files'     => []];
            foreach($fileList as $file) {
                $filePath = $this->getPackager()->get_file_path($file);
                $dataList['files'][$filePath] = hash_file("crc32b", $filePath);
            }
            $data = serialize($dataList);
            $md5 = md5($data);
            $cache->save($data, 'js-' . md5(serialize($components)), ['javascript', 'js-index']);
            $cache->save($jsCode, 'js-' . $md5, ['javascript', 'js-code']);
        }
        return $jsCode;
    }

    protected function buildFromComponents(array $components, array $exclude, &$md5 = null) {
        if($md5 !== false) {
            $files = $this->getPackager()->components_to_files($components);
            $fileList = $this->getPackager()->complete_files($files);
            $dataList = [
                'packages'  => $this->options['packages'],
                'files'     => []];
            foreach($fileList as $file) {
                $dataList['files'][$this->getPackager()->get_file_path($file)] = hash_file("crc32b", $this->getPackager()->get_file_path($file));
            }
            $data = serialize($dataList);
            $md5 = md5($data);
            return $this->javascriptFromFiles($files, $this->options['minifier']);
        }
        return $this->javascriptFromComponents($components, $this->options['minifier']);
    }
  
    public function javascriptFromComponents($components, $minifier = false) {
        $jsCode = $this->getPackager()->build_from_components($components);
        return self::minifier($jsCode, $minifier);
    }

    public function javascriptFromFiles($files, $minifier = false) {
        $jsCode = $this->getPackager()->build_from_files($files);
        return self::minifier($jsCode, $minifier);
    }

    /**
     * Comprime el código javascript
     */
    public static function minifier($jsCode, $minifier) {
        if($minifier && !class_exists('Minifier', false) && is_readable('JShrink/Minifier.php')) {
            require_once 'JShrink/Minifier.php';
        }
        if($minifier && class_exists('Minifier')) {
            $jsCode = Minifier::minify($jsCode, $minifier);
        }
        return $jsCode;
    }

    public static function controllerAction(ControllerInterface $controller, array $vars) {
        if(isset($vars['component'])) {
            require_once 'Kansas/View/Result/Javascript.php';
            return new ViewResultJavascript($vars['component']);
        }
        return false;
    }
   
}
