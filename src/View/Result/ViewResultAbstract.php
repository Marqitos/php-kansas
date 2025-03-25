<?php declare(strict_types = 1);
/**
 * Proporciona la funcionalidad básica del resultado de una solicitud
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2024, Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultInterface;

use function header;
use function is_int;
use function is_string;
use function gmdate;
use function trim;

require_once 'Kansas/View/Result/ViewResultInterface.php';

abstract class ViewResultAbstract implements ViewResultInterface {
    
  protected function __construct(
    protected string $mimeType = ''
  ) {}
  
  // Obtiene o establece el tipo de contenido de archivo
  public function getMimeType() : string {
    return $this->mimeType;
  }
  public function setMimeType(string $value) : void {
    $this->mimeType = $value;
  }
  
  /**
    * Envía las cabecera http
    *
    * @param mixed $cache false: No se usará cache. int: cache basado en tiempo, string: cache basado en Etag
    * @return bool true: hay que mandar el contenido, false: el cliente tiene el contenido en cache
    */
  protected function sendHeaders($cache = false) : bool {
    $mimeType = $this->getMimeType();
    if (!empty($mimeType)) {
      header('Content-Type: ' . $mimeType);
    }
    if ($cache) {
      header ("cache-control: must-revalidate");
      if (is_int($cache)) {
        header ("expires: " . gmdate ("D, d M Y H:i:s", time() + $cache) . " GMT");
      }
      if (is_string($cache)) {
        header('Etag: "' . $cache . '"');
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            trim($_SERVER['HTTP_IF_NONE_MATCH']) == $cache) {
          header ("HTTP/1.1 304 Not Modified");
          return false;
        }
      }
    } else {
      header('Cache-Control: no-cache');
    }
    return true;
  }

}
