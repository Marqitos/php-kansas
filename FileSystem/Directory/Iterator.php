<?php

class Kansas_File_Directory_Iterator 
	implements Iterator {
	
	private $_handle;
	private $_current = false;
	
	
	public function __construct($directory) {
		if(is_dir($directory))
			$this->_handle = opendir($directory);
		else
			throw new Exception('Directorio no valido');
	}
			
	public function __destruct() {
		closedir($this->_handle);	
	}
	
  /* (non-PHPdoc)
   * @see Iterator::current()
   */
  public function current() {
		return new Kansas_File($this->_current);
  }
  
  /* (non-PHPdoc)
   * @see Iterator::next()
   */
  public function next() {
		$this->_current = readdir($this->_handle);
  }
  
  /* (non-PHPdoc)
   * @see Iterator::key()
   */
  public function key() {
    return $this->_current;
  }
  
  /* (non-PHPdoc)
   * @see Iterator::valid()
   */
  public function valid() {
    return $this->_current != false;
  }
  
  /* (non-PHPdoc)
   * @see Iterator::rewind()
   */
  public function rewind() {
		rewinddir($this->_handle);
  }
}