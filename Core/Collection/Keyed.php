<?php

abstract class Kansas_Core_Collection_Keyed
	implements Kansas_Core_Collection_Interface {
	
	protected $offset;
	
	/**
	 * Crea el objeto e inserta los elementos
	 * @param Traversable $array
	 */
	protected function __construct(Traversable $array = null) {
		$this->offset = [];
		$this->addRange($array);
	}
	
	// Miembros de ArrayAccess
	public function offsetExists($offset) {
		$key = $this->parseKey($offset);
		return isset($this->offset[$key]);
	}
	public function offsetGet($offset) {
		$key = $this->parseKey($offset);
		if (array_key_exists($key, $this->offset))
			return $this->offset[$key];
		else
			return null;
	}
	public function offsetSet($offset, $value) {
		throw new System_NotSupportedException('Metodo no soportado. Utilice el metodo "add" para insertar elementos en la colección.');
	}
	public function offsetUnset($offset) {
		$key = $this->parseKey($offset);
		unset($this->offset[$key]);
	}

	// Miembros de IteratorAggregate
	public function getIterator() {
		return new ArrayIterator($this->offset);
	}
	
	// Miembros públicos
	/**
	 * Agrega un nuevo elemento.
	 * @param mixed $item Elemento a añadir
	 */
	public function add($item) {
		$key = $this->getKey($item);
		$this->offset[$key] = $item;
	}
	/**
	 * Agrega una colección de elementos.
	 * @param Traversable $items Elementos a añadir
	 */
	public function addRange(Traversable $items = null) {
		if($items == null)
			return 0;
		$count = 0;
		foreach($items as $item) {
			$key = $this->getKey($item);
			$this->offset[$key] = $item;
			$count++;
		}
		return $count;
	}
	
	public function count() {
		return count($this->offset);
	}
	
	// Metodos abstractos
	/**
	 * Al implementarlo debe devolver las claves de la colección a partir del valor
	 * @param mixed $item Valor almacenado en la colección
	 * @return mixed Clave correspondiente al objeto
	 */
	protected abstract function getKey($item);		//key
	/**
	 * Al implementarlo se asegura que se envia una clave válida para realizar la busqueda
	 * @param mixed $offset
	 * @return mixed Clave válida
	 */
	protected abstract function parseKey($offset); 	//key
	
}