<?php 

namespace Kansas\View\Result;

use Kansas\View\Result\ViewResultInterface;
use System\Net\WebException;

require_once 'Kansas/View/Result/ViewResultInterface.php';

class Redirect implements ViewResultInterface {
    
  const MOVED_PERMANENTLY = 301;
  const MOVED_TEMPORARILY = 302;
  
  private $location;
  private $code = self::MOVED_TEMPORARILY;
  
  /**
   * Obtiene el tipo de redirección.
   * @return int Codigo de redirección.
   */
  public function getCode() {
    return $this->code;
  }
  /**
   * Establece el tipo de redirección
   * @param int $code Codigo de redirección
   * @throws WebException Si se indica un codigo de redirección no válido.
   */
  public function setCode($code) {
    $this->code      = (int)$code;
    if ((300 > $this->code) ||
        (307 < $this->code) ||
        (304 == $this->code) ||
        (306 == $this->code)) {
      require_once 'System/Net/WebException.php';
      throw new WebException($code, 'Invalid redirect HTTP status code (' . $code  . ')');
    }
  }
  

  /**
   * Establece la dirección de redirección mediante una URL
   * @param  string $url
   * @return void
   */
  public function setGotoUrl($url) {
    $this->location = str_replace(["\n", "\r"], '', $url);
  }
    
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
    self::redirect($this->location);
    return true;
  }
  
  public static function gotoUrl($url, $code = self::MOVED_TEMPORARILY) {
    $result = new self();
    $result->setCode($code);
    $result->setGotoUrl($url);
    return $result;
  }

  public static function redirect($location, $exit = true) {
    header("Location: " . $location);
    if ($exit) {
      exit;
    }
  }

}
