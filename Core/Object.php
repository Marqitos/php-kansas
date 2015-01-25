<?php
/**
 * Objeto que permite usar los metodos 'getProperty()' y 'setProperty($value)', directamente
 * como si fuesen propiedades
 * @author Marcos
 *
 */
class Kansas_Core_Object {
  /**
   * Devuelve el valor de una propiedad, causa un error si no existe.
   * @param string $name Nombre de la propiedad
   * @throws System_NotSupportedException
   */
  public function __get($name) {
    $name = ucfirst($name);
  	if($method = Kansas_Core_Object_getPublicMethod($this, 'get' . $name))
  	  return $method->invoke($this);
  	else
  	  throw new System_NotSupportedException();
  }
  
  /**
   * Establece el valor a una propiedad.
   * @param string $name
   * @param mixed $value
   */
  public function __set($name, $value) {
    $name = ucfirst($name);
    if($method = Kansas_Core_Object_getPublicMethod($this, 'set' . $name))
  	  return $method->invoke($this, $value);
  }

}
/**
 * Devuelve un metodo publico de un objeto, y con el nombre indicado
 * @param mixed $object Objeto en el que buscar el metodo
 * @param string $name Nombre del metodo
 * @return ReflectionMethod|false Devuelve la información del metodo si existe; false en caso contrario.
 */
function Kansas_Core_Object_getPublicMethod($object, $name) {
  $reflector	= new ReflectionClass($object);
  if(!$reflector->hasMethod($name))
    return false;
  $method = $reflector->getMethod($name);
  return $method->isPublic() ?
  	$method:
  	false;
}