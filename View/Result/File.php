<?php 
namespace Kansas\View\Result;

use Kansas\View\Result\FileAbstract;
use function realpath;
use function finfo_open;
use function finfo_file;
use function finfo_close;
use function strtolower;
use function header;

require_once 'Kansas/View/Result/FileAbstract.php';

class File extends FileAbstract {
    
  private $_filename;
  private $_chunksize; // how many bytes per chunk 
  private $_retbytes;
    
  public function __construct($filename, array $options = []) {
    $this->_filename	= realpath($filename);
    $this->_retbytes	= isset($options['retbytes']) ? $options['retbytes'] : true;
    $this->download   = isset($options['download']) ? $options['download'] : false;
    $this->_chunksize	= 1*(1024*1024);
  }
  
    // Obtiene o establece el tipo de contenido de archivo	
    public function getMimeType() {
        if(empty($this->_mimeType)) {
            if(class_exists("MIME_Type")) {
                return MIME_Type::autoDetect($this->_filename);
            }
            if(function_exists("finfo_open")) {
                try {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    return finfo_file($finfo, $this->_filename);
                } finally {
                    finfo_close($finfo);
                }
            }
        }
        return $this->_mimeType;
    }  
  
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
    parent::sendHeaders();
    
    if($this->getUseXSendFile()) {
      if(strtolower(substr(php_uname('s'), 0, 3)) == 'win')
        $filename = str_replace('\\', '/', $this->_filename);
      else
        $filename = $this->_filename;
      header("X-SENDFILE: " . $filename);
    } else {
      $buffer = ''; 
      $cnt =0; 
      $handle = fopen($this->_filename, 'rb'); 
      if ($handle === false) 
        return false; 
      while (!feof($handle)) { 
        $buffer = fread($handle, $this->_chunksize); 
        echo $buffer; 
        ob_flush(); 
        flush(); 
        if ($this->_retbytes)
          $cnt += strlen($buffer); 
      } 
      $status = fclose($handle); 
      return ($this->_retbytes && $status)?
        $cnt: // return num. bytes delivered like readfile() does. 
        $status; 
    }
  }
 
  public function getUseXSendFile() { // Por defecto devuelve true
    return !function_exists('apache_get_modules') || in_array('mod_xsendfile', apache_get_modules());
  }

}