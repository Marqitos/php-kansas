<?php
/**
 * Representa un objeto que se identifica como unico mediante un UUID
 * @author Marcos
 *
 */
interface Kansas_Core_GuidItem_Interface {
  /**
   * Obtiene el Id del objeto
   * @return System_Guid Id del objeto.
   */
  public function getId();
	
	// Obtiene si se ha establecido un Id al objeto.
	public function hasId();
}