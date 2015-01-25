<?php

class Kansas_String {
    /**
     * Determina si el principio de una cadena coincide con una cadena especificada.
     * @param string $self Cadena completa
     * @param string $value Cadena a comparar
     * @return boolean Es true si value coincide con el principio de esta cadena; en caso contrario, es false.
     */
	public static function startWith($self, $value) {
		return substr($self, 0, strlen($value)) == $value;
	}

}