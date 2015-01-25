<?php

class Kansas_Core_Iterator
  implements Iterator {
    
  private $_array = array();
  private $_position = 0;
  
  public function __construct() {
    foreach(func_get_args() as $arg) {
      if(is_array($arg))
        $this->_array = array_merge($this->_array, $arg);
      else
        $this->_array[] = $arg;
    }
  }
  
  /* (non-PHPdoc)
   * @see Iterator::current()
   */
  public function current() {
    return $this->_array[$this->_position];
  }
  
  /* (non-PHPdoc)
   * @see Iterator::next()
   */
  public function next() {
    ++$this->_position;
  }
  
  /* (non-PHPdoc)
   * @see Iterator::key()
   */
  public function key() {
    return $this->_position;
  }
  
  /* (non-PHPdoc)
   * @see Iterator::valid()
   */
  public function valid() {
  	return isset($this->_array[$this->_position]);
  }
  
  /* (non-PHPdoc)
   * @see Iterator::rewind()
   */
  public function rewind() {
    $this->_position = 0;
  }

  
  
    
    
}