<?php

/**
 * Representa una respuesta a una solicitud, que devuelve texto
 * @author Marcos
 *
 */
interface Kansas_View_Result_String_Interface
	extends Kansas_View_Result_Interface {
  /**
   * Devuelve el texto a enviar
   */	
  public function getResult(&$noCache);
}