<?php
require_once 'System/Guid.php';
require_once 'Kansas/Core/Object.php';
require_once 'Kansas/Core/GuidItem/Interface.php';

/**
 * Representa un objeto identificado mediante un GUID
 * @author Marcos
 * @package Kansas
 *
 */
class Kansas_Core_GuidItem
  extends Kansas_Core_Object
  implements Kansas_Core_GuidItem_Interface {
	
// Campos
  /**
   * Almacena el Id del objeto
   * @var System_Guid
   */
  private $_id;
	
// Constructor
  /**
   * Crea un objeto con el Id especificado
   * @param string|System_Guid|callback $id
   */
  protected  function __construct($id) {
    if(is_string($id))
      $id = new System_Guid($id);
    
    $this->_id = $id;
  }

// Miembros de Kansas_Core_GuidItem_Interface
  /**
   * (non-PHPdoc)
   * @see Kansas_Core_GuidItem_Interface::getId()
   */
  public function getId() {
    if(is_callable($this->_id))
      $this->_id = call_user_func($this->_id);
    return $this->_id;
  }
	
}
