<?php 

class Kansas_View_Result_Redirect
  implements Kansas_View_Result_Interface {
    
  const MOVED_PERMANENTLY = 301;
  const MOVED_TEMPORARILY = 302;
  
  private $_location;
  private $_code = self::MOVED_TEMPORARILY;
  
  /**
   * Obtiene el tipo de redirección.
   * @return int Codigo de redirección.
   */
  public function getCode() {
    return $this->_code;
  }
  /**
   * Establece el tipo de redirección
   * @param int $code Codigo de redirección
   * @throws Zend_Controller_Action_Exception Si se indica un codigo de redirección no válido.
   */
  public function setCode($code) {
    $this->_code      = (int)$code;
    if ((300 > $this->_code) || (307 < $this->_code) || (304 == $this->_code) || (306 == $this->_code)) {
      require_once 'Zend/Controller/Action/Exception.php';
      throw new Zend_Controller_Action_Exception('Invalid redirect HTTP status code (' . $code  . ')');
    }
  }
  

  /**
   * Establece la dirección de redirección mediante una URL
   * @param  string $url
   * @return void
   */
  public function setGotoUrl($url) {
        // prevent header injections
        $url = str_replace(array("\n", "\r"), '', $url);

        // If relative URL, decide if we should prepend base URL
      /*  if (!preg_match('|^[a-z]+://|', $url)) {
            $url = $this->_prependBase($url);
        }*/

        $this->_location = $url;
    }
    
  /* (non-PHPdoc)
   * @see Kansas_View_Result_Interface::executeResult()
   */
  public function executeResult () {
		Kansas_Response::redirect($this->_location);
		return true;
  }

    
}
