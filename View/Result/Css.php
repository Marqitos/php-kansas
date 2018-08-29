<?php

namespace Kansas\View\Result;

use System\Guid;
use Kansas\Environment;
use Kansas\View\Result\StringAbstract;
use function bin2hex;
use function chr;
use function file_get_contents;
use function hexdec;
use function md5_file;
use function preg_replace;
use function substr;
use function urlencode;

require_once('Kansas/View/Result/StringAbstract.php');

class Css	extends StringAbstract {
    
    private $files;
  
    public function __construct($files) {
        $this->files = (array)$files;
        parent::__construct('text/css; charset= UTF-8');    
    }
  
    public static function compress($buffer) {
        // remove comments 
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        return $buffer;
    }
  
    public function getCacheId() {
        require_once 'System/Guid.php';
        $hash = Guid::getEmpty()->getRaw();
        foreach($this->files as $file) {
            $value = md5_file($file, true);
            $hash ^= $value;
        }
        
        return urlencode(
            'css|'.
            bin2hex($hash)
        );
    }
  
    public function getResult(&$noCache) {
        $noCache = true;
        $result = '';
        foreach($this->_files as $file)
            $result .= file_get_contents($file);
        
        global $environment;
        if($environment->getStatus() == Environment::PRODUCTION)
            return preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $result); // remove comments
        
        return $result;
    }
  
}