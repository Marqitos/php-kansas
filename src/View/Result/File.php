<?php declare(strict_types = 1);
/**
  * Representa el resultado de una solicitud, en la que se va a devolver un archivo
  *
  * @package    Kansas
  * @author     Marcos Porto Mariño
  * @copyright  2025, Marcos Porto <lib-kansas@marcospor.to>
  * @since      v0.4
  */

namespace Kansas\View\Result;

use MIME_Type;
use Kansas\View\Result\FileAbstract;
use function class_exists;
use function finfo_open;
use function finfo_file;
use function finfo_close;
use function function_exists;
use function header;
use function realpath;
use function set_time_limit;
use function strtolower;
use const FILEINFO_MIME_TYPE;

require_once 'Kansas/View/Result/FileAbstract.php';

class File extends FileAbstract {

  private $filename;
  // TODO: Aceptar rangos de bytes
  private $chunksize; // tamaño del buffer, false para usar el metodo integrado de php
  private $eTag;

  public function __construct(string $filename, array $options = []) {
    $this->filename     = realpath($filename);
    $this->download     = isset($options['download'])
                        ? $options['download']
                        : false;
    $this->chunksize    = isset($options['chunksize'])
                        ? $options['chunksize']
                        : false;
    $this->eTag         = isset($options['eTag'])
                        ? $options['eTag']
                        : false;
    $this->size         = isset($options['size'])
                        ? $options['size']
                        : false;
    if(isset($options['mime'])) {
        parent::__construct($options['mime']);
    }
  }

  // Obtiene o establece el tipo de contenido de archivo
  public function getMimeType() : string {
    if(empty($this->mimeType)) {
      if(class_exists("MIME_Type", false)) {
        return MIME_Type::autoDetect($this->filename);
      }
      if(function_exists("finfo_open")) {
        try {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          return finfo_file($finfo, $this->filename);
        } finally {
          finfo_close($finfo);
        }
      }
    }
    return $this->mimeType;
  }

  /**
    * @inheritdoc
    */
  public function executeResult() {
    $cnt        = 0;
    $sendFile   = parent::sendHeaders($this->eTag);
    if(!$sendFile) {
        return $cnt;
    }

    if($this->getUseXSendFile()) {
      $filename = (strtolower(substr(php_uname('s'), 0, 3)) == 'win')
        ? str_replace('\\', '/', $this->filename)
        : $this->filename;
      header("X-SENDFILE: " . $filename);
    } else {
      set_time_limit(0);
      if($this->chunksize) {
        $buffer = '';
        $handle = fopen($this->filename, 'rb');
        if($handle) {
          while (!feof($handle)) {
            $buffer = fread($handle, $this->chunksize);
            echo $buffer;
            ob_flush();
            flush();
            $cnt += strlen($buffer);
          }
          $status = fclose($handle);
          if(!$status) {
            $cnt = false;
          }
        } else {
          $cnt = false;
        }
      } else {
        $cnt = readfile($this->filename);
      }
    }
    return $cnt;
  }

  /**
    * Devuelve si está instalado el modulo X-Sendfile
    *
    * @return boolean
    */
  public function getUseXSendFile() {
    return function_exists('apache_get_modules') &&
           in_array('mod_xsendfile', apache_get_modules());
  }

}
