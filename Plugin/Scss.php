<?php
/**
 * Plugin para el procesamiento de archivos scss. Compilación y almacenamiento en cache, para devolución css
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
use Kansas\View\Result\Scss as ScssViewResult;
use Leafo\ScssPhp\Compiler;

use function file_get_contents;

require_once 'System/Configurable.php';
require_once 'Kansas/Plugin/PluginInterface.php';
require_once 'Kansas/Controller/Index.php';

class Scss extends Configurable implements PluginInterface {
        
    private $parser;

    public function __construct(array $options = []) {
        parent::__construct($options);
        Index::addAction('scss', [$this, 'controllerAction']);
    }

    /// Miembros de Kansas\Plugin\Interface
    public function getDefaultOptions(string $environment) : array {
        switch ($environment) {
        case 'production':
            return [
            'formater' => 'Leafo\ScssPhp\Formatter\Compressed',
            'cache' => true,
            'environment' => $environment];
        case 'development':
        case 'test':
            return [
            'formater' => 'Leafo\ScssPhp\Formatter\Expanded',
            'cache' => false,
            'environment' => $environment];
        default:
            require_once 'System/NotSupportedException.php';
            throw new NotSupportedException("Entorno no soportado [$environment]");
        }
    }
        
    public function getVersion() : Version {
        global $environment;
        return $environment->getVersion();
    }  

    public function getParser() {
        if($this->parser == null) {
            require_once 'Leafo/ScssPhp/Compiler.php';
            $this->parser = new Compiler();
            $this->parser->addImportPath([$this, 'getFile']);
            $this->parser->setFormatter($this->options['formater']);
        } 
        return $this->parser;
    }
    
    public function getFile($fileName, $first = false) {
        if(file_exists($fileName)) {
            return $fileName;
        }
        $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
        $files = [$fileName];
        if($ext != 'scss' && $ext != 'css') {
            $files[] = $fileName . '.scss';
            $files[] = $fileName . '.css';
            if(!$first) {
                $files[] = dirname($fileName).DIRECTORY_SEPARATOR.'_'.basename($fileName) . '.scss';
                $files[] = dirname($fileName).DIRECTORY_SEPARATOR.'_'.basename($fileName) . '.css';
            }
        }
        if(!$first) {
            $files[] = dirname($fileName).DIRECTORY_SEPARATOR.'_'.basename($fileName);
        }
        global $environment;
        foreach($environment->getThemePaths() as $dir) {
            foreach ($files as $file) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $file);
                if ($path && is_readable($path) && !is_dir($path)) {
                    return $path;
                }
            }
        }
        return false;
    }
    
    public function toCss($fileName, &$md5 = null) {
        global $application;
        $file = $this->getFile($fileName, true);
        if($this->options['cache'] &&
           $cache = $application->hasPlugin('BackendCache')) { // comprobamos si hay una respuesta en cache
            $unchanged = false;
            if($cache->test('scss-list-' . md5($file))) {
                $data = $cache->load('scss-list-' . md5($file));
                $md5 = md5($data);
                $fileList = unserialize($data);
                $unchanged = true;
                foreach($fileList as $path => $crc) { // comprobamos que no haya cambiado ningun archivo
                    if(!is_readable($path) || $crc != hash_file("crc32b", $path)) {
                        $unchanged = false;
                        break;
                    }
                }
            }
            if($unchanged && $cache->test('scss-' . $md5 . '.css')) { // devolvemos respuesta desde cache
                return $cache->load('scss-' . $md5 . '.css');
            }
        }
        $css = $this->getParser()->compile(file_get_contents($file));
        if($md5 !== false ||
           ($this->options['cache'] &&
            $application->hasPlugin('BackendCache'))) { // calculamos md5
            $fileList = [$file => hash_file("crc32b", $file)];
            foreach($this->getParser()->getParsedFiles() as $path => $time) {
                $fileList[$path] = hash_file("crc32b", $path);
            }
            $data = serialize($fileList);
            $md5 = md5($data);
        }
        if($this->options['cache'] &&
           $cache = $application->hasPlugin('BackendCache')) { // guardamos resultado en cache
            $cache->save($data, 'scss-list-' . md5($file));        
            $cache->save($css, 'scss-' . $md5 . '.css', ['scss']);
        }
        return $css;
    }

    public function controllerAction(ControllerInterface $controller, array $vars = []) {
        require_once 'Kansas/View/Result/Scss.php';
        return new ScssViewResult($vars['file']);
    }
    
}