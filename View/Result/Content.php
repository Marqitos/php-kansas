<?php declare(strict_types = 1);
/**
 * Representa el resultado de una solicitud, en la que se va a devolver el contenido indicado
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright 2024, Marcos Porto
 * @since v0.4
 */

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultAbstract;
use function System\String\isNullOrEmpty as StringIsNullorEmpty;
use function basename;
use function header;
use function is_string;
use function md5;

require_once 'Kansas/View/Result/ViewResultAbstract.php';

class Content extends ViewResultAbstract {
    
  protected $download = false;
  private $content;
  private $etag;

  public function __construct(string $content, string $mimeType, string $etag = null) {
    parent::__construct($mimeType);
    $this->content = $content;
    $this->etag = $etag;
  }

  protected function sendHeaders($cache = false) : bool {
    require_once 'System/String/isNullOrEmpty.php';
    if (StringIsNullorEmpty($this->etag)) {
      $this->etag = md5($this->content);
    }
    if (parent::sendHeaders()) {
      if(is_string($this->download)) {
        header('Content-Disposition: attachment; filename="' . basename($this->download) . '"');
      } else {
        $disposition = 'Content-Disposition: ' . ($this->download
          ? 'attachment'
          : 'inline');
        header($disposition);
      }
      header('Content-Length: ' . strlen($this->content));
      return true;
    }
    return false;
  }

  public function executeResult() {
    if ($this->sendHeaders()) {
      echo $this->content;
    }
    return true;
  }

}
